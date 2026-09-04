# ---- Stage 1: install PHP dependencies with Composer ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

# ---- Stage 2: runtime image ----
FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev icu-libs oniguruma-dev \
    && docker-php-ext-install intl mbstring pdo_mysql mysqli

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN chown -R www-data:www-data writable

EXPOSE 9000
CMD ["php-fpm"]
