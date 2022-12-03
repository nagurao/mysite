#include <LiquidCrystal_I2C.h>
#include <ESP8266WiFi.h>
#include <WiFiClient.h>
#include <ESP8266mDNS.h>
#include <ESP8266HTTPUpdateServer.h>
#include <ESP8266WebServer.h>
#include <DNSServer.h>
#include <ESP_WiFiManager.h>
#include <Timer.h>
#include <TimeAlarms.h>
#include <TimeLib.h>
#include <Time.h>
#include <NTPClient.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ArduinoJson.h>
#include "secrets.h"
#include "indexnetmeter.h"

#define HALF_SEC 500
#define IST_OFFSET 19800
#define ONE_SEC 1000
#define FIVE_SEC 5
#define ONE_MINUTE 60
#define TEN_MINUTES 600
#define ONE_HOUR 3600
#define UPDATE_INTERVAL 3600000UL

#define LCD_I2C_ADDR 0x27
#define LCD_ROWS 4
#define LCD_COLUMNS 20
#define LCD_BACKLIGHT_ID 1
#define ROW_1 0
#define ROW_2 1
#define ROW_3 2
#define ROW_4 3

#define ARG_LCDBACKLIGHT "backlight"
#define ARG_LCDBACKLIGHTTIME "backlightTime"

ESP8266WebServer httpServer(80);
ESP8266HTTPUpdateServer httpUpdater;
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");
LiquidCrystal_I2C lcd(LCD_I2C_ADDR, LCD_COLUMNS, LCD_ROWS);
Timer timer;

char monthNames[13][4] PROGMEM = {"", "JAN", "FEB", "MAR","APR", "MAY", "JUN","JUL", "AUG", "SEP","OCT", "NOV", "DEC"};

//const char url[] PROGMEM = "http://192.168.0.245/usage.php?src=ESP&prev=%s&readingDate=%4d-%02d-%02d";
const char currDate[] PROGMEM = "%02d-%s-%04d %02d:%02d:%02d";
const char url[] PROGMEM = "http://192.168.0.245/usage.php?src=ESP";

char ReadingImportG[7];
char ReadingExportG[7];
char NetImportUnitsG[5];
char NetExportUnitsG[5];
char BillYTDImportUnitsG[7];
char BillYTDExportUnitsG[7];
char PrevBillImportG[7];
char PrevBillExportG[7];

time_t prevDisplay = 0;
byte currPage = 0;

byte backLightOnTime = 5;
boolean lcdBackLightFlag = true;
boolean firstTime = true;
void setup()
{
  lcd.init();
  lcd.clear();
  if (lcdBackLightFlag)
    lcd.backlight();
  else
    lcd.noBacklight();   
  printLCDVal(ROW_1,0,"CONNECTING TO WIFI..",true);
  Serial.begin(115200);
  ESP_WiFiManager ESP_wifiManager;
  if (ESP_wifiManager.WiFi_SSID() != "")
    ESP_wifiManager.setConfigPortalTimeout(60); //If no access point name has been previously entered disable timeout.
  ESP_wifiManager.startConfigPortal(AP_ssid, AP_password); 
  while ( (WiFi.status() != WL_CONNECTED))
  {
      delay(200);
      printLCDVal(ROW_1,0,"CONNECTING TO WIFI..",true);
  }
  if (!MDNS.begin(host)) Serial.println("Error setting up MDNS responder");
  httpUpdater.setup(&httpServer, update_path, update_username, update_password);
  httpServer.begin();
  MDNS.addService("http", "tcp", 80);
  Serial.printf("HTTPUpdateServer ready! Open http://%s.local%s in your browser and login with username '%s' and password '%s'\n", host, update_path, update_username, update_password);
  httpServer.on("/", handleRoot);
  httpServer.on("/getVersion",    getCodeVersion);
  httpServer.on("/getBackLight",  getBackLight);
  httpServer.on("/getBackLightTime",getBackLightTime);
  httpServer.onNotFound(handleNotFound);
  printLCDVal(ROW_2,0,"CONNECTED TO WIFI...",true);
  timeClient.begin();
  timeClient.setTimeOffset(IST_OFFSET);
  timeClient.setUpdateInterval(ONE_SEC * 60);
  setSyncProvider(getTimeFromNTP);
  setSyncInterval(ONE_MINUTE);
  timeClient.forceUpdate();
  printLCDVal(ROW_3,1,"WAITING FOR NTP...",true);
  while (year() == 1970)  displayWaitMessage();
  printLCDVal(ROW_4,1,"NTP UPDATE DONE...",true);
  delay(ONE_SEC * 30);
  lcd.clear();
  timeClient.setUpdateInterval(UPDATE_INTERVAL);
  setSyncInterval(ONE_HOUR * 3);
  Alarm.timerRepeat(TEN_MINUTES,pullData);
  Alarm.timerRepeat(FIVE_SEC * 2,displayLCDData);
  printLCDVal(ROW_4,0,"UPD:",true);
  pullData();
}

void loop()
{
  httpServer.handleClient();
  MDNS.update();
  if (timeStatus() != timeNotSet)
  {
    if (now() != prevDisplay)
    {
      //update the display only if time has changed
      prevDisplay = now();
      digitalClockDisplay();
    }
  } 
  Alarm.delay(1);
}

time_t getTimeFromNTP()
{
  timeClient.forceUpdate();
  return (time_t) timeClient.getEpochTime();
}

void digitalClockDisplay()
{
  char displayDate[21];
  sprintf_P(displayDate, currDate,day(),monthNames[month()],year(),hour(),minute(),second());
  printLCDVal(ROW_1,0,displayDate,false);
}

void displayLCDData()
{
  switch(currPage)
  {
    case 0:displayPageOne();break;
    case 1:displayPageTwo();break;
    case 2:displayPageThree();break;
    case 3:displayPageFour();break;
  }
  currPage = (currPage + 1 ) % 4;
}

void displayPageOne()
{
  printLCDVal(ROW_2,0,"CURR IMP UNIT:",true);
  printLCDVal(ROW_2,14,ReadingImportG,true);
  printLCDVal(ROW_3,0,"CURR EXP UNIT:",true);
  printLCDVal(ROW_3,14,ReadingExportG,true);
}
void displayPageTwo()
{  
  printLCDVal(ROW_2,0,"PREV DAY IMPORT:",true);
  printLCDVal(ROW_2,16,NetImportUnitsG,true);
  printLCDVal(ROW_3,0,"PREV DAY EXPORT:",true); 
  printLCDVal(ROW_3,16,NetExportUnitsG,true);
}

void displayPageThree()
{
  printLCDVal(ROW_2,0,"CURR BILL IMP:",true);
  printLCDVal(ROW_3,0,"CURR BILL EXP:",true);
  printLCDVal(ROW_2,14,BillYTDImportUnitsG,true);
  printLCDVal(ROW_3,14,BillYTDExportUnitsG,true);  
}

void displayPageFour()
{
  printLCDVal(ROW_2,0,"LAST BILL IMP:",true);
  printLCDVal(ROW_3,0,"LAST BILL EXP:",true);
  printLCDVal(ROW_2,14,PrevBillImportG,true);
  printLCDVal(ROW_3,14,PrevBillExportG,true);
}

void pullData()
{
  char path[40];
  StaticJsonDocument<1024> doc;
  //sprintf_P(path, url,"true", year(),month(),day());
  //sprintf_P(path, url, 2022,8,10);
  sprintf_P(path, url);
  Serial.println(path);
  DeserializationError error = deserializeJson(doc, httpGETRequest(path));
  if (error)
  {
    Serial.print(F("deserializeJson() failed: "));
    Serial.println(error.f_str());
    return;
  }
  const char* result = doc["result"];
  Serial.print("The result is : "); Serial.println(result);
  const char* ReadingDate = doc["ReadingDate"];
  const char* ReadingTime = doc["ReadingTimeHHMM"];
  printLCDVal(ROW_4,4,ReadingDate,true);
  printLCDVal(ROW_4,14,ReadingTime,true);
  if(strcmp(result,"OK") == 0)
  {
    const char* ReadingImport = doc["ReadingImport"];
    const char* ReadingExport = doc["ReadingExport"]; 
    const char* NetImportUnits = doc["NetImportUnits"]; 
    const char* NetExportUnits = doc["NetExportUnits"]; 
    const char* PrevBillImport = doc["PrevBillImport"]; 
    const char* PrevBillExport = doc["PrevBillExport"];
    const char* BillYTDImportUnits = doc["BillYTDImportUnits"];
    const char* BillYTDExportUnits = doc["BillYTDExportUnits"];
  
    strncpy(ReadingImportG,ReadingImport,6);
    strncpy(ReadingExportG,ReadingExport,6);
    strncpy(NetImportUnitsG,NetImportUnits,4);
    strncpy(NetExportUnitsG,NetExportUnits,4);
    strncpy(PrevBillImportG,PrevBillImport,6);
    strncpy(PrevBillExportG,PrevBillExport,6);
    strncpy(BillYTDImportUnitsG,BillYTDImportUnits,6);
    strncpy(BillYTDExportUnitsG,BillYTDExportUnits,6);    
  }
  if (firstTime)
  {
    firstTime = false;
    displayPageOne();
  }
  if (lcdBackLightFlag)
    lcd.backlight();
  else
  {
    lcd.backlight();
    Alarm.timerOnce(backLightOnTime * 5, lcdLightOff);
  }
}

