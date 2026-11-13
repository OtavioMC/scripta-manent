FROM composer:2.11.1 AS composer

FROM php:8.4.27-fpm-alpine3.24 AS base
WORKDIR /app
RUN docker-php-ext-install pdo_mysql

FROM base AS dev
RUN apk add --no-cache git \
    && apk add --no-cache --virtual .dependencias-do-xdebug $PHPIZE_DEPS linux-headers \
    && pecl install xdebug-3.5.3 \
    && docker-php-ext-enable xdebug \
    && apk del .dependencias-do-xdebug \
    && printf 'xdebug.mode = off\nxdebug.client_host = host.docker.internal\nxdebug.start_with_request = trigger\n' \
        > "$PHP_INI_DIR/conf.d/zz-xdebug.ini" \
    && git config --system --add safe.directory /app \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM composer AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist
COPY src/ src/
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

FROM base AS php
ARG VERSAO=dev
ENV VERSAO=$VERSAO
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && docker-php-ext-enable opcache \
    && printf '[www]\nenv[VERSAO] = $VERSAO\n' > /usr/local/etc/php-fpm.d/zz-ambiente.conf
COPY --from=dependencias /app/vendor/ vendor/
COPY config/ config/
COPY public/ public/
COPY templates/ templates/
COPY banco/003-dados-exemplo.sql banco/
COPY src/ src/
USER www-data

FROM nginx:1.30.6-alpine AS web
COPY docker/nginx/comum.conf /etc/nginx/comum.conf
COPY docker/nginx/producao.conf /etc/nginx/conf.d/default.conf
COPY --from=dependencias /app/vendor/twbs/bootstrap/dist/ /app/bootstrap/
COPY public/ /app/public/
