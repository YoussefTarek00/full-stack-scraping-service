FROM php:8.4-cli-alpine AS base

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev sqlite-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring bcmath zip intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS test

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize && touch .env

CMD ["php", "artisan", "test"]

FROM base AS runtime

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
