-- Migration 004: Add Instagram Support
-- Adiciona suporte completo para integração com Instagram Business API

-- Tabela para armazenar credenciais do Instagram App de cada company
CREATE TABLE instagram_apps (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    app_id VARCHAR(255) NOT NULL,
    app_secret VARCHAR(500) NOT NULL, -- Criptografado
    access_token TEXT, -- Long-lived token
    token_expires_at TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'expired', 'error')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(company_id)
);

CREATE INDEX idx_instagram_apps_company ON instagram_apps(company_id);
CREATE INDEX idx_instagram_apps_status ON instagram_apps(status);

-- Adicionar colunas Instagram na tabela instances
ALTER TABLE instances ADD COLUMN instagram_user_id VARCHAR(255);
ALTER TABLE instances ADD COLUMN instagram_username VARCHAR(255);

-- Adicionar tipo 'instagram' no CHECK constraint de providers
ALTER TABLE providers DROP CONSTRAINT providers_type_check;
ALTER TABLE providers ADD CONSTRAINT providers_type_check 
    CHECK (type IN ('waha', 'uazapi', 'instagram'));

-- Adicionar trigger para updated_at na tabela instagram_apps
CREATE TRIGGER update_instagram_apps_updated_at BEFORE UPDATE ON instagram_apps
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comentários
COMMENT ON TABLE instagram_apps IS 'Credenciais do Instagram App de cada empresa';
COMMENT ON COLUMN instagram_apps.app_secret IS 'App Secret criptografado usando openssl_encrypt';
COMMENT ON COLUMN instagram_apps.access_token IS 'Long-lived access token válido por 60 dias';
COMMENT ON COLUMN instances.instagram_user_id IS 'ID do usuário Instagram Business conectado';
COMMENT ON COLUMN instances.instagram_username IS 'Username da conta Instagram Business';
