##################################
# Stage 1: Composer dependencies #
##################################
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files and install dependencies
# Ignore platform requirements since this is just for dependency resolution
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs

# Copy the full Laravel application
COPY . .

##################################
# Stage 2: Frontend build        #
##################################
FROM node:20-alpine AS frontend

WORKDIR /app

# Install Node dependencies
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile

# Copy source files and vendor (for Ziggy)
COPY . .
COPY --from=composer /app/vendor ./vendor

# Build frontend assets
RUN yarn build

##################################
# Stage 3: PHP FPM Runtime Image #
##################################
FROM php:8.3-fpm-alpine

LABEL maintainer="Robert Hartles"

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache --virtual .build-deps \
      autoconf g++ make zlib-dev libpng-dev libjpeg-turbo-dev \
      freetype-dev postgresql-dev libzip-dev oniguruma-dev \
      libxml2-dev openssl-dev icu-dev \
    && apk add --no-cache \
      curl git libpng libjpeg-turbo freetype libzip \
      oniguruma icu-libs postgresql-libs zlib mariadb-client \
      libpq libxml2 openssl supervisor netcat-openbsd \
      python3 py3-pip shadow \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd pdo_mysql pdo_pgsql zip intl mbstring pcntl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache .build-deps

# Copy PHP config
COPY docker/config/php-minimal.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/config/php-fpm-minimal.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Copy built Laravel app from composer stage
COPY --from=composer /app /var/www/html

# Copy built frontend assets from frontend stage (overwrite public dir)
COPY --from=frontend /app/public /var/www/html/public

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Laravel cron setup
RUN mkdir -p /var/log/cron \
    && touch /var/log/cron/cron.log \
    && chown -R www-data:www-data /var/log/cron \
    && echo '* * * * * su -s /bin/sh www-data -c "cd /var/www/html && php artisan schedule:run >> /var/log/cron/cron.log 2>&1"' > /etc/crontabs/root

# Supervisor setup
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
COPY docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose PHP-FPM port
EXPOSE 9000

# Default command: PHP-FPM
CMD ["php-fpm"]
