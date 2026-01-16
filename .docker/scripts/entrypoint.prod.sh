#!/bin/sh
set -e

cd /var/www/html

# Optional: run migrations on start when enabled
if [ "$MIGRATE_ON_START" = "true" ]; then
  php artisan migrate --force || true
fi

# Warm caches (idempotent)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
