#!/bin/bash

SOURCE_DIR="./"
DESTINATION="pi@puking-skeleton.local:/home/pi/app"

sync_and_restart() {
    rsync -avz --delete -e ssh "$SOURCE_DIR/" "$DESTINATION"
    ssh -n pi@puking-skeleton.local "sudo systemctl restart puke.service"
}

sync_and_restart

fswatch -o "$SOURCE_DIR" | while read f; do
    sync_and_restart
done
