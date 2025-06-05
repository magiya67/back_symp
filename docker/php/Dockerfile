FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/project

RUN chown -R www-data:www-data /var/www/project 