#!/bin/sh
set -e

cd /var/www/html

# Ensure code is present (helpful if bind mount isn't available yet)
if [ ! -f artisan ]; then
  echo "artisan not found in /var/www/html. Waiting for volume..."
  tries=0
  while [ $tries -lt 20 ] && [ ! -f artisan ]; do
    tries=$((tries+1))
    sleep 0.5
  done
fi

if [ ! -f artisan ]; then
  echo "ERROR: Could not find 'artisan' in /var/www/html."
  echo "Hint: Ensure the project is bind-mounted (docker compose) and file sharing is enabled for your drive."
  ls -la
  exit 1
fi

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
