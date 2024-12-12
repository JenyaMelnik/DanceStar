#!/bin/bash

echo "Creating database laravel..."
./vendor/bin/sail exec mysql mysql -usail -ppassword -e "CREATE DATABASE IF NOT EXISTS laravel;"
echo "Database laravel created (or already exists)."
