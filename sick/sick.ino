#include <Adafruit_Soundboard.h>
#include <ArduinoOTA.h>
#include <TelnetStream.h>

#define PUMP_RELAY_PIN      27
#define SFX_RESET_PIN 21

#define DOOR_MOVE_TIME_MS 16000

#define DEVICE_NAME "sick"
#define WIFI_CONNECT_TIMEOUT_MS 15000

WiFiClient client;

Adafruit_Soundboard sfx = Adafruit_Soundboard(&Serial1, NULL, SFX_RESET_PIN);

void setup() {  
  pinMode(PUMP_RELAY_PIN, OUTPUT);
  pumpOff();
  
  Serial.begin(115200);
  while (!Serial);

  initWiFi();
  initSound();

  randomSeed(analogRead(A2));
}

void initWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin("Halloween", "veryspooky");
  while (WiFi.waitForConnectResult() != WL_CONNECTED) {
    Serial.println("WiFI Connection Failed");
    delay(1000);

    if (millis() > WIFI_CONNECT_TIMEOUT_MS) {
      Serial.println("Giving up. Continuing without WiFi");
      break;
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    ArduinoOTA.setHostname(DEVICE_NAME);
    ArduinoOTA.begin();
  
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());

    TelnetStream.begin();
  }
}

void initSound() {
  Serial1.begin(9600);

  while (!sfx.reset()) {
    Serial.println("Waiting for SFX board");
    TelnetStream.println("Waiting for SFX board");
    delay(1000);
  }
  Serial.println("SFX board found");
  TelnetStream.println("SFX board found");

  for (int i = 0; i < 100; i++) {
    sfx.volUp();
  }
}

void playSound() {
  long trackNumber = random(0, 4);
  char trackName[20];
  sprintf(trackName, "T0%d     WAV", trackNumber);

  Serial.print("Playing");
  Serial.println(trackName);

  sfx.playTrack(trackName);
}

void waitForSound() {
  uint32_t current, total;
  while (sfx.trackTime(&current, &total)) {
    delay(1000);
  }
}

void loop() {
  ArduinoOTA.handle();
  
  playSound();
  waitForSound();

  pumpOn();
  delay(30000);
  pumpOff();

  nextTriggerDelay();
}

void nextTriggerDelay() {
  Serial.println("Next trigger delay");
  for (int i = 1; i < 30; i++) {
    delay(1000);
    ArduinoOTA.handle();
  }
}

void pumpOn() {
  Serial.println("Pump on");
  digitalWrite(PUMP_RELAY_PIN, LOW);
}

void pumpOff() {
  Serial.println("Pump off");
  digitalWrite(PUMP_RELAY_PIN, HIGH);
}
