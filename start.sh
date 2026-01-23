#!/bin/bash

# Start services
docker compose up -d --build

# Wait for database to be ready
echo "Waiting for database..."
sleep 5

# Install dependencies
docker compose exec app composer install

# Run migrations
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

echo "Application started at http://localhost:${APP_PORT:-8080}"
