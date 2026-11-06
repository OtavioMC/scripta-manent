#!/bin/sh

if [ -z "${BANCO_SENHA:-}" ]; then
    echo "BANCO_SENHA está vazio. O usuário da aplicação não foi criado." >&2
    exit 1
fi

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE USER 'aplicacao'@'%' IDENTIFIED BY '$BANCO_SENHA';
GRANT SELECT, INSERT, UPDATE, DELETE ON scripta_manent.* TO 'aplicacao'@'%';
SQL
