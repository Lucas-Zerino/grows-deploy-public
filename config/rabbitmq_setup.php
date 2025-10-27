<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Utils\Logger;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Setting up RabbitMQ topology...\n";
Logger::info('RabbitMQ setup started');

try {
    QueueService::init();
    $config = require __DIR__ . '/rabbitmq.php';
    
    // ===== DECLARAR EXCHANGES =====
    echo "Creating exchanges...\n";
    
    QueueService::declareExchange($config['exchanges']['outbound'], 'topic', true);
    echo "  ✓ {$config['exchanges']['outbound']} (topic)\n";
    
    QueueService::declareExchange($config['exchanges']['inbound'], 'fanout', true);
    echo "  ✓ {$config['exchanges']['inbound']} (fanout)\n";
    
    QueueService::declareExchange($config['exchanges']['events'], 'topic', true);
    echo "  ✓ {$config['exchanges']['events']} (topic)\n";
    
    QueueService::declareExchange($config['exchanges']['retry'], 'topic', true);
    echo "  ✓ {$config['exchanges']['retry']} (topic)\n";
    
    QueueService::declareExchange($config['exchanges']['dlq'], 'direct', true);
    echo "  ✓ {$config['exchanges']['dlq']} (direct)\n";
    
    // ===== DECLARAR FILAS GLOBAIS =====
    echo "\nCreating global queues...\n";
    
    // Outbox processor
    QueueService::declareQueue($config['global_queues']['outbox_processor'], true);
    echo "  ✓ {$config['global_queues']['outbox_processor']}\n";
    
    // Health check
    QueueService::declareQueue($config['global_queues']['health_check'], true);
    echo "  ✓ {$config['global_queues']['health_check']}\n";
    
    // Queue manager
    QueueService::declareQueue($config['global_queues']['queue_manager'], true);
    echo "  ✓ {$config['global_queues']['queue_manager']}\n";
    
    // Webhook fanout
    QueueService::declareQueue($config['global_queues']['webhook_fanout'], true);
    echo "  ✓ {$config['global_queues']['webhook_fanout']}\n";
    
    // DLQ final
    QueueService::declareQueue($config['global_queues']['dlq_final'], true);
    QueueService::bindQueue(
        $config['global_queues']['dlq_final'],
        $config['exchanges']['dlq'],
        'final'
    );
    echo "  ✓ {$config['global_queues']['dlq_final']}\n";
    
    echo "\n✓ RabbitMQ topology setup completed successfully!\n";
    echo "\nNote: Company-specific queues will be created automatically when companies are created.\n";
    
    Logger::info('RabbitMQ setup completed');
    
    QueueService::close();
    
} catch (\Exception $e) {
    echo "\n✗ Setup failed: {$e->getMessage()}\n";
    Logger::critical('RabbitMQ setup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    exit(1);
}

