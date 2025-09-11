web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
worker: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
