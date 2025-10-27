-- Migration: Adicionar status 'connecting' e 'connected' no constraint
-- Data: 2025-10-14
-- Descrição: Permitir status connecting/connected para instâncias em processo de conexão

-- Remover constraint antigo e criar novo
ALTER TABLE instances DROP CONSTRAINT IF EXISTS instances_status_check;

ALTER TABLE instances ADD CONSTRAINT instances_status_check 
    CHECK (status IN ('creating', 'connecting', 'connected', 'active', 'disconnected', 'error', 'deleted'));

-- Log
DO $$
BEGIN
    RAISE NOTICE 'Constraint instances_status_check atualizado com sucesso! Status permitidos: creating, connecting, connected, active, disconnected, error, deleted';
END $$;

