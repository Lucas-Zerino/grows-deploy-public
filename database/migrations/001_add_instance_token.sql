-- Migration: Adicionar campo token nas instâncias
-- Data: 2025-10-14
-- Descrição: Cada instância agora tem seu próprio token UUID para autenticação

-- Adicionar coluna token se não existir
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='instances' AND column_name='token') THEN
        ALTER TABLE instances ADD COLUMN token UUID NOT NULL DEFAULT gen_random_uuid() UNIQUE;
        CREATE INDEX idx_instances_token ON instances(token);
        
        RAISE NOTICE 'Coluna token adicionada com sucesso!';
    ELSE
        RAISE NOTICE 'Coluna token já existe, pulando migration';
    END IF;
END $$;

