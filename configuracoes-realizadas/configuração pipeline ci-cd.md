# Configuração pipeline CI/CD

## GitHub

1. Em Settings > Environments criei o ambiente `production`, com Required reviewers marcado e eu como revisor, Prevent self-review desmarcado e Deployment branches em Selected branches, só a `main`.
2. Em Settings > Actions > General, em Workflow permissions, escolhi Read repository contents and packages permissions e deixei desmarcado o Allow GitHub Actions to create and approve pull requests.

## Arquivos

1. `.github/workflows/pipeline.yml`. Os comandos do CI são scripts do `composer.json` rodando no contêiner `php` do `compose.yaml`. As actions ficam travadas pelo hash do commit.

   ```yaml
   name: pipeline

   on:
     push:
       branches: ['**']
     pull_request:

   permissions:
     contents: read

   jobs:
     ci:
       runs-on: ubuntu-24.04
       steps:
         - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
         - run: sh bin/gerar-env
         - run: docker compose build php
         - run: docker compose run --rm -T php composer install --no-interaction --no-progress
         - run: docker compose run --rm -T php composer sintaxe
         - run: docker compose run --rm -T php composer estilo
         - run: docker compose run --rm -T php composer analise
         - run: docker compose run --rm -T php composer camadas
         - run: docker compose run --rm -T php composer testes
         - run: docker compose run --rm -T php composer audit

     publicar:
       needs: ci
       if: github.event_name == 'push' && github.ref == 'refs/heads/main'
       runs-on: ubuntu-24.04
       permissions:
         contents: read
         packages: write
       strategy:
         matrix:
           alvo: [php, web]
       steps:
         - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
         - uses: docker/setup-buildx-action@f87e5991a6d7451dcb8d9637bfbc97413f497069 # v4.4.1
         - uses: docker/login-action@8aa5831fb8d48d8f76594c7ddde61ae58019f550 # v4.8.0
           with:
             registry: ghcr.io
             username: ${{ github.actor }}
             password: ${{ secrets.GITHUB_TOKEN }}
         - uses: docker/build-push-action@c3c9e263c25d99ce0380d002d59b67737d91b0dc # v7.4.0
           with:
             context: .
             target: ${{ matrix.alvo }}
             push: true
             build-args: VERSAO=${{ github.sha }}
             labels: |
               org.opencontainers.image.source=${{ github.server_url }}/${{ github.repository }}
               org.opencontainers.image.revision=${{ github.sha }}
             tags: ghcr.io/otaviomc/scripta-manent-${{ matrix.alvo }}:${{ github.sha }}

     promover:
       needs: publicar
       runs-on: ubuntu-24.04
       environment: production
       concurrency: producao
       permissions:
         contents: read
         packages: write
       steps:
         - uses: docker/setup-buildx-action@f87e5991a6d7451dcb8d9637bfbc97413f497069 # v4.4.1
         - uses: docker/login-action@8aa5831fb8d48d8f76594c7ddde61ae58019f550 # v4.8.0
           with:
             registry: ghcr.io
             username: ${{ github.actor }}
             password: ${{ secrets.GITHUB_TOKEN }}
         - run: |
             for alvo in php web; do
               docker buildx imagetools create \
                 -t "ghcr.io/otaviomc/scripta-manent-$alvo:producao" \
                 "ghcr.io/otaviomc/scripta-manent-$alvo:$GITHUB_SHA"
             done

     conferir:
       needs: promover
       runs-on: ubuntu-24.04
       timeout-minutes: 11
       steps:
         - run: |
             for _ in $(seq 60); do
               if [ "$(curl -fsS https://scriptamanent.com.br/versao || true)" = "$GITHUB_SHA" ]; then
                 exit 0
               fi
               sleep 10
             done
             echo "Produção não chegou ao commit $GITHUB_SHA em 10 minutos."
             exit 1
   ```

2. `compose.prod.yaml`. Sem porta exposta por conta do cloudflare tunnel.

   ```yaml
   name: scriptamanent

   services:
     php:
       image: ghcr.io/otaviomc/scripta-manent-php:producao
       restart: unless-stopped
       mem_limit: 512m

     web:
       image: ghcr.io/otaviomc/scripta-manent-web:producao
       restart: unless-stopped
       environment:
         NGINX_HTPASSWD: ${NGINX_HTPASSWD:?defina NGINX_HTPASSWD no .env}
       depends_on: [php]
       mem_limit: 128m

     tunnel:
       image: cloudflare/cloudflared:2026.10.7
       command: tunnel run
       restart: unless-stopped
       environment:
         TUNNEL_TOKEN: ${TUNNEL_TOKEN:?defina TUNNEL_TOKEN no .env}
       depends_on: [web]
       mem_limit: 128m
   ```

