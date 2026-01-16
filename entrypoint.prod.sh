#!/bin/sh
set -e

cd /var/www/html

# Ensure APP_KEY
php artisan key:generate --force || true

# Storage symlink
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Run migrations if enabled
if [ "$MIGRATE_ON_START" = "true" ]; then
  php artisan migrate --force || true
fi

# Start services via Supervisor
exec "$@"
