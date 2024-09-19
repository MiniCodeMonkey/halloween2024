#!/bin/bash
FOLDER=./
MINWAIT=10
MAXWAIT=30

while :
do
    FILENAME=$(find $FOLDER -type f -name '*.mp4' | shuf -n 1)
    echo $FILENAME

    vlc --no-video-title -f $FILENAME vlc://quit
    DURATION=$((MINWAIT+RANDOM % (MAXWAIT-MINWAIT)))

    echo "Sleeping for $DURATION seconds"
    sleep $DURATION
done

