<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Running database migrations...\n\n";

try {
    $db = Database::getInstance();
    
    // Lista de migrações a executar (em ordem)
    $migrations = [
        '003_add_instance_webhooks.sql' => function($db) {
            // Verificar se a tabela já existe
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'instance_webhooks')");
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                echo "✓ Table instance_webhooks already exists, skipping migration 003\n";
                return false;
            }
            
            return true; // Precisa executar
        },
        '004_add_instagram_support.sql' => function($db) {
            // Verificar se as colunas do Instagram já existem
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'instances' AND column_name = 'instagram_user_id')");
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                echo "✓ Instagram columns already exist, skipping migration 004\n";
                return false;
            }
            
            return true; // Precisa executar
        },
        '005_add_facebook_support.sql' => function($db) {
            // Verificar se as colunas do Facebook já existem
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'instances' AND column_name = 'facebook_page_id')");
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                echo "✓ Facebook columns already exist, skipping migration 005\n";
                return false;
            }
            
            return true; // Precisa executar
        },
        '006_add_validated_phone_numbers.sql' => function($db) {
            // Verificar se a tabela já existe E se está completa
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'validated_phone_numbers')");
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                // Verificar se tem todas as colunas necessárias
                $stmt = $db->query("
                    SELECT COUNT(*) 
                    FROM information_schema.columns 
                    WHERE table_name = 'validated_phone_numbers' 
                    AND column_name IN ('id', 'instance_id', 'original_number', 'validated_number', 'is_valid', 'last_validated_at', 'created_at', 'updated_at')
                ");
                $columnCount = $stmt->fetchColumn();
                
                if ($columnCount >= 8) {
                    echo "✓ Table validated_phone_numbers already exists and is complete, skipping migration 006\n";
                    return false;
                } else {
                    echo "⚠ Table validated_phone_numbers exists but is incomplete, will fix with check-and-fix-schema\n";
                    return false; // Deixar o check-and-fix-schema corrigir
                }
            }
            
            return true; // Precisa executar
        }
    ];
    
    $executed = 0;
    $skipped = 0;
    
    foreach ($migrations as $migrationFile => $checkFunction) {
        $migrationPath = __DIR__ . '/../database/migrations/' . $migrationFile;
        
        if (!file_exists($migrationPath)) {
            echo "⚠ Migration file not found: {$migrationFile}\n";
            continue;
        }
        
        // Verificar se precisa executar
        $shouldRun = $checkFunction($db);
        
        if (!$shouldRun) {
            $skipped++;
            continue;
        }
        
        echo "Running migration: {$migrationFile}\n";
        
        $sql = file_get_contents($migrationPath);
        
        if (!$sql) {
            throw new Exception("Could not read migration file: {$migrationPath}");
        }
        
        // Executar a migração
        try {
            $db->exec($sql);
            echo "✓ Migration {$migrationFile} completed successfully!\n\n";
            $executed++;
        } catch (\PDOException $e) {
            // Se for erro de "já existe", apenas avisar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'duplicate') !== false) {
                echo "⚠ Migration {$migrationFile} skipped (already applied): {$e->getMessage()}\n\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n========================================";
    echo "\nMigrations summary:";
    echo "\n  Executed: {$executed}";
    echo "\n  Skipped: {$skipped}";
    echo "\n========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
