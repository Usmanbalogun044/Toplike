#!/bin/sh
set -e

# Initialize storage directory if empty
# -----------------------------------------------------------
# If the storage directory is empty, copy the initial contents
# and set the correct permissions.
# -----------------------------------------------------------
mkdir -p /var/www/storage
if [ -z "$(ls -A /var/www/storage 2>/dev/null)" ]; then
  echo "Initializing storage directory..."
  if [ -d "/var/www/storage-init" ]; then
    cp -R /var/www/storage-init/. /var/www/storage
  else
    echo "Warning: /var/www/storage-init not found; skipping initial storage copy"
  fi
  chown -R www-data:www-data /var/www/storage || true
fi

# Remove storage-init directory if present
[ -d "/var/www/storage-init" ] && rm -rf /var/www/storage-init

# Run Laravel migrations
# -----------------------------------------------------------
# Ensure the database schema is up to date.
# -----------------------------------------------------------
php artisan migrate --force

# Clear and cache configurations
# -----------------------------------------------------------
# Improves performance by caching config and routes.
# -----------------------------------------------------------
php artisan config:cache
php artisan route:cache

# Run the default command
exec "$@"
