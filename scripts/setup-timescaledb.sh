#!/bin/sh
# Script para configurar TimescaleDB no PostgreSQL

echo "Configurando TimescaleDB..."

# Aguardar PostgreSQL estar pronto
until pg_isready -U postgres -h localhost; do
  echo "Aguardando PostgreSQL..."
  sleep 1
done

# Criar extensão TimescaleDB
psql -U postgres -d growhub_gateway <<EOF
-- Criar extensão TimescaleDB
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- Verificar se a tabela logs já existe e converter para hypertable
DO \$\$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'logs'
    ) AND NOT EXISTS (
        SELECT 1 FROM timescaledb_information.hypertables WHERE hypertable_name = 'logs'
    ) THEN
        PERFORM create_hypertable('logs', 'created_at', 
            chunk_time_interval => INTERVAL '1 day',
            if_not_exists => true
        );
        RAISE NOTICE 'Tabela logs convertida em hypertable';
    ELSE
        RAISE NOTICE 'Tabela logs já é hypertable ou não existe';
    END IF;
END \$\$;

-- Criar índices otimizados se não existirem
CREATE INDEX IF NOT EXISTS idx_logs_level_created ON logs(level, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_logs_company_created ON logs(company_id, created_at DESC) 
    WHERE company_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_logs_instance_created ON logs(instance_id, created_at DESC) 
    WHERE instance_id IS NOT NULL;
EOF

echo "TimescaleDB configurado com sucesso!"

