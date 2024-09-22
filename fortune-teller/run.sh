#!/bin/bash

BASE_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

while true; do
    /usr/bin/php "$BASE_DIR"/artisan app:server
    echo "App shut down or crashed, restarting..."
    sleep 1
done
