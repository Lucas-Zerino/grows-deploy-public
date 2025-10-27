<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\OutboxService;
use App\Utils\Logger;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Outbox Processor Worker...\n";
Logger::info('Outbox Processor Worker started');

$running = true;

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () use (&$running) {
    echo "Shutting down gracefully...\n";
    Logger::info('Outbox Processor Worker shutting down');
    $running = false;
});

pcntl_signal(SIGINT, function () use (&$running) {
    echo "Shutting down gracefully...\n";
    Logger::info('Outbox Processor Worker shutting down');
    $running = false;
});

try {
    while ($running) {
        pcntl_signal_dispatch();
        
        // Processar batch de mensagens do outbox
        $processed = OutboxService::processOutbox(100);
        
        if ($processed > 0) {
            echo "Processed {$processed} outbox messages\n";
        }
        
        // Aguardar 5 segundos antes do próximo batch
        sleep(5);
    }
    
    echo "Worker stopped\n";
    Logger::info('Outbox Processor Worker stopped');
    
} catch (\Exception $e) {
    Logger::critical('Outbox Processor Worker crashed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "Worker crashed: {$e->getMessage()}\n";
    exit(1);
}

