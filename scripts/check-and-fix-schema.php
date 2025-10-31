<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "========================================";
echo "\n  Verificando e corrigindo schema...";
echo "\n========================================";
echo "\n\n";

try {
    $db = Database::getInstance();
    
    $fixed = 0;
    $skipped = 0;
    $errors = [];
    
    // ==========================================
    // 1. Verificar tabela validated_phone_numbers
    // ==========================================
    echo "Verificando tabela validated_phone_numbers...\n";
    
    $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'validated_phone_numbers')");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        echo "  → Tabela não existe, criando...\n";
        try {
            $db->exec("
                CREATE TABLE validated_phone_numbers (
                    id SERIAL PRIMARY KEY,
                    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
                    original_number VARCHAR(20) NOT NULL,
                    validated_number VARCHAR(20) NOT NULL,
                    is_valid BOOLEAN NOT NULL DEFAULT true,
                    last_validated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");
            echo "  ✓ Tabela criada\n";
            $fixed++;
        } catch (\Exception $e) {
            echo "  ✗ Erro ao criar tabela: " . $e->getMessage() . "\n";
            $errors[] = "validated_phone_numbers (criação): " . $e->getMessage();
        }
    } else {
        echo "  ✓ Tabela existe\n";
        $skipped++;
        
        // Verificar colunas
        $requiredColumns = [
            'id' => 'SERIAL',
            'instance_id' => 'INTEGER',
            'original_number' => 'VARCHAR(20)',
            'validated_number' => 'VARCHAR(20)',
            'is_valid' => 'BOOLEAN',
            'last_validated_at' => 'TIMESTAMP',
            'created_at' => 'TIMESTAMP',
            'updated_at' => 'TIMESTAMP'
        ];
        
        $stmt = $db->query("
            SELECT column_name, data_type, character_maximum_length 
            FROM information_schema.columns 
            WHERE table_name = 'validated_phone_numbers'
        ");
        $existingColumns = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $existingColumns[$row['column_name']] = $row;
        }
        
        foreach ($requiredColumns as $colName => $type) {
            if (!isset($existingColumns[$colName])) {
                echo "  → Coluna {$colName} não existe, adicionando...\n";
                try {
                    if ($colName === 'id') {
                        // ID já existe (deve ser PRIMARY KEY)
                        continue;
                    } elseif ($colName === 'instance_id') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE");
                    } elseif ($colName === 'original_number') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN original_number VARCHAR(20) NOT NULL");
                    } elseif ($colName === 'validated_number') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN validated_number VARCHAR(20) NOT NULL");
                    } elseif ($colName === 'is_valid') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN is_valid BOOLEAN NOT NULL DEFAULT true");
                    } elseif ($colName === 'last_validated_at') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN last_validated_at TIMESTAMP NOT NULL DEFAULT NOW()");
                    } elseif ($colName === 'created_at') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT NOW()");
                    } elseif ($colName === 'updated_at') {
                        $db->exec("ALTER TABLE validated_phone_numbers ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT NOW()");
                    }
                    echo "  ✓ Coluna {$colName} adicionada\n";
                    $fixed++;
                } catch (\Exception $e) {
                    echo "  ✗ Erro ao adicionar coluna {$colName}: " . $e->getMessage() . "\n";
                    $errors[] = "validated_phone_numbers.{$colName}: " . $e->getMessage();
                }
            }
        }
        
        // Verificar índices
        $requiredIndexes = [
            'validated_phone_numbers_instance_original_key' => 'UNIQUE INDEX',
            'idx_validated_phone_numbers_validated' => 'INDEX',
            'idx_validated_phone_numbers_original' => 'INDEX'
        ];
        
        $stmt = $db->query("
            SELECT indexname 
            FROM pg_indexes 
            WHERE tablename = 'validated_phone_numbers'
        ");
        $existingIndexes = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $existingIndexes[] = $row['indexname'];
        }
        
        foreach ($requiredIndexes as $indexName => $type) {
            if (!in_array($indexName, $existingIndexes)) {
                echo "  → Índice {$indexName} não existe, criando...\n";
                try {
                    if ($indexName === 'validated_phone_numbers_instance_original_key') {
                        $db->exec("CREATE UNIQUE INDEX validated_phone_numbers_instance_original_key ON validated_phone_numbers(instance_id, original_number)");
                    } elseif ($indexName === 'idx_validated_phone_numbers_validated') {
                        $db->exec("CREATE INDEX idx_validated_phone_numbers_validated ON validated_phone_numbers(instance_id, validated_number)");
                    } elseif ($indexName === 'idx_validated_phone_numbers_original') {
                        $db->exec("CREATE INDEX idx_validated_phone_numbers_original ON validated_phone_numbers(instance_id, original_number)");
                    }
                    echo "  ✓ Índice {$indexName} criado\n";
                    $fixed++;
                } catch (\Exception $e) {
                    echo "  ✗ Erro ao criar índice {$indexName}: " . $e->getMessage() . "\n";
                    $errors[] = "validated_phone_numbers.{$indexName}: " . $e->getMessage();
                }
            }
        }
        
        // Verificar trigger
        $stmt = $db->query("
            SELECT tgname 
            FROM pg_trigger 
            WHERE tgname = 'update_validated_phone_numbers_updated_at'
        ");
        $triggerExists = $stmt->fetchColumn();
        
        if (!$triggerExists) {
            echo "  → Trigger não existe, criando...\n";
            try {
                // Verificar se a função update_updated_at_column existe
                $stmt = $db->query("SELECT EXISTS (SELECT FROM pg_proc WHERE proname = 'update_updated_at_column')");
                $functionExists = $stmt->fetchColumn();
                
                if (!$functionExists) {
                    echo "  → Função update_updated_at_column não existe, criando...\n";
                    $db->exec("
                        CREATE OR REPLACE FUNCTION update_updated_at_column()
                        RETURNS TRIGGER AS \$\$
                        BEGIN
                            NEW.updated_at = NOW();
                            RETURN NEW;
                        END;
                        \$\$ LANGUAGE plpgsql;
                    ");
                    echo "  ✓ Função criada\n";
                    $fixed++;
                }
                
                $db->exec("
                    CREATE TRIGGER update_validated_phone_numbers_updated_at 
                    BEFORE UPDATE ON validated_phone_numbers
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()
                ");
                echo "  ✓ Trigger criado\n";
                $fixed++;
            } catch (\Exception $e) {
                echo "  ✗ Erro ao criar trigger: " . $e->getMessage() . "\n";
                $errors[] = "validated_phone_numbers (trigger): " . $e->getMessage();
            }
        }
    }
    
    echo "\n";
    echo "========================================";
    echo "\nResumo:";
    echo "\n  ✓ Corrigido: {$fixed}";
    echo "\n  ✓ Já existe: {$skipped}";
    if (!empty($errors)) {
        echo "\n  ✗ Erros: " . count($errors);
        foreach ($errors as $error) {
            echo "\n    - {$error}";
        }
    }
    echo "\n========================================\n";
    
    if (!empty($errors)) {
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

