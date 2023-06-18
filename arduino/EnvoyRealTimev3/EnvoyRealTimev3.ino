#include "secrets.h"
#include "index.h"
#include <Timer.h>
#include <TimeAlarms.h>
#include <TimeLib.h>
#include <Time.h>
#include <NTPClient.h>
#include <LedControl.h>
#include <FontLEDClock.h>
#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPUpdateServer.h>    
#include <WiFiClient.h>                 
#include <AutoConnect.h>
#include <Arduino_JSON.h>

#define NUM_MAX 4
#define CS_PIN  0   // D3
#define DIN_PIN 13  // D7
#define CLK_PIN 14  // D5
#define MAX_COL pow(2,5)
#define SECS_ROW MAX_ROW - 1
#define MAX_ROW 8
#define ON 1
#define OFF 0
#define DEFAULT_INTENSITY 7

#define HALF_SEC 500
#define ONE_SEC 1000
#define SECS_10 10
#define SECS_20 20
#define HALF_MINUTE 30
#define ONE_MINUTE 60
#define TEN_MINUTES 600
#define ONE_HOUR 3600
#define UPDATE_INTERVAL 3600000UL
#define DEFAULT_OFFSET 19800
#define IST_OFFSET 19800
#define MAX_PARAMS 8
time_t prevDisplay = 0;
static const char url[] PROGMEM = "http://envoy.local/production.json";
static const char urlUsage [] PROGMEM = "http://lamp.local/envoy/srvmonthly.php";

static const char MainPage[] PROGMEM = R"(
{ "title": "Envoy Real Time Display", "uri": "/", "menu": true, "element": [
    { "name": "caption", "type": "ACText", "value": "<h2>Envoy Real Time Display</h2>",  "style": "text-align:center;color:#2f4f4f;padding:10px;" },
    { "name": "content", "type": "ACText", "value": "In this page, place the custom web page handled by the Sketch application." } ]
}
)";

Timer timer;
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");
LedControl lc = LedControl(DIN_PIN, CLK_PIN, CS_PIN, NUM_MAX);

ESP8266WebServer httpServer;                
ESP8266HTTPUpdateServer httpUpdate;         
AutoConnect portal(httpServer);             
AutoConnectAux update(update_path, "UPDATE"); 
AutoConnectAux mainPage;

byte displayIntensity;
float consumption = 0.00;
float production = 0.00;
float netConsumption =  0.00; 
float energyProduced = 0.00;
float energyConsumed = 0.00;
float monthProduction = 0.00;
float monthConsumption = 0.00;
float monthNetConsumption = 0.00;
byte displayParam = 0;

void setup()
{
  httpUpdate.setup(&httpServer, update_username, update_password); 
  mainPage.load(MainPage);
  portal.join({ mainPage, update });
  Serial.begin(115200);
  checkDisplay();
  if (portal.begin())
    displayWifiConnected();
  timeClient.begin();
  timeClient.setTimeOffset(IST_OFFSET);
  timeClient.setUpdateInterval(ONE_SEC * 60);
  setSyncProvider(getTimeFromNTP);
  setSyncInterval(ONE_MINUTE);
  timeClient.forceUpdate();
  clearScreen();
  while (year() == 1970)
    displayWaitMessage();
  timeClient.setUpdateInterval(UPDATE_INTERVAL);
  setSyncInterval(ONE_HOUR * 3);
  clearScreen();
  Alarm.timerRepeat(ONE_HOUR * 3, displayIPAddress);
  Alarm.timerRepeat(ONE_MINUTE, pullEnvoyData);
  Alarm.timerRepeat(SECS_10, displayEnvoyData);
  displayLoadingMessage();
  pullEnvoyData();
  httpServer.on("/", handleRoot);
  httpServer.on("/getProdData", getProdData);
  httpServer.on("/getConsData", getConsData);
  httpServer.on("/getNetData", getNetData);    
}

void loop()
{
  httpServer.handleClient();
  if (now() != prevDisplay)
  {
    prevDisplay = now();
    secondsDot((byte)second());
  }
  Alarm.delay(1);
  timer.update();
}

void handleRoot()
{
  String webPage = MainPage;
  httpServer.send(200, "text/html", webPage);
}

time_t getTimeFromNTP()
{
  timeClient.forceUpdate();
  return (time_t) timeClient.getEpochTime();
}

void getProdData()
{
  
}

void getConsData()
{
  
}

void getNetData()
{

}

