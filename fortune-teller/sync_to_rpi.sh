#!/bin/bash

SOURCE_DIR="./"
DESTINATION="pi@fortune-teller.local:/home/pi/app"

fswatch -o "$SOURCE_DIR" | while read f; do
    rsync -avz --delete -e ssh "$SOURCE_DIR/" "$DESTINATION"
    ssh -n pi@fortune-teller.local "sudo systemctl restart fortune-teller.service"
done