3. `.env.exemplo`

   ```
   TUNNEL_TOKEN=
   NGINX_HTPASSWD=
   ```

4. `deploy/atualizar.sh`.

   ```bash
   #!/usr/bin/env bash
   set -euo pipefail

   readonly COMPOSE=(docker compose -f compose.prod.yaml)
   readonly IMAGEM=ghcr.io/otaviomc/scripta-manent

   precisa_atualizar() {
     local alvo em_uso
     for alvo in php web; do
       em_uso=$("${COMPOSE[@]}" ps -q "$alvo" | xargs -r docker inspect -f '{{.Image}}')
       if [ "$(docker image inspect -f '{{.Id}}' "$IMAGEM-$alvo:producao")" != "$em_uso" ]; then
         return 0
       fi
     done
     return 1
   }

   main() {
     "${COMPOSE[@]}" pull -q php web
     precisa_atualizar || return 0
     local revisao
     revisao=$(docker image inspect -f '{{index .Config.Labels "org.opencontainers.image.revision"}}' "$IMAGEM-php:producao")
     git fetch -q origin
     if ! git cat-file -e "$revisao:compose.prod.yaml" 2>/dev/null; then
       echo "O commit $revisao não tem compose.prod.yaml. A produção continua no commit atual." >&2
       return 1
     fi
     echo "Atualizando para o commit $revisao"
     git checkout -q "$revisao"
     "${COMPOSE[@]}" up -d --remove-orphans
     docker image prune -f
     echo "Produção no commit $revisao"
   }

   main; exit
   ```

5. `deploy/scriptamanent-atualizar.service`

   ```ini
   [Unit]
   Description=Atualiza o scriptamanent quando a imagem producao muda
   Wants=network-online.target
   After=network-online.target docker.service

   [Service]
   Type=oneshot
   User=deploy
   WorkingDirectory=/home/deploy/scripta-manent
   ExecStart=/usr/bin/bash deploy/atualizar.sh
   ```

6. `deploy/scriptamanent-atualizar.timer`

   ```ini
   [Unit]
   Description=Confere a imagem producao a cada minuto

   [Timer]
   OnCalendar=minutely

   [Install]
   WantedBy=timers.target
   ```

## Pipeline

- Todo push e pull request roda o job `ci`.
- Push na `main` com o `ci` verde publica as imagens `php` e `web` com o hash do commit e para esperando aprovação.
- Pra aprovar: Actions => execução do pipeline => Review deployment => marcar `production`==> Approve and deploy.
- Depois da aprovação, `promover` marca as imagens como `producao` e `conferir` espera o hash novo aparecer em `/versao`.

## Na VPS

1. Clonei o repositório

   ```bash
   git clone https://github.com/OtavioMC/scripta-manent.git ~/scripta-manent
   cd ~/scripta-manent
   ```

2. Criei o `.env` a partir do exemplo, trazendo o token do túnel da pasta de teste

   ```bash
   grep -v '^TUNNEL_TOKEN=' .env.exemplo > .env
   cat ~/teste-tunel/.env >> .env
   chmod 600 .env
   rm -r ~/teste-tunel
   ```

3. Gerei a senha do site e colei a linha que sai no `NGINX_HTPASSWD` do `.env`, entre aspas simples

   ```bash
   docker run --rm -it httpd:2.4.69-alpine htpasswd -nB avaliador
   ```

4. Liguei o timer

   ```bash
   sudo cp deploy/scriptamanent-atualizar.service deploy/scriptamanent-atualizar.timer /etc/systemd/system/
   sudo systemctl daemon-reload
   sudo systemctl enable --now scriptamanent-atualizar.timer
   ```

   Pra conferir

   ```bash
   systemctl list-timers scriptamanent-atualizar.timer
   journalctl -u scriptamanent-atualizar.service -f
   ```

## Primeira publicação

1. Depois do primeiro push na `main`, antes de aprovar, entrei em Packages no GitHub, abri `scripta-manent-php` > Package settings > Change visibility > Public. Fiz o mesmo com `scripta-manent-web`.
