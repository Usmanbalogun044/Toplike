#!/bin/sh
set -e

# Wait for DB if using MySQL/pgsql
if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
  echo "Waiting for MySQL at $DB_HOST:$DB_PORT..."
  until nc -z "$DB_HOST" "$DB_PORT"; do
    sleep 1
  done
fi
if [ "$DB_CONNECTION" = "pgsql" ]; then
  echo "Waiting for Postgres at $DB_HOST:$DB_PORT..."
  until nc -z "$DB_HOST" "$DB_PORT"; do
    sleep 1
  done
fi

cd /var/www/html

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

# Run pending migrations (safe for dev)
php artisan migrate --force || true

# Start PHP-FPM (container CMD will be php-fpm)
exec "$@"
