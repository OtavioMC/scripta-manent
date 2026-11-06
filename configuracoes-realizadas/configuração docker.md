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

5. No alvo `dev` do `Dockerfile` liberei a pasta do projeto pro Git de dentro do contêiner

   ```dockerfile
   RUN apk add --no-cache git \
       && git config --system --add safe.directory /app \
       && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
   ```

   ```bash
   docker compose build php
   ```

6. Separei a parte do nginx que vale pra desenvolvimento e produção em `docker/nginx/comum.conf`, já com os cabeçalhos de segurança

   ```nginx
   listen 80;
   root /app/public;
   server_tokens off;

   add_header X-Content-Type-Options "nosniff" always;
   add_header X-Frame-Options "DENY" always;
   add_header Referrer-Policy "same-origin" always;
   add_header Content-Security-Policy "default-src 'self'; frame-ancestors 'none'" always;
   add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

   location / {
       try_files $uri /index.php$is_args$args;
   }

   location = /index.php {
       fastcgi_pass php:9000;
       include fastcgi_params;
       fastcgi_param SCRIPT_FILENAME /app/public/index.php;
       fastcgi_hide_header X-Powered-By;
   }

   location ~ \.php$ {
       return 404;
   }
   ```

   O `docker/nginx/site.conf` ficou só com

   ```nginx
   server {
       include /etc/nginx/comum.conf;
   }
   ```

   e no `compose.yaml` montei o arquivo novo no serviço `web`

   ```yaml
   - ./docker/nginx/comum.conf:/etc/nginx/comum.conf:ro
   ```

   ```bash
   docker compose up -d --force-recreate web
   ```

7. Acrescentei no `Dockerfile` os alvos de produção, `php` e `web`

   ```dockerfile
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
   COPY src/ src/
   USER www-data

   FROM nginx:1.30.6-alpine AS web
   COPY docker/nginx/comum.conf /etc/nginx/comum.conf
   COPY docker/nginx/producao.conf /etc/nginx/conf.d/default.conf
   COPY --chmod=755 docker/nginx/htpasswd.sh /docker-entrypoint.d/40-htpasswd.sh
   COPY public/ /app/public/
   ```

   Criei o `docker/nginx/producao.conf`, com senha em tudo menos no `/versao`

   ```nginx
   map $request_uri $area_protegida {
       ~^/versao(\?|$) off;
       default "Scripta Manent";
   }

   server {
       include /etc/nginx/comum.conf;

       auth_basic $area_protegida;
       auth_basic_user_file /etc/nginx/.htpasswd;
   }
   ```

   Criei o `docker/nginx/htpasswd.sh`, que grava a senha do `.env` quando o nginx sobe

   ```sh
   #!/bin/sh
   set -eu

   if [ -z "${NGINX_HTPASSWD:-}" ]; then
       echo "NGINX_HTPASSWD está vazio. Gere a senha com htpasswd e coloque no .env antes de subir o site." >&2
       exit 1
   fi

   printf '%s\n' "$NGINX_HTPASSWD" > /etc/nginx/.htpasswd
   chown root:nginx /etc/nginx/.htpasswd
   chmod 640 /etc/nginx/.htpasswd
   ```

   E o `.dockerignore`

   ```
   .git
   .env
   vendor
   .phpunit.cache
   .phpstan.cache
   .deptrac.cache
   ```

   Pra testar as imagens

   ```bash
   docker build --target php --build-arg VERSAO=abc1234 -t sm-php:teste .
   docker build --target web -t sm-web:teste .
   ```

8. Passei a servir o Bootstrap pelo nginx, direto da pasta do Composer

   No `docker/nginx/comum.conf`, a CSP passou a aceitar imagens `data:` (usadas pelos ícones do CSS do Bootstrap) e entraram duas regras antes do `location /`

   ```nginx
   add_header Content-Security-Policy "default-src 'self'; img-src 'self' data:; frame-ancestors 'none'" always;

   location /assets/bootstrap/ {
       alias /app/bootstrap/;
   }

   location /assets/ {
       try_files $uri =404;
   }
   ```

   No `compose.yaml`, montei o Bootstrap no serviço `web`

   ```yaml
   - ./vendor/twbs/bootstrap/dist:/app/bootstrap:ro
   ```

   No `Dockerfile`, a imagem `web` copia o Bootstrap das dependências e a imagem `php` passou a levar os templates

   ```dockerfile
   COPY --from=dependencias /app/vendor/twbs/bootstrap/dist/ /app/bootstrap/
   ```

   ```dockerfile
   COPY templates/ templates/
   ```

   ```bash
   docker compose up -d --force-recreate web
   ```

9. Adicionei o Gotenberg, que gera o PDF do relatório, no `compose.yaml` e no `compose.prod.yaml`, só na rede interna

   ```yaml
   gotenberg:
     image: gotenberg/gotenberg:8.38.1
     command:
       - gotenberg
       - --api-timeout=20s
       - --chromium-disable-javascript=true
       - --chromium-allow-list=^file:///tmp/.*
   ```

   No serviço `php`, a variável que liga a geração de PDF e a dependência do contêiner novo

   ```yaml
   GOTENBERG_URL: http://gotenberg:3000
   ```

   ```bash
   docker compose up -d
   ```

10. Tirei a senha do nginx, que passou para a tela de login da aplicação

   Apaguei o `docker/nginx/htpasswd.sh` e a linha que copiava ele no `Dockerfile`. O `docker/nginx/producao.conf` ficou só com o limite de tentativas no `/login`, pelo IP real que a Cloudflare manda

   ```nginx
   limit_req_zone $http_cf_connecting_ip zone=login:10m rate=10r/m;

   server {
       include /etc/nginx/comum.conf;

       location = /login {
           limit_req zone=login burst=5 nodelay;
           limit_req_status 429;
           fastcgi_pass php:9000;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME /app/public/index.php;
           fastcgi_hide_header X-Powered-By;
       }
   }
   ```

   No `compose.yaml` e no `compose.prod.yaml`, a variável `NGINX_HTPASSWD` saiu do serviço `web` e foi para o `php`

   ```yaml
   NGINX_HTPASSWD: ${NGINX_HTPASSWD:?defina NGINX_HTPASSWD no .env}
   ```

11. Adicionei o Redis, que guarda o cache das listas e do relatório, no `compose.yaml` e no `compose.prod.yaml`, só na rede interna

   ```yaml
   redis:
     image: redis:8.10.1-alpine
     command:
       - redis-server
       - --save
       - ""
       - --appendonly
       - "no"
       - --maxmemory
       - 64mb
       - --maxmemory-policy
       - volatile-lru
   ```

   Sem persistência porque nada ali é dado definitivo. O `volatile-lru` só descarta chaves com prazo, então as versões das coleções nunca são perdidas por falta de memória.

   No serviço `php`, a variável que liga o cache

   ```yaml
   REDIS_URL: tcp://redis:6379
   ```

   ```bash
   docker compose up -d
   ```
