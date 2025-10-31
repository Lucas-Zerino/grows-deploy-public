-- Migration: Adicionar TimescaleDB e converter tabela logs em hypertable
-- TimescaleDB é uma extensão do PostgreSQL otimizada para dados time-series
-- que melhora significativamente a performance de inserção e busca de logs

-- Criar extensão TimescaleDB
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- Verificar se a tabela logs já existe e é uma hypertable
DO $$
BEGIN
    -- Se a tabela não é uma hypertable ainda, converter
    IF EXISTS (
        SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'logs'
    ) AND NOT EXISTS (
        SELECT 1 FROM timescaledb_information.hypertables WHERE hypertable_name = 'logs'
    ) THEN
        -- Converter tabela logs em hypertable com chunks diários
        -- Isso otimiza queries por data e permite compression automática
        PERFORM create_hypertable('logs', 'created_at', 
            chunk_time_interval => INTERVAL '1 day',
            if_not_exists => true
        );
        
        RAISE NOTICE 'Tabela logs convertida em hypertable com chunks diários';
    ELSE
        RAISE NOTICE 'Tabela logs já é uma hypertable ou não existe';
    END IF;
END $$;

-- Criar índices adicionais otimizados para queries comuns
-- Índice composto para busca por nível e data (ordem mais comum)
CREATE INDEX IF NOT EXISTS idx_logs_level_created ON logs(level, created_at DESC);

-- Índice composto para busca por empresa e data
CREATE INDEX IF NOT EXISTS idx_logs_company_created ON logs(company_id, created_at DESC) 
    WHERE company_id IS NOT NULL;

-- Índice para busca por instância e data
CREATE INDEX IF NOT EXISTS idx_logs_instance_created ON logs(instance_id, created_at DESC) 
    WHERE instance_id IS NOT NULL;

-- Comentários explicativos
COMMENT ON EXTENSION timescaledb IS 'TimescaleDB: Extensão para dados time-series otimizando performance de logs';
COMMENT ON TABLE logs IS 'Tabela de logs do sistema convertida em hypertable TimescaleDB para melhor performance';

