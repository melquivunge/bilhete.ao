#!/bin/bash
# Cria a base de dados usada pelos testes, separada da de desenvolvimento.
#
# phpunit.xml aponta DB_DATABASE para "${DB_DATABASE}_testing". Sem esta base, o
# primeiro teste que toque no PostgreSQL falha — e como nenhum teste do Marco 0
# até C0.4 usa base de dados, a falta só apareceria em C0.5.
#
# Scripts em /docker-entrypoint-initdb.d/ correm apenas quando o volume de dados
# é criado de raiz. Um volume já existente não é reinicializado.

set -euo pipefail

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE "${POSTGRES_DB}_testing" OWNER "$POSTGRES_USER";
EOSQL

echo "base de dados de testes criada: ${POSTGRES_DB}_testing"
