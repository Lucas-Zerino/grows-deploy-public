<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\HealthCheckService;
use App\Middleware\RateLimitMiddleware;
use App\Services\OutboxService;
use App\Utils\Logger;
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

