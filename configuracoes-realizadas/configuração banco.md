# Configuração banco

1. Acrescentei as senhas do banco no `.env.exemplo`

   ```
   BANCO_SENHA_ROOT=
   BANCO_SENHA=
   TUNNEL_TOKEN=
   NGINX_HTPASSWD=
   ```

2. Criei o `bin/gerar-env`, que cria o `.env` com senhas aleatórias se ele ainda não existir

   ```sh
   #!/bin/sh
   set -eu

   cd "$(dirname "$0")/.."

   if [ -f .env ]; then
       echo "O .env já existe e não foi alterado."
       exit 0
   fi

   senha() {
       LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 32
   }

   sed -e "s/^BANCO_SENHA_ROOT=$/BANCO_SENHA_ROOT=$(senha)/" \
       -e "s/^BANCO_SENHA=$/BANCO_SENHA=$(senha)/" \
       .env.exemplo > .env
   chmod 600 .env

   echo ".env criado com senhas aleatórias para o banco."
   ```

3. Criei o `banco/000-usuario-aplicacao.sh`, que o MySQL roda na primeira subida e cria o usuário da aplicação sem permissão de criar ou alterar tabelas

   ```sh
   #!/bin/sh

   if [ -z "${BANCO_SENHA:-}" ]; then
       echo "BANCO_SENHA está vazio. O usuário da aplicação não foi criado." >&2
       exit 1
   fi

   mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
   CREATE USER 'aplicacao'@'%' IDENTIFIED BY '$BANCO_SENHA';
   GRANT SELECT, INSERT, UPDATE, DELETE ON scripta_manent.* TO 'aplicacao'@'%';
   SQL
   ```

4. No `Dockerfile`, o estágio `base` ganhou o driver do MySQL

   ```dockerfile
   RUN docker-php-ext-install pdo_mysql
   ```

5. No `compose.yaml` acrescentei o MySQL e passei os dados de conexão pro `php`

   ```yaml
   php:
     environment:
       BANCO_HOST: mysql
       BANCO_NOME: scripta_manent
       BANCO_USUARIO: aplicacao
       BANCO_SENHA: ${BANCO_SENHA:?rode sh bin/gerar-env para criar o .env}
     depends_on:
       mysql:
         condition: service_healthy

   mysql:
     image: mysql:8.4.13
     environment:
       MYSQL_ROOT_PASSWORD: ${BANCO_SENHA_ROOT:?rode sh bin/gerar-env para criar o .env}
       MYSQL_DATABASE: scripta_manent
       BANCO_SENHA: ${BANCO_SENHA:?rode sh bin/gerar-env para criar o .env}
     volumes:
       - mysql:/var/lib/mysql
       - ./banco:/docker-entrypoint-initdb.d:ro
     healthcheck:
       test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "--silent"]
       interval: 2s
       timeout: 5s
       retries: 60

   volumes:
     mysql:
   ```

6. No pipeline, o job `ci` roda `sh bin/gerar-env` antes do `docker compose build php`

7. Gerei o `.env` e subi tudo

   ```bash
   sh bin/gerar-env
   docker compose up -d --build
   ```

   Os scripts da pasta `banco/` só rodam com o volume vazio. Pra recriar o banco do zero

   ```bash
   docker compose down -v
   docker compose up -d
   ```
