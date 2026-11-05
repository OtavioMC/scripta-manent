#!/bin/sh
set -eu

if [ -z "${NGINX_HTPASSWD:-}" ]; then
    echo "NGINX_HTPASSWD está vazio. Gere a senha com htpasswd e coloque no .env antes de subir o site." >&2
    exit 1
fi

printf '%s\n' "$NGINX_HTPASSWD" > /etc/nginx/.htpasswd
chown root:nginx /etc/nginx/.htpasswd
chmod 640 /etc/nginx/.htpasswd
