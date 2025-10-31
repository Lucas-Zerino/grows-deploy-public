<?php

return [
    'host' => $_ENV['RABBITMQ_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['RABBITMQ_PORT'] ?? 5672),
    'user' => $_ENV['RABBITMQ_USER'] ?? 'admin',
    'password' => $_ENV['RABBITMQ_PASSWORD'] ?? 'admin123',
    'vhost' => $_ENV['RABBITMQ_VHOST'] ?? '/',
    
    // Exchanges
    'exchanges' => [
        'outbound' => 'messaging.outbound.exchange',
        'inbound' => 'messaging.inbound.exchange',
        'events' => 'events.exchange',
        'retry' => 'retry.exchange',
        'dlq' => 'dlq.exchange',
        'logs' => 'logs.exchange', // Exchange para logs do sistema
    ],
    
    // Global queues
    'global_queues' => [
        'outbox_processor' => 'outbox.processor',
        'health_check' => 'health.check',
        'queue_manager' => 'queue.manager',
        'webhook_fanout' => 'webhook.fanout',
        'dlq_final' => 'dlq.final',
        'logs' => 'logs.queue', // Fila para processamento de logs
    ],
];

