# Configuração Docker

1. Criei o `Dockerfile`

   ```dockerfile
   FROM composer:2.11.1 AS composer

   FROM php:8.4.27-fpm-alpine3.24 AS base
   WORKDIR /app

   FROM base AS dev
   RUN apk add --no-cache git \
       && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
   COPY --from=composer /usr/bin/composer /usr/bin/composer
   ```

2. Criei o `compose.yaml`

   ```yaml
   name: scripta-manent

   services:
     php:
       build:
         context: .
         target: dev
       volumes:
         - .:/app

     web:
       image: nginx:1.30.6-alpine
       ports:
         - "127.0.0.1:8080:80"
       volumes:
         - ./docker/nginx/site.conf:/etc/nginx/conf.d/default.conf:ro
         - ./public:/app/public:ro
       depends_on:
         - php
   ```

3. Criei o `docker/nginx/site.conf`

   ```nginx
   server {
       listen 80;
       root /app/public;

       location / {
           try_files $uri /index.php$is_args$args;
       }

       location = /index.php {
           fastcgi_pass php:9000;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME /app/public/index.php;
       }

       location ~ \.php$ {
           return 404;
       }
   }
   ```

4. Subi os contêineres e instalei as dependências

   ```bash
   docker compose up -d --build
   docker compose run --rm php composer install
   ```

   O site fica em `http://localhost:8080`.
