#!/bin/sh
set -e

# Render Nginx config with PORT env variable
if [ -z "$PORT" ]; then
  export PORT=80
fi
mkdir -p /etc/nginx/conf.d
envsubst < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

cd /var/www/html

# Ensure Composer deps are present
if [ ! -d vendor ]; then
  echo "Installing Composer dependencies..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

# Auto-create SQLite database file when using sqlite
if [ "$DB_CONNECTION" = "sqlite" ]; then
  DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
  DB_DIR=$(dirname "$DB_FILE")
  if [ ! -d "$DB_DIR" ]; then
    mkdir -p "$DB_DIR"
  fi
  if [ ! -f "$DB_FILE" ]; then
    echo "Initializing SQLite database at $DB_FILE"
    touch "$DB_FILE"
  fi
fi

# Ensure APP_KEY exists
php artisan key:generate --force || true

# Link storage if needed
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Migrations on start when enabled
if [ "$MIGRATE_ON_START" = "true" ]; then
  php artisan migrate --force || true
fi

# Warm caches
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
