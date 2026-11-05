FROM composer:2.11.1 AS composer

FROM php:8.4.27-fpm-alpine3.24 AS base
WORKDIR /app

FROM base AS dev
RUN apk add --no-cache git \
    && git config --system --add safe.directory /app \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=composer /usr/bin/composer /usr/bin/composer
