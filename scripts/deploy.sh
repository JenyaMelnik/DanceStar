#!/bin/bash

echo "Starting deployment process..."
./scripts/copy-env.sh
docker compose up -d
echo "Installing dependencies..."
docker exec -it dancestar-laravel.test-1 composer install
docker compose down
./vendor/bin/sail up -d
./vendor/bin/sail npm install
echo "Generating application key..."
./vendor/bin/sail artisan key:generate
echo "Creating database..."
./scripts/create-database.sh
echo "Running migrations..."
./vendor/bin/sail artisan migrate --force
echo "Deployment complete!"
