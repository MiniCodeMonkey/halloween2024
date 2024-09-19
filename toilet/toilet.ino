#include <Adafruit_Soundboard.h>
#include <ArduinoOTA.h>
#include <TelnetStream.h>

#define DOOR_RELAY1_PIN       32
#define DOOR_RELAY2_PIN       14
#define LIGHTS_RELAY_PIN      27
#define PIR_SENSOR_PIN        A0

#define SFX_RESET_PIN 21

#define DOOR_MOVE_TIME_MS 18000

#define DEVICE_NAME "toilet"
#define WIFI_CONNECT_TIMEOUT_MS 15000

WiFiClient client;

Adafruit_Soundboard sfx = Adafruit_Soundboard(&Serial1, NULL, SFX_RESET_PIN);

int pirValue;

void setup() {  
  pinMode(DOOR_RELAY1_PIN, OUTPUT);
  digitalWrite(DOOR_RELAY1_PIN, HIGH);

  pinMode(DOOR_RELAY2_PIN, OUTPUT);
  digitalWrite(DOOR_RELAY2_PIN, HIGH);

  pinMode(LIGHTS_RELAY_PIN, OUTPUT);
  digitalWrite(LIGHTS_RELAY_PIN, LOW);

  pinMode(PIR_SENSOR_PIN, INPUT);
  
  Serial.begin(115200);
  while (!Serial);
  
  initWiFi();
  initSound();
  
  resetDoorPosition();

  randomSeed(analogRead(A2));

  TelnetStream.println("Ready for action!");
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

  for (int i = 0; i < 10; i++) {
    sfx.volUp();
  }

  sfx.playTrack(10);
}

void waitForSound() {
  uint32_t current, total;
  while (sfx.trackTime(&current, &total)) {
    delay(1000);
  }
}

void resetDoorPosition() {
  Serial.println("Resetting door position");
  TelnetStream.println("Resetting door position");
  openDoor();
  delay(DOOR_MOVE_TIME_MS);
  stopDoor();
  delay(1000);
  closeDoor();
  delay(DOOR_MOVE_TIME_MS);
  delay(DOOR_MOVE_TIME_MS);
  stopDoor();
  Serial.println("Door is closed");
  TelnetStream.println("Door is closed");
}

void loop() {
  ArduinoOTA.handle();
  
  pirValue = digitalRead(PIR_SENSOR_PIN);
  if (pirValue == HIGH) {
    Serial.println("Motion detected.");
    TelnetStream.println("Motion detected.");

    sfx.playTrack(8);
    delay(1000);
    openDoor();
    delay(500);
    sfx.playTrack(9);
    lightsOn();
    delay(2000);
    sfx.playTrack(10);
    delay(3000);
    stopDoor();
    delay(1000);
    
    long trackNumber = random(0, 3);
    Serial.print("Playing");
    Serial.println(trackNumber);
    TelnetStream.print("Playing");
    TelnetStream.println(trackNumber);

    sfx.playTrack(trackNumber);
    waitForSound();

    delay(7000);

    lightsOff();
    closeDoor();
    delay(DOOR_MOVE_TIME_MS);
    stopDoor();

    TelnetStream.println("Delaying next trigger");

    nextTriggerDelay();

    TelnetStream.println("Ready for motion");
  }
   
  delay(100);
}

void nextTriggerDelay() {
  for (int i = 1; i < 30; i++) {
    delay(1000);
    ArduinoOTA.handle();
  }
}

void openDoor() {
  Serial.println("Opening door");
  digitalWrite(DOOR_RELAY1_PIN, HIGH);  
  digitalWrite(DOOR_RELAY2_PIN, LOW);
}

void closeDoor() {
  Serial.println("Closing door");
  digitalWrite(DOOR_RELAY1_PIN, LOW);  
  digitalWrite(DOOR_RELAY2_PIN, HIGH);
}

void stopDoor() {
  Serial.println("Stopping door");
  digitalWrite(DOOR_RELAY1_PIN, HIGH);  
  digitalWrite(DOOR_RELAY2_PIN, HIGH);
}

void lightsOn() {
  Serial.println("Lights on");
  digitalWrite(LIGHTS_RELAY_PIN, HIGH);
}

void lightsOff() {
  Serial.println("Lights off");
  digitalWrite(LIGHTS_RELAY_PIN, LOW);
}
