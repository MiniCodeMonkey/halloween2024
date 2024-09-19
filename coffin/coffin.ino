#include <WiFi.h>
#include <ESPmDNS.h>
#include <WiFiUdp.h>
#include <ArduinoOTA.h>
#include <Adafruit_MotorShield.h>
#include "Adafruit_Soundboard.h"

#define PROJECT_NAME "coffin"

#define DELAY_TIME_MIN 5
#define DELAY_TIME_MAX 20
#define TRACKS_COUNT   5

#define SOUNDFX_RESET_PIN 14

Adafruit_Soundboard sfx = Adafruit_Soundboard(&Serial1, NULL, SOUNDFX_RESET_PIN);

Adafruit_MotorShield AFMS = Adafruit_MotorShield();
Adafruit_StepperMotor *motor = AFMS.getStepper(200, 1);

int lastTrackNo = 0;

void setup() {
  Serial.begin(115200);
  while (!Serial);

  randomSeed(analogRead(0));

  initWifi();
  initBoards();
}

void initWifi() {
  WiFi.mode(WIFI_AP);
  if (!WiFi.softAP(PROJECT_NAME, "halloween")) {
    Serial.println("Creating access point failed");
    delay(5000);
    ESP.restart();
  }

  IPAddress Ip(192, 168, 1, 1);
  IPAddress NMask(255, 255, 255, 0);
  WiFi.softAPConfig(Ip, Ip, NMask);

  ArduinoOTA.setHostname(PROJECT_NAME);
  
  ArduinoOTA
    .onStart([]() {
      String type;
      if (ArduinoOTA.getCommand() == U_FLASH)
        type = "sketch";
      else // U_SPIFFS
        type = "filesystem";

      // NOTE: if updating SPIFFS this would be the place to unmount SPIFFS using SPIFFS.end()
      Serial.println("Start updating " + type);
    })
    .onEnd([]() {
      Serial.println("\nEnd");
    })
    .onProgress([](unsigned int progress, unsigned int total) {
      Serial.printf("Progress: %u%%\r", (progress / (total / 100)));
    })
    .onError([](ota_error_t error) {
      Serial.printf("Error[%u]: ", error);
      if (error == OTA_AUTH_ERROR) Serial.println("Auth Failed");
      else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
      else if (error == OTA_CONNECT_ERROR) Serial.println("Connect Failed");
      else if (error == OTA_RECEIVE_ERROR) Serial.println("Receive Failed");
      else if (error == OTA_END_ERROR) Serial.println("End Failed");
    });

  ArduinoOTA.begin();

  Serial.println("Ready");
  Serial.print("IP address: ");
  Serial.println(WiFi.softAPIP());
}

void initBoards() {

  Serial.println("Connecting to Sound board");
  Serial1.begin(9600);
  
  if (!sfx.reset()) {
    Serial.println("Uh oh. Sound board not found");
    while (1);
  }
  
  Serial.println("Sound board found");
  for (int i = 0; i < 10; i++) {
    sfx.volUp();
  }

  if (!AFMS.begin()) {
    Serial.println("Could not find Motor Shield. Check wiring.");
    while (1);
  }
  Serial.println("Motor Shield found.");

  motor->setSpeed(50);
}

void loop() {
  ArduinoOTA.handle();
  
  perform();
  
  ArduinoOTA.handle();
  
  int delayTime = random(DELAY_TIME_MIN, DELAY_TIME_MAX);
  delaySeconds(delayTime);
}

void perform() {
  int trackNo = random(0, TRACKS_COUNT);

  if (trackNo == lastTrackNo) {
    perform();
  } else {
    // Reset hand position
    motor->step(300, BACKWARD, SINGLE);
      
    lastTrackNo = trackNo;
    if (!sfx.playTrack(trackNo)) {
      Serial.println("Failed to play track");
    }

    // Wave hand
    motor->step(50, FORWARD, MICROSTEP);
    delay(100);
    motor->step(50, BACKWARD, MICROSTEP);
  }
}

void waitForSound() {
  uint32_t current, total;
  while (sfx.trackTime(&current, &total)) {
    delaySeconds(1);
  }
}

void delaySeconds(int seconds) {
  for (int i = 0; i < seconds; i++) {
    ArduinoOTA.handle();
    delay(1000);
  }
}
