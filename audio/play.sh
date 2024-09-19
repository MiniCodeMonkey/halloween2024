#!/bin/bash
FOLDER=./witch
MINWAIT=10
MAXWAIT=30

amixer set Master 90%

while :
do
    FILENAME=$(find $FOLDER -type f -name '*.wav' | shuf -n 1)
    echo $FILENAME

    aplay $FILENAME
    DURATION=$((MINWAIT+RANDOM % (MAXWAIT-MINWAIT)))

    echo "Sleeping for $DURATION seconds"
    sleep $DURATION
done
