# Stage 1: Build Assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Production Runtime
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
  libsqlite3-dev \
  curl \
  nginx \
  supervisor \
  gettext-base \
  && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure intl \
  && docker-php-ext-install -j"$(nproc)" \
  pdo_mysql \
  pdo_pgsql \
  pdo_sqlite \
  bcmath \
  intl \
  zip \
  exif \
  pcntl \
  gd \
  opcache

# Configure Opcache for Production
RUN { \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.interned_strings_buffer=16'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.save_comments=1'; \
  echo 'opcache.fast_shutdown=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Copy compiled assets from frontend stage
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP dependencies (no dev) and optimize
RUN composer install --no-dev --optimize-autoloader --no-interaction \
  && php artisan config:cache \
  && php artisan route:cache \
  && php artisan view:cache || true

# Nginx config template
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
