#!/bin/bash

echo "Setting locale to $1"
sed -i "s/APP_LOCALE.*/APP_LOCALE=$1/" .env

echo "Restarting service"
sudo systemctl restart fortune-teller.service
