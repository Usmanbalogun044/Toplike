#!/bin/sh
set -e

# Write Nginx conf with PORT env variable
if [ -z "$PORT" ]; then
  export PORT=8080
fi
mkdir -p /etc/nginx/conf.d
envsubst < /etc/nginx/templates/app.conf.template > /etc/nginx/conf.d/default.conf

cd /var/www/html

# Ensure Composer deps are present
if [ ! -d vendor ]; then
  echo "Installing Composer dependencies..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
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
