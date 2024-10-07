#include <WiFi.h>
#include <ArduinoOTA.h>
#include <TelnetStream.h>

#define PIR_SENSOR_PIN 21
#define RELAY_FANS_PIN 12
#define RELAY_FOG_PIN 27

#define DEVICE_NAME "fog-screen"
#define WIFI_CONNECT_TIMEOUT_MS 30000

#define PROJECTOR_IP_ADDRESS "192.168.2.247"
#define PROJECTOR_PORT 1337

int pirValue;

void setup() {  
  pinMode(RELAY_FANS_PIN, OUTPUT);
  digitalWrite(RELAY_FANS_PIN, LOW);

  pinMode(RELAY_FOG_PIN, OUTPUT);
  digitalWrite(RELAY_FOG_PIN, LOW);

  pinMode(PIR_SENSOR_PIN, INPUT);
  
  Serial.begin(115200);
  while (!Serial);

  initWiFi();
}

void initWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin("Halloween", "veryspooky");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(WiFi.status());
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
  
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());

    Serial.print("RRSI: ");
    Serial.println(WiFi.RSSI());

    TelnetStream.begin();
  }
}

void loop() {
  ArduinoOTA.handle();
  
  pirValue = digitalRead(PIR_SENSOR_PIN);
  if (pirValue == HIGH) {
    Serial.println("Motion detected.");
    TelnetStream.println("Motion detected.");

    digitalWrite(RELAY_FANS_PIN, HIGH);
    delay(2000);
    digitalWrite(RELAY_FOG_PIN, HIGH);
    delay(2000);
    digitalWrite(RELAY_FOG_PIN, LOW);

    notifyProjector();

    delay(2000);
    digitalWrite(RELAY_FANS_PIN, LOW);

    ArduinoOTA.handle();

    delay(30000);
  }
   
  delay(100);
}

void notifyProjector() {
  NetworkClient client;

  if (client.connect(PROJECTOR_IP_ADDRESS, PROJECTOR_PORT)) {
    Serial.println("Connected to TCP server");
    client.print("Hello!");
  } else {
    Serial.println("Failed to connect to TCP server");
  }
}
