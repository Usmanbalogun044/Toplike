# Simple MVP Dockerfile: single container running Laravel via PHP built-in server
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions commonly used by Laravel
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql \
       bcmath \
       intl \
       zip \
       exif \
       pcntl \
       gd \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/start-app.sh
RUN chmod +x /usr/local/bin/start-app.sh

EXPOSE 8000
CMD ["/usr/local/bin/start-app.sh"]
