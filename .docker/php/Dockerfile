# syntax=docker/dockerfile:1
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxslt1-dev \
    libxml2-dev \
    curl \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel and this app
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        exif \
        pcntl \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Base PHP settings
COPY .docker/php/conf/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Use the www-data user for file permissions compatibility
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
USER www-data

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
