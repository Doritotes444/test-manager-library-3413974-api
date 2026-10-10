#!/bin/sh
set -e

echo "Waiting for database..."
while ! nc -z ${DB_HOST:-db} ${DB_PORT:-3306}; do
  sleep 1
done
echo "Database is ready."

php artisan migrate --force

echo "Starting PHP built-in server on 0.0.0.0:8000..."
exec php -S 0.0.0.0:8000 -t public