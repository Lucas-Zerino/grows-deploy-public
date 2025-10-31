<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\HealthCheckService;
use App\Middleware\RateLimitMiddleware;
use App\Services\OutboxService;
use App\Utils\Logger;
use App\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Health Check Worker...\n";
Logger::info('Health Check Worker started');

$running = true;

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () use (&$running) {
    echo "Shutting down gracefully...\n";
    Logger::info('Health Check Worker shutting down');
    $running = false;
});

pcntl_signal(SIGINT, function () use (&$running) {
    echo "Shutting down gracefully...\n";
    Logger::info('Health Check Worker shutting down');
    $running = false;
});

try {
    $iteration = 0;
    
    while ($running) {
        pcntl_signal_dispatch();
        
        echo "[" . date('Y-m-d H:i:s') . "] Running health checks...\n";
        
        // Check all providers (a cada 1 minuto)
        $results = HealthCheckService::checkAllProviders();
        
        $healthy = count(array_filter($results, fn($r) => $r['status'] === 'healthy'));
        $total = count($results);
        
        echo "Providers: {$healthy}/{$total} healthy\n";
        
        // Cleanup tasks (a cada 10 minutos)
        if ($iteration % 10 === 0) {
            echo "Running cleanup tasks...\n";
            
            // Cleanup old outbox messages
            $deletedOutbox = OutboxService::cleanupOldMessages(7);
            echo "Cleaned {$deletedOutbox} old outbox messages\n";
            
            // Cleanup old rate limits
            $deletedRateLimits = RateLimitMiddleware::cleanup();
            echo "Cleaned {$deletedRateLimits} old rate limit records\n";
            
            // Cleanup old logs (configurável via LOG_RETENTION_DAYS, padrão 90 dias)
            $retentionDays = (int)($_ENV['LOG_RETENTION_DAYS'] ?? 90);
            $deletedLogs = cleanupOldLogs($retentionDays);
            if ($deletedLogs >= 0) {
                echo "Cleaned {$deletedLogs} old logs (older than {$retentionDays} days)\n";
            } else {
                echo "Cleaned old logs using TimescaleDB drop_chunks (older than {$retentionDays} days)\n";
            }
        }
        
        $iteration++;
        
        // Aguardar 1 minuto
        sleep(60);
    }
    
    echo "Worker stopped\n";
    Logger::info('Health Check Worker stopped');
    
} catch (\Exception $e) {
    Logger::critical('Health Check Worker crashed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "Worker crashed: {$e->getMessage()}\n";
    exit(1);
}

/**
 * Limpar logs antigos usando TimescaleDB drop_chunks ou DELETE simples
 */
function cleanupOldLogs(int $retentionDays): int
{
    try {
        $db = Database::getInstance();
        
        // Verificar se TimescaleDB está instalado e se logs é hypertable
        $isHypertable = $db->query("
            SELECT EXISTS (
                SELECT 1 FROM timescaledb_information.hypertables 
                WHERE hypertable_name = 'logs'
            )
        ")->fetchColumn();
        
        if ($retentionDays <= 0) {
            return 0; // Não limpar se retentionDays <= 0
        }
        
        if ($isHypertable) {
            // Usar drop_chunks do TimescaleDB (muito mais rápido)
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
            
            $sql = "SELECT drop_chunks('logs', INTERVAL '{$retentionDays} days')";
            $db->exec($sql);
            
            // Retornar número estimado (não podemos contar exatamente com drop_chunks)
            // Mas é muito mais eficiente que DELETE
            return -1; // Indica que foi usado drop_chunks (muito mais eficiente)
        } else {
            // Fallback para DELETE se não for hypertable
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
            
            $sql = "DELETE FROM logs WHERE created_at < :cutoff_date";
            $stmt = $db->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate]);
            
            return $stmt->rowCount();
        }
    } catch (\Exception $e) {
        Logger::warning('Failed to cleanup old logs', [
            'error' => $e->getMessage(),
            'retention_days' => $retentionDays,
        ]);
        return 0;
    }
}

