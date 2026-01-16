#!/bin/sh
set -e

cd /var/www/html

# Wait for DB if configured
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
  echo "Waiting for DB at $DB_HOST:$DB_PORT..."
  until nc -z "$DB_HOST" "$DB_PORT"; do
    sleep 1
  done
fi

# Install dependencies if missing
if [ ! -d vendor ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Ensure APP_KEY exists
php artisan key:generate --force || true

# Link storage if needed
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Run pending migrations (safe for dev/test)
php artisan migrate --force || true

# Serve the application (built-in PHP server)
exec php artisan serve --host=0.0.0.0 --port=8000
