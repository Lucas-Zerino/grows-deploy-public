-- Tabela para cache de números de telefone validados
-- Armazena o número correto após validação (com ou sem o dígito 9)

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

-- Índice único: uma instância só pode ter um registro por número original
CREATE UNIQUE INDEX IF NOT EXISTS validated_phone_numbers_instance_original_key 
    ON validated_phone_numbers(instance_id, original_number);

-- Índice para busca rápida por número validado
CREATE INDEX IF NOT EXISTS idx_validated_phone_numbers_validated 
    ON validated_phone_numbers(instance_id, validated_number);

-- Índice para busca rápida por número original
CREATE INDEX IF NOT EXISTS idx_validated_phone_numbers_original 
    ON validated_phone_numbers(instance_id, original_number);

-- Comentários
COMMENT ON TABLE validated_phone_numbers IS 'Cache de números de telefone validados para evitar validações repetidas';
COMMENT ON COLUMN validated_phone_numbers.original_number IS 'Número original fornecido pelo usuário';
COMMENT ON COLUMN validated_phone_numbers.validated_number IS 'Número correto validado (com ou sem dígito 9)';
COMMENT ON COLUMN validated_phone_numbers.is_valid IS 'Se o número é válido no WhatsApp';
COMMENT ON COLUMN validated_phone_numbers.last_validated_at IS 'Última vez que o número foi validado';

-- Trigger para atualizar updated_at automaticamente
CREATE TRIGGER update_validated_phone_numbers_updated_at BEFORE UPDATE ON validated_phone_numbers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

