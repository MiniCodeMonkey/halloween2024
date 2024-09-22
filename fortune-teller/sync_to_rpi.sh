#!/bin/bash

SOURCE_DIR="./"
DESTINATION="pi@fortune-teller.local:/home/pi/app"

perform_sync() {
    rsync -avz --delete -e ssh "$SOURCE_DIR/" "$DESTINATION"
}

perform_sync

fswatch -o "$SOURCE_DIR" | while read f; do
    perform_sync
    ssh -n pi@fortune-teller.local "sudo systemctl restart fortune-teller.service"
done
