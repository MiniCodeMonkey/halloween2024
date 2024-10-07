#!/bin/bash

SOURCE_DIR="./"
DESTINATION="pi@fog-projector.local:/home/pi/app"

sync_and_restart() {
    rsync -avz --delete -e ssh "$SOURCE_DIR/" "$DESTINATION"
    ssh -n pi@fog-projector.local "sudo systemctl restart projector.service"
}

sync_and_restart

fswatch -o "$SOURCE_DIR" | while read f; do
    sync_and_restart
done
