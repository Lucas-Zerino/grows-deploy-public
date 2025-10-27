<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Models\Instance;
use App\Utils\Logger;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Event Processor Worker...\n";
Logger::info('Event Processor Worker started');

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Event Processor Worker shutting down');
    QueueService::close();
    exit(0);
});

pcntl_signal(SIGINT, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Event Processor Worker shutting down');
    QueueService::close();
    exit(0);
});

try {
    QueueService::init();
    
    $httpClient = new Client(['timeout' => 10]);
    
    // Consumir do exchange com routing pattern wildcard
    $exchangeName = 'messaging.inbound.exchange';
    $routingPattern = 'company.*'; // Routing key pattern
    
    echo "Consuming events from exchange: {$exchangeName} with pattern: {$routingPattern}\n";
    
    $callback = function (array $data, $msg) use ($httpClient) {
        pcntl_signal_dispatch();
        
        try {
            $instanceId = $data['instance']['instanceId'] ?? $data['instance_id'] ?? null;
            
            Logger::info('Processing inbound event', [
                'instance_id' => $instanceId,
                'event_type' => $data['event'] ?? 'unknown',
            ]);

            // Buscar webhook_url da instância (se não veio no payload)
            $webhookUrl = $data['webhook_url'] ?? null;

            if (!$webhookUrl && $instanceId) {
                // Buscar instância pelo external_instance_id
                $instance = Instance::findByExternalId($instanceId);
                if ($instance && $instance['webhook_url']) {
                    $webhookUrl = $instance['webhook_url'];
                }
            }
            
            // Se tem webhook_url, enviar evento para o cliente (formato UAZAPI)
            if ($webhookUrl) {
                try {
                    $response = $httpClient->post($webhookUrl, [
                        'json' => $data, // Já está no formato UAZAPI após tradução
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'X-Webhook-Source' => 'GrowHub-Gateway',
                            'User-Agent' => 'GrowHub-Gateway/1.0',
                        ],
                    ]);
                    
                    Logger::info('Webhook notification sent to client', [
                        'instance_id' => $instanceId,
                        'webhook_url' => $webhookUrl,
                        'status_code' => $response->getStatusCode(),
                    ]);
                } catch (\Exception $e) {
                    Logger::warning('Failed to send webhook notification to client', [
                        'instance_id' => $instanceId,
                        'webhook_url' => $webhookUrl,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Não falhar o job por erro no webhook do cliente
                }
            } else {
                Logger::debug('No webhook URL configured for instance', [
                    'instance_id' => $instanceId,
                ]);
            }
            
            return true; // ACK
        } catch (\Exception $e) {
            Logger::error('Error processing inbound event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false; // NACK
        }
    };
    
    QueueService::consumeFromExchange($exchangeName, $routingPattern, $callback, 10);
    
} catch (\Exception $e) {
    Logger::critical('Event Processor Worker crashed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "Worker crashed: {$e->getMessage()}\n";
    exit(1);
}