void displayEnvoyData()
{
  char fValue[8];
  char dValue[9];
  byte fi = 0;
  byte di = 0;
  if ( consumption == 0.00 && production == 0.00 && netConsumption == 0.00 )
  {
    Serial.println("Error pulling data from Envoy");
    return;
  }

  switch(displayParam)
  {
    case 0: Serial.print("Production : "); Serial.println(production); dtostrf(production,6,1,fValue);dValue[di++] = 'P';dValue[di++] = ':';break;
    case 1: Serial.print("Consumption : "); Serial.println(consumption); dtostrf(consumption,6,1,fValue);dValue[di++] = 'C';dValue[di++] = ':';break;
    case 2: Serial.print("Net Power : "); Serial.println(netConsumption); dtostrf(abs(netConsumption),6,1,fValue);dValue[di++] = (netConsumption >0) ? 'I' : 'E';dValue[di++] = ':';break;
    case 3: Serial.print("Power Generated : "); Serial.println(energyProduced);dtostrf(energyProduced,5,2,fValue);dValue[di++] = 'P';dValue[di++]='G';dValue[di++]=':';break;
    case 4: Serial.print("Power Consumed : "); Serial.println(energyConsumed);dtostrf(energyConsumed,5,2,fValue);dValue[di++] = 'P';dValue[di++]='C';dValue[di++]=':';break;
    case 5: Serial.print("Curr Month Production : ");Serial.println(monthProduction);dtostrf(monthProduction,5,1,fValue);dValue[di++] = 'M';dValue[di++]='P';dValue[di++]=':';break;
    case 6: Serial.print("Curr Month Consumption : ");Serial.println(monthConsumption);dtostrf(monthConsumption,5,1,fValue);dValue[di++] = 'M';dValue[di++]='C';dValue[di++]=':';break;
    case 7: Serial.print("Curr Month Net Consumption : ");Serial.println(monthNetConsumption);dtostrf(abs(monthNetConsumption),5,1,fValue);dValue[di++] = 'M';dValue[di++]= (monthNetConsumption) > 0 ? 'E' : 'I';dValue[di++]=':';break;
  }
  
  while(fValue[fi])
    dValue[di++] = fValue[fi++];

  di = 0;
  while(dValue[di])
  {
     puttinychar((di * 4), 1, dValue[di]);
     delay(35);
     di++;  
  }
  displayParam = (displayParam + 1) % MAX_PARAMS;
}

void pullEnvoyData()
{
  char path[40];
  sprintf_P(path, url);
  Serial.println(path);
  DynamicJsonDocument doc(3072);
  DeserializationError error = deserializeJson(doc, httpGETRequest(path));
  if (error)
  {
    Serial.print(F("deserializeJson() failed: "));
    Serial.println(error.f_str());
    return;
  }
  JsonObject consumption_0 = doc["consumption"][0];
  JsonObject consumption_1 = doc["consumption"][1];
  JsonObject production_1 = doc["production"][1];
  consumption = truncate(consumption_0["wNow"],2);
  production = truncate(production_1["wNow"],2);
  netConsumption =  truncate(consumption_1["wNow"],2);
  energyProduced = truncate(production_1["whToday"],0);
  energyProduced = truncate(energyProduced/1000,2);
  energyConsumed = truncate(consumption_0["whToday"],0);
  energyConsumed = truncate(energyConsumed/1000,2);
  if ( consumption == 0.00 && production == 0.00 && netConsumption == 0.00 )
  {
    Serial.println("Error pulling data from Envoy");
    return;
  }
  doc.clear();
  sprintf_P(path, urlUsage);
  Serial.println(path);
  error = deserializeJson(doc, httpGETRequest(path));
  if (error)
  {
    Serial.print(F("deserializeJson() failed: "));
    Serial.println(error.f_str());
    return;
  }

  float CurrMonthProduction = doc["CurrMonthProduction"]; 
  float CurrMonthConsumption = doc["CurrMonthConsumption"]; 
  float CurrMonthNetConsumption = doc["CurrMonthNetConsumption"];
  
  monthProduction = truncate(CurrMonthProduction,1);
  monthConsumption = truncate(CurrMonthConsumption,1);
  monthNetConsumption = truncate(CurrMonthNetConsumption,1);
}

float truncate(float val, byte dec) 
{
    float x = val * pow(10, dec);
    float y = round(x);
    float z = x - y;
    if ((int)z == 5)
    {
        y++;
    } else {}
    x = y / pow(10, dec);
    return x;
}

void checkDisplay()
{
  displayIntensity = DEFAULT_INTENSITY;
  for (byte address = 0; address < lc.getDeviceCount(); address++)
  {
    lc.shutdown(address, false);
    lc.setIntensity(address, displayIntensity);
    lc.clearDisplay(address);
  }
  byte intensity;
  for (byte address = 0; address < NUM_MAX; address++)
    lc.setIntensity(address, 0);

  for (byte x = 0; x < MAX_COL ; x++ )
  {
    for (byte y = 0; y < MAX_ROW; y++)
      plot(x, y, ON);
    delay(50);
  }

  for (intensity = 1 ; intensity <= displayIntensity; intensity++)
  {
    for (byte address = 0; address < NUM_MAX; address++)
      lc.setIntensity(address, intensity);
    delay(ONE_SEC);
  }
  for (intensity = displayIntensity ; intensity >= 1 ; intensity--)
  {
    for (byte address = 0; address < NUM_MAX; address++)
      lc.setIntensity(address, intensity);
    delay(ONE_SEC);
  }

  for (byte address = 0; address < lc.getDeviceCount(); address++)
    lc.setIntensity(address, displayIntensity);

  clearScreen();
  char msg[9] = "..WiFi..";
  byte i = 0;
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
}