String httpGETRequest(const char* serverName)
{
  WiFiClient client;
  HTTPClient http;
    
  // Your Domain name with URL path or IP address with path
  http.begin(client, serverName);
  
  // Send HTTP POST request
  int httpResponseCode = http.GET();
  
  String payload = "{}"; 
  
  if (httpResponseCode>0) {
    Serial.print("HTTP Response code: ");
    Serial.println(httpResponseCode);
    payload = http.getString();
  }
  else {
    Serial.print("Error code: ");
    Serial.println(httpResponseCode);
  }
  // Free resources
  http.end();

  return payload;
}

void displayWaitMessage()
{
  printLCDVal(ROW_3,1,"WAITING FOR NTP...",true);
  delay(ONE_SEC * 5);
  timeClient.forceUpdate();
}

void printLCDVal(byte row, byte column, const char* text, boolean clearFlag)
{
  byte stringLength = strlen(text);
  if (clearFlag)
  {
    lcd.setCursor(column, row);
    for (byte i = 1; i <= stringLength; i++)
      lcd.print(" ");
  }
  lcd.setCursor(column, row);
  lcd.print(text);
}

void printLCDVal(byte row, byte column, char* text, boolean clearFlag)
{
  byte stringLength = strlen(text);
  if (clearFlag)
  {
    lcd.setCursor(column, row);
    for (byte i = 1; i <= stringLength; i++)
      lcd.print(" ");
  }
  lcd.setCursor(column, row);
  lcd.print(text);
}

void printLCDVal(byte row, byte column, char text, boolean clearFlag)
{
  lcd.setCursor(column, row);
  if (clearFlag)
    lcd.print(" ");
  lcd.setCursor(column, row);
  lcd.print(text);
}

void lcdLightOff()
{
  lcd.noBacklight();
}

char* codeVersion()
{
  static char codeVersion[20];
  strcpy(codeVersion, "");
  strcat(codeVersion, __DATE__);
  strcat(codeVersion, " ");
  strcat(codeVersion, __TIME__);
  return codeVersion;
}

void handleRoot()
{
  String webPage = MainPage;
  httpServer.send(200, "text/html", webPage);
}

void handleNotFound()
{
  String webPage = ErrorPage;
  httpServer.send(404, "text/html", webPage);
}

void getBackLight()
{
  String flag = (lcdBackLightFlag) ? "1" : "0";
  httpServer.send(200, "text/plain", flag);  
}

void getBackLightTime()
{
  String onTime = String(backLightOnTime);
  httpServer.send(200, "text/plain", onTime);
}
void getCodeVersion()
{
  httpServer.send(200, "text/plain", String(codeVersion()));
}

void setBackLight()
{
  String newFlag = httpServer.arg(ARG_LCDBACKLIGHT);
  if (newFlag == "1")
    lcdBackLightFlag = true;
  else
    lcdBackLightFlag = false;

  if (lcdBackLightFlag)
    lcd.backlight();
  else
  {
    lcd.backlight();
    Alarm.timerOnce(backLightOnTime * 5, lcdLightOff);
  }
      
  httpServer.send(200, "text/plain", newFlag);  
}

void setBackLightTime()
{
  String newTime = httpServer.arg(ARG_LCDBACKLIGHTTIME);
  backLightOnTime = (byte)newTime.toInt();
  httpServer.send(200, "text/plain", newTime);
}