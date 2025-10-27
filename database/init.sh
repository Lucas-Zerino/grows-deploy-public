#!/bin/bash
# Script de inicialização do banco de dados

set -e

echo "Aguardando PostgreSQL ficar pronto..."
until pg_isready -h ${DB_HOST:-localhost} -p ${DB_PORT:-5432} -U ${DB_USER:-postgres}; do
  sleep 2
done

echo "Aplicando schema..."
psql -h ${DB_HOST:-localhost} -p ${DB_PORT:-5432} -U ${DB_USER:-postgres} -d ${DB_NAME:-growhub_gateway} -f /var/www/html/database/schema.sql

echo "Banco de dados inicializado com sucesso!"