void secondsDot(byte seconds)
{
  if (seconds % 2 == 0)
  {
    if (seconds == 0)
      plot(30, SECS_ROW, OFF);
    else
      plot(seconds / 2, SECS_ROW, OFF);

    plot((seconds / 2) + 1, SECS_ROW, ON);
  }
}

void clearSecondsDot()
{
  for (byte x = 0; x < MAX_COL ; x++ )
    plot(x, SECS_ROW, OFF);
}

void clearScreen()
{
  for (byte address = 0; address < lc.getDeviceCount(); address++)
    lc.clearDisplay(address);
}

void displayWifiConnected()
{
  Serial.println("Connected");
  char msg[9] = "Conn. Up";
  byte i = 0;
  clearScreen();
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
  delay(ONE_SEC * 5);
  displayIPAddress();
}

void displayLoadingMessage()
{
  char msg[9] = "Loading.";
  byte i = 0;
  clearScreen();
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }  
}
void displayWaitMessage()
{
  char msg[9] = "..Wait..";
  byte i = 0;
  clearScreen();
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
  delay(ONE_SEC * 5);
  timeClient.forceUpdate();
}

void displayIPAddress()
{
  String strIPAddress = WiFi.localIP().toString();
  byte pos = 0;
  byte i = 0;
  byte idx = 0;
  char ipAddress[16] = "";
  char subIP[4][4] = {"", "", "", ""};
  char msg[9] = "";
  const char dot = '.';
  byte digits_old[4] = {99, 99, 99, 99};
  digits_old[0] = digits_old[1] = digits_old[2] = digits_old[3] = 99;
  strIPAddress.toCharArray(ipAddress, strIPAddress.length());

  while (strIPAddress[i])
  {
    if (strIPAddress[i] == dot)
    {
      subIP[idx][pos] = '\0';
      idx++;
      pos = 0;
    }
    else
    {
      subIP[idx][pos++] = strIPAddress[i];
    }
    i++;
  }
  subIP[idx][pos] = '\0';

  strcpy(msg, "");
  strcat(msg, "IP: ");
  strcat(msg, subIP[0]);
  strcat(msg, ".");
  clearScreen();
  i = 0;
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
  delay(ONE_SEC * 2);
  strcpy(msg, "");
  strcat(msg, subIP[1]);
  strcat(msg, ".");
  strcat(msg, subIP[2]);
  strcat(msg, ".");
  clearScreen();
  i = 0;
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
  delay(ONE_SEC * 2);
  strcpy(msg, "");
  strcat(msg, subIP[3]);
  clearScreen();
  i = 0;
  while (msg[i])
  {
    puttinychar((i * 4), 1, msg[i]);
    delay(35);
    i++;
  }
  delay(ONE_SEC * 2);
  clearScreen();
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

void plot (byte x, byte y, byte val)
{
  //select which matrix depending on the x coord
  byte address;
  if (x >= 0 && x <= 7)   {
    address = 3;
  }
  if (x >= 8 && x <= 15)  {
    address = 2;
    x = x - 8;
  }
  if (x >= 16 && x <= 23) {
    address = 1;
    x = x - 16;
  }
  if (x >= 24 && x <= 31) {
    address = 0;
    x = x - 24;
  }

  if (val) {
    lc.setLed(address, y, x, true);
  } else {
    lc.setLed(address, y, x, false);
  }
}

void puttinychar(byte x, byte y, char c)
{
  byte dots;
  if (c >= 'A' && c <= 'Z' || (c >= 'a' && c <= 'z') ) {
    c &= 0x1F;   // A-Z maps to 1-26
  }
  else if (c >= '0' && c <= '9') {
    c = (c - '0') + 32;
  }
  else if (c == ' ') {
    c = 0; // space
  }
  else if (c == '.') {
    c = 27; // full stop
  }
  else if (c == ':') {
    c = 28; // colon
  }
  else if (c == '\'') {
    c = 29; // single quote mark
  }
  else if (c == '!') {
    c = 30; // single quote mark
  }
  else if (c == '?') {
    c = 31; // single quote mark
  }
  else if (c == '-') {
    c = 42; // hyphen mark
  }
  for (byte col = 0; col < 3; col++) {
    dots = pgm_read_byte_near(&mytinyfont[c][col]);
    for (char row = 0; row < 5; row++) {
      if (dots & (16 >> row))
        plot(x + col, y + row, 1);
      else
        plot(x + col, y + row, 0);
    }
  }
}