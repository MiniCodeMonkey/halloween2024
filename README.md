# Halloween 2024
3D Printing Models & Arduino code for various props

## Overview

### mirror

Magic mirror that greets you as you enter the haunted forest.

* avatar: The web-based 3D rendered avatar using AWS Polly for TTS (Based on AWS Sumerian Hosts)

### toilet

The skeleton is sitting on the toilet. Do not disturb.

Arduino code to control door actuator, lights and sounds. Triggered by PIR (motion) sensor

### tripwire

ESP32-based device with an attached motion sensor that sends motion events to a local MQTT server

### sneezer

ESP32-based motion activated device that will play a sneeze sound and activate a sprinkler

### coffin

ESP32-based motion activated coffin with motor for skeleton hand waving and sounds

### fortune-teller

Rasperry Pi based device. Uses microphone and speaker connection.

* Receives question via microphone
* Uses speech to text API to transcribe audio
* Uses Anthropic's Claude API to come up with a response
* Uses text to speech API to output response via speaker
* Controls few lights via relays
