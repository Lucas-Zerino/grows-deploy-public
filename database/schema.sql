-- GrowHub Gateway - Database Schema
-- PostgreSQL 16

-- Extension for UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Admins/Users table
CREATE TABLE admins (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    token UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    is_superadmin BOOLEAN NOT NULL DEFAULT false,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    last_login TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_admins_token ON admins(token);
CREATE INDEX idx_admins_status ON admins(status);

-- Companies table
CREATE TABLE companies (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    token UUID NOT NULL UNIQUE DEFAULT uuid_generate_v4(),
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_companies_token ON companies(token);
CREATE INDEX idx_companies_status ON companies(status);

-- Providers table (Servidores WAHA/UAZAPI)
CREATE TABLE providers (
    id SERIAL PRIMARY KEY,
    type VARCHAR(20) NOT NULL CHECK (type IN ('waha', 'uazapi')),
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    api_key VARCHAR(500),
    is_active BOOLEAN NOT NULL DEFAULT true,
    max_instances INTEGER NOT NULL DEFAULT 100,
    current_instances INTEGER NOT NULL DEFAULT 0,
    health_status VARCHAR(20) NOT NULL DEFAULT 'unknown' CHECK (health_status IN ('healthy', 'unhealthy', 'unknown')),
    last_check TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_providers_type ON providers(type);
CREATE INDEX idx_providers_active ON providers(is_active);
CREATE INDEX idx_providers_health ON providers(health_status);

-- Instances table
CREATE TABLE instances (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    provider_id INTEGER NOT NULL REFERENCES providers(id) ON DELETE RESTRICT,
    instance_name VARCHAR(100) NOT NULL,
    token UUID NOT NULL DEFAULT gen_random_uuid() UNIQUE,
    phone_number VARCHAR(20),
    status VARCHAR(20) NOT NULL DEFAULT 'creating' CHECK (status IN ('creating', 'connecting', 'connected', 'active', 'disconnected', 'error', 'deleted')),
    webhook_url VARCHAR(500),
    external_instance_id VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Unique constraint: permite recriar instância com mesmo nome após deletar
-- A constraint só aplica para registros que NÃO estão deletados
CREATE UNIQUE INDEX instances_company_id_instance_name_key ON instances(company_id, instance_name) WHERE status != 'deleted';

CREATE INDEX idx_instances_company ON instances(company_id);
CREATE INDEX idx_instances_provider ON instances(provider_id);
CREATE INDEX idx_instances_status ON instances(status);
CREATE INDEX idx_instances_token ON instances(token);
CREATE INDEX idx_instances_phone ON instances(phone_number);

-- Instance webhooks table (multiple webhooks per instance)
CREATE TABLE instance_webhooks (
    id SERIAL PRIMARY KEY,
    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
    webhook_url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL DEFAULT '[]',
    is_active BOOLEAN NOT NULL DEFAULT true,
    retry_count INTEGER NOT NULL DEFAULT 0,
    last_retry_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_instance_webhooks_instance ON instance_webhooks(instance_id);
CREATE INDEX idx_instance_webhooks_active ON instance_webhooks(is_active);
CREATE INDEX idx_instance_webhooks_url ON instance_webhooks(webhook_url);

COMMENT ON TABLE instance_webhooks IS 'Multiple webhooks per instance for receiving events';
COMMENT ON COLUMN instance_webhooks.events IS 'Array of event types this webhook should receive';
COMMENT ON COLUMN instance_webhooks.retry_count IS 'Number of failed delivery attempts';
COMMENT ON COLUMN instance_webhooks.last_retry_at IS 'Last time a retry was attempted';

-- Validated phone numbers table (cache de números validados)
CREATE TABLE IF NOT EXISTS validated_phone_numbers (
    id SERIAL PRIMARY KEY,
    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
    original_number VARCHAR(20) NOT NULL,
    validated_number VARCHAR(20) NOT NULL,
    is_valid BOOLEAN NOT NULL DEFAULT true,
    last_validated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX validated_phone_numbers_instance_original_key 
    ON validated_phone_numbers(instance_id, original_number);

CREATE INDEX idx_validated_phone_numbers_validated 
    ON validated_phone_numbers(instance_id, validated_number);

CREATE INDEX idx_validated_phone_numbers_original 
    ON validated_phone_numbers(instance_id, original_number);

COMMENT ON TABLE validated_phone_numbers IS 'Cache de números de telefone validados para evitar validações repetidas';
COMMENT ON COLUMN validated_phone_numbers.original_number IS 'Número original fornecido pelo usuário';
COMMENT ON COLUMN validated_phone_numbers.validated_number IS 'Número correto validado (com ou sem dígito 9)';
COMMENT ON COLUMN validated_phone_numbers.is_valid IS 'Se o número é válido no WhatsApp';
COMMENT ON COLUMN validated_phone_numbers.last_validated_at IS 'Última vez que o número foi validado';

CREATE TRIGGER update_validated_phone_numbers_updated_at BEFORE UPDATE ON validated_phone_numbers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Messages table
CREATE TABLE messages (
    id BIGSERIAL PRIMARY KEY,
    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    direction VARCHAR(10) NOT NULL CHECK (direction IN ('outbound', 'inbound')),
    phone_to VARCHAR(20) NOT NULL,
    phone_from VARCHAR(20) NOT NULL,
    message_type VARCHAR(20) NOT NULL DEFAULT 'text' CHECK (message_type IN ('text', 'image', 'video', 'audio', 'document', 'location', 'contact')),
    content TEXT,
    media_url TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'queued' CHECK (status IN ('queued', 'processing', 'sent', 'delivered', 'read', 'failed', 'error')),
    priority INTEGER NOT NULL DEFAULT 5 CHECK (priority BETWEEN 1 AND 10),
    external_id VARCHAR(255),
    error_message TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    sent_at TIMESTAMP,
    delivered_at TIMESTAMP,
    read_at TIMESTAMP
);

CREATE INDEX idx_messages_instance ON messages(instance_id);
CREATE INDEX idx_messages_company ON messages(company_id);
CREATE INDEX idx_messages_direction ON messages(direction);
CREATE INDEX idx_messages_status ON messages(status);
CREATE INDEX idx_messages_created ON messages(created_at DESC);
CREATE INDEX idx_messages_phone_to ON messages(phone_to);
CREATE INDEX idx_messages_phone_from ON messages(phone_from);
CREATE INDEX idx_messages_external_id ON messages(external_id);

-- Events table
CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    message_id BIGINT REFERENCES messages(id) ON DELETE CASCADE,
    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL CHECK (event_type IN ('delivered', 'read', 'sent', 'failed', 'connection_open', 'connection_close', 'connection_update', 'qr_code', 'status_update')),
    payload JSONB,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_events_message ON events(message_id);
CREATE INDEX idx_events_instance ON events(instance_id);
CREATE INDEX idx_events_company ON events(company_id);
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_events_created ON events(created_at DESC);
CREATE INDEX idx_events_payload ON events USING GIN(payload);

-- Outbox messages table (OutboxDB Pattern)
CREATE TABLE outbox_messages (
    id BIGSERIAL PRIMARY KEY,
    queue_name VARCHAR(255) NOT NULL,
    routing_key VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 3,
    error_message TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    processed_at TIMESTAMP,
    next_retry_at TIMESTAMP
);

CREATE INDEX idx_outbox_status ON outbox_messages(status);
CREATE INDEX idx_outbox_queue ON outbox_messages(queue_name);
CREATE INDEX idx_outbox_created ON outbox_messages(created_at);
CREATE INDEX idx_outbox_next_retry ON outbox_messages(next_retry_at) WHERE status = 'failed';

-- Logs table
CREATE TABLE logs (
    id BIGSERIAL PRIMARY KEY,
    level VARCHAR(20) NOT NULL CHECK (level IN ('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL')),
    context VARCHAR(100),
    message TEXT NOT NULL,
    payload JSONB,
    company_id INTEGER REFERENCES companies(id) ON DELETE SET NULL,
    instance_id INTEGER REFERENCES instances(id) ON DELETE SET NULL,
    message_id BIGINT REFERENCES messages(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_logs_level ON logs(level);
CREATE INDEX idx_logs_context ON logs(context);
CREATE INDEX idx_logs_created ON logs(created_at DESC);
CREATE INDEX idx_logs_company ON logs(company_id);
CREATE INDEX idx_logs_payload ON logs USING GIN(payload);

-- Índices compostos otimizados para queries comuns (úteis mesmo sem TimescaleDB)
CREATE INDEX idx_logs_level_created ON logs(level, created_at DESC);
CREATE INDEX idx_logs_company_created ON logs(company_id, created_at DESC) WHERE company_id IS NOT NULL;
CREATE INDEX idx_logs_instance_created ON logs(instance_id, created_at DESC) WHERE instance_id IS NOT NULL;

-- Comentário sobre otimização TimescaleDB (será aplicada via migration 007)
-- COMMENT: A tabela logs será convertida em hypertable TimescaleDB para melhor performance via migration

-- Rate limiting table
CREATE TABLE rate_limits (
    id BIGSERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    endpoint VARCHAR(255) NOT NULL,
    count INTEGER NOT NULL DEFAULT 1,
    window_start TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(company_id, endpoint, window_start)
);

CREATE INDEX idx_rate_limits_company ON rate_limits(company_id);
CREATE INDEX idx_rate_limits_window ON rate_limits(window_start);

-- Queue metadata (para monitoramento)
CREATE TABLE queue_metadata (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    queue_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    last_activity TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT true
);

CREATE INDEX idx_queue_metadata_company ON queue_metadata(company_id);
CREATE INDEX idx_queue_metadata_active ON queue_metadata(is_active);

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers for updated_at
CREATE TRIGGER update_admins_updated_at BEFORE UPDATE ON admins
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_companies_updated_at BEFORE UPDATE ON companies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_providers_updated_at BEFORE UPDATE ON providers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_instances_updated_at BEFORE UPDATE ON instances
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_instance_webhooks_updated_at BEFORE UPDATE ON instance_webhooks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comments
COMMENT ON TABLE admins IS 'Administradores do sistema (superadmin e staff)';
COMMENT ON TABLE companies IS 'Empresas que utilizam o gateway';
COMMENT ON TABLE providers IS 'Servidores WAHA/UAZAPI configurados';
COMMENT ON TABLE instances IS 'Instâncias de WhatsApp das empresas';
COMMENT ON TABLE instance_webhooks IS 'Múltiplos webhooks por instância para receber eventos';
COMMENT ON TABLE messages IS 'Histórico de mensagens enviadas e recebidas';
COMMENT ON TABLE events IS 'Eventos relacionados às mensagens e instâncias';
COMMENT ON TABLE outbox_messages IS 'Outbox pattern para garantir entrega às filas';
COMMENT ON TABLE logs IS 'Logs estruturados do sistema';
COMMENT ON TABLE rate_limits IS 'Controle de rate limiting por empresa';
COMMENT ON TABLE queue_metadata IS 'Metadados das filas dinâmicas criadas';

