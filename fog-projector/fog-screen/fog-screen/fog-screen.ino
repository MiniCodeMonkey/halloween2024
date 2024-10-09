#include <WiFi.h>
#include <WiFiMulti.h>
#include <ArduinoOTA.h>
#include <TelnetStream.h>

#define PIR_SENSOR_PIN 21
#define RELAY_FANS_PIN 18
#define RELAY_FOG_PIN 19
#define INTERNAL_LED_PIN 13

#define DEVICE_NAME "fog-screen"
#define WIFI_CONNECT_TIMEOUT_MS 30000

#define PROJECTOR_IP_ADDRESS "192.168.2.247"
#define PROJECTOR_PORT 1337

WiFiMulti WiFiMulti;

int pirValue;
int state = HIGH;

void setup() {  
  pinMode(RELAY_FANS_PIN, OUTPUT);
  digitalWrite(RELAY_FANS_PIN, HIGH);

  pinMode(RELAY_FOG_PIN, OUTPUT);
  digitalWrite(RELAY_FOG_PIN, HIGH);

  pinMode(INTERNAL_LED_PIN, OUTPUT);
  digitalWrite(INTERNAL_LED_PIN, LOW);

  pinMode(PIR_SENSOR_PIN, INPUT);
  
  Serial.begin(115200);
  while (!Serial);

  initWiFi();
}

void initWiFi() {
  WiFiMulti.addAP("Halloween", "veryspooky");

  while (WiFiMulti.run() != WL_CONNECTED) {
    Serial.println(".");
    delay(1000);

    if (millis() > WIFI_CONNECT_TIMEOUT_MS) {
      Serial.println("Giving up. Continuing without WiFi");
      break;
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    ArduinoOTA.setHostname(DEVICE_NAME);
    ArduinoOTA.begin();

    TelnetStream.begin();
  
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    TelnetStream.println(WiFi.localIP());

    Serial.print("RRSI: ");
    Serial.println(WiFi.RSSI());
    TelnetStream.println(WiFi.RSSI());
  }
}

void loop() {
  ArduinoOTA.handle();
  
  pirValue = digitalRead(PIR_SENSOR_PIN);
  if (pirValue == HIGH) {
    digitalWrite(INTERNAL_LED_PIN, HIGH);
    Serial.println("Motion detected.");
    TelnetStream.println("Motion detected.");

    notifyProjector();

    digitalWrite(RELAY_FANS_PIN, LOW);
    delay(2000); // Run fans for 2s before starting fog machine

    digitalWrite(RELAY_FOG_PIN, LOW);
    delay(5000);
    digitalWrite(RELAY_FOG_PIN, HIGH);

    // Keep running fans for another 30s
    for (int i = 0; i < 300; i++) {
      ArduinoOTA.handle();
      delay(100);
    }

    digitalWrite(RELAY_FANS_PIN, HIGH);

    // Pause for 30s before waiting for motion again
    for (int i = 0; i < 300; i++) {
      ArduinoOTA.handle();
      delay(100);

      if (state == LOW) {
        state = HIGH;
      } else {
        state = LOW;
      }

      digitalWrite(INTERNAL_LED_PIN, state);
    }
    digitalWrite(INTERNAL_LED_PIN, LOW);
  }
   
  delay(100);
}

void notifyProjector() {
  NetworkClient client;
  client.setTimeout(5000);

  if (client.connect(PROJECTOR_IP_ADDRESS, PROJECTOR_PORT)) {
    Serial.println("Connected to TCP server");
    TelnetStream.println("Sent TCP trigger");
    client.print("trigger");
    client.stop();
  } else {
    Serial.println("Failed to connect to TCP server");
    TelnetStream.println("Failed to send TCP trigger");
  }
}
