#!/bin/bash

SOURCE_DIR="./"
DESTINATION="pi@fortune-teller.local:/home/pi/app"

sync_and_restart() {
    rsync -avz --delete -e ssh "$SOURCE_DIR/" "$DESTINATION"
    ssh -n pi@fortune-teller.local "sudo systemctl restart fortune-teller.service"
}

sync_and_restart

fswatch -o "$SOURCE_DIR" | while read f; do
    sync_and_restart
done
