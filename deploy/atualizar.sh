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
  echo "Atualizando para o commit $revisao"
  git fetch -q origin
  git checkout -q "$revisao"
  "${COMPOSE[@]}" up -d --remove-orphans
  docker image prune -f
  echo "Produção no commit $revisao"
}

main; exit
