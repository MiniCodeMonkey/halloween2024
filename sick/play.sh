#!/bin/bash

AUDIO_DIR="/home/pi/app/audio"

GPIO_CHIP="gpiochip0"
PUMP_RELAY_PIN=2

control_pump() {
    if [ "$1" = "on" ]; then
        gpioset $GPIO_CHIP $PUMP_RELAY_PIN=1
    else
        gpioset $GPIO_CHIP $PUMP_RELAY_PIN=0
    fi
}

setup() {  
  gpio -g mode $RELAY_PIN out
  control_pump off
}

playSound() {
  audio_files=("$AUDIO_DIR"/*.wav)
  random_file=${audio_files[$RANDOM % ${#audio_files[@]}]}
  aplay "$random_file"
}

while true; do
  playSound

  sleep 1

  control_pump on
  sleep 30
  control_pump off

  sleep 30
done