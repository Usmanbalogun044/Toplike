# Single-container production image for Render: Nginx + PHP-FPM via Supervisor
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    curl \
    nginx \
    supervisor \
    gettext-base \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql \
       pdo_pgsql \
       bcmath \
       intl \
       zip \
       exif \
       pcntl \
       gd \
       opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Install PHP dependencies (no dev) and warm caches
RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache || true

# Nginx config template (uses $PORT)
COPY docker/production/nginx.conf.template /etc/nginx/templates/default.conf.template

# Supervisor config
COPY docker/production/supervisord.conf /etc/supervisor/supervisord.conf

# Entrypoint
COPY docker/production/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV PORT=80
EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --retries=5 CMD curl -fsS http://127.0.0.1:${PORT}/ || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
