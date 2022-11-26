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

#define IST_OFFSET 19800
#define ONE_SEC 1000
#define ONE_MINUTE 60
#define TEN_MINUTES 600
#define ONE_HOUR 3600
#define UPDATE_INTERVAL 3600000UL

const char* host = "ESPNetMeter";
const char* update_path = "/firmware";
const char* update_username = "ESPNode";
const char* update_password = "*******";
const char* AP_ssid = "ESPNode";
const char* AP_password = "*******";

const byte lcdColumns = 20;
const byte lcdRows = 4;

#define LCD_I2C_ADDR 0x27
#define LCD_ROWS 4
#define LCD_COLUMNS 20
#define LCD_BACKLIGHT_ID 1
#define ROW_1 0
#define ROW_2 1
#define ROW_3 2
#define ROW_4 3

ESP8266WebServer httpServer(80);
ESP8266HTTPUpdateServer httpUpdater;
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");
LiquidCrystal_I2C lcd(LCD_I2C_ADDR, LCD_COLUMNS, LCD_ROWS);

//char monthNames[13][4] PROGMEM = {"", "Jan", "Feb", "Mar","Apr", "May", "Jun","Jul", "Aug", "Sep","Oct", "Nov", "Dec"};

const char url[] PROGMEM = "http://lamp.local/usage.php?src=ESP&prev=%s&readingDate=%4d-%02d-%02d";
const byte dateRow PROGMEM = 0;
const byte dateCol PROGMEM = 7;

const byte impReadRow PROGMEM = 1;
const byte impReadCol PROGMEM = 4;

const byte expReadRow PROGMEM = 2;
const byte expReadCol PROGMEM = 4;

const byte netImpRow PROGMEM = 1;
const byte netImpCol PROGMEM = 16;

const byte netExpRow PROGMEM = 2;
const byte netExpCol PROGMEM = 16;

const byte lastUpdRow PROGMEM = 3;
const byte lastUpdCol PROGMEM = 14;

const byte lenImpExp PROGMEM = 6;
const byte lenNetImpExp PROGMEM = 4;
const byte lenDate PROGMEM = 5;

const byte readingIntLen PROGMEM = 4;
const byte netReadingIntLen PROGMEM = 2;
const byte readingResolution PROGMEM = 1;

void setup()
{
  lcd.init();
  lcd.backlight();
  lcd.clear();
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
  printLCDVal(ROW_1,0,"CONNECTED TO WIFI...",true);
  timeClient.begin();
  timeClient.setTimeOffset(IST_OFFSET);
  timeClient.setUpdateInterval(ONE_SEC * 60);
  setSyncProvider(getTimeFromNTP);
  setSyncInterval(ONE_MINUTE);
  timeClient.forceUpdate();
  while (year() == 1970)  displayWaitMessage();
  //Serial.println("Received Current Date/Time from Network NTP Server");
  printLCDVal(ROW_3,1,"NTP UPDATE DONE...",true);
  delay(ONE_SEC * 60);
  lcd.clear();
  timeClient.setUpdateInterval(UPDATE_INTERVAL);
  setSyncInterval(ONE_HOUR * 3);
  Alarm.timerRepeat(TEN_MINUTES,pullData);
  setupLCDLabel();
  pullData();
}

void loop()
{
  httpServer.handleClient();
  MDNS.update();
  Alarm.delay(1);
}

void setupLCDLabel()
{
  printLCDVal(ROW_1,2,"DATE:",true);

  printLCDVal(ROW_2,0,"IMP:",true);
  printLCDVal(ROW_3,0,"EXP:",true);
 
  printLCDVal(ROW_2,11,"PDIM:",true);
  printLCDVal(ROW_3,11,"PDEX:",true);

  printLCDVal(ROW_4,0,"LAST UPDATED:",true);
}
void pullData()
{
  char path[60];
  StaticJsonDocument<2048> doc;
  sprintf_P(path, url,"true", year(),month(),day());
  //sprintf_P(path, url, 2022,8,10);
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
  printLCDVal(dateRow,dateCol,ReadingDate,true);
  printLCDVal(lastUpdRow,lastUpdCol,ReadingTime,true);
  if(strcmp(result,"OK") == 0)
  {
    const char* ReadingImport = doc["ReadingImport"]; // "1454.10"
    const char* ReadingExport = doc["ReadingExport"]; // "1509.30"
    const char* NetImportUnits = doc["NetImportUnits"]; // "0.00"
    const char* NetExportUnits = doc["NetExportUnits"]; // "0.00"
    printLCDVal(impReadRow,impReadCol,ReadingImport,true);
    printLCDVal(expReadRow,expReadCol,ReadingExport,true);
    printLCDVal(netImpRow,netImpCol,NetImportUnits,true);
    printLCDVal(netExpRow,netExpCol,NetExportUnits,true);
    
  }
  else
  {
    printLCDVal(impReadRow,impReadCol,"0000.0",true);
    printLCDVal(expReadRow,expReadCol,"0000.0",true);
    printLCDVal(netImpRow,netImpCol,"00.0",true);
    printLCDVal(netExpRow,netExpCol,"00.0",true);    
  }
}

time_t getTimeFromNTP()
{
  timeClient.forceUpdate();
  return (time_t) timeClient.getEpochTime();
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
  printLCDVal(ROW_2,1,"WAITING FOR NTP...",true);
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