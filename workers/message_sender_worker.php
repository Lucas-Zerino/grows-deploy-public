<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Services\ProviderManager;
use App\Models\Message;
use App\Utils\Logger;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Message Sender Worker...\n";
Logger::info('Message Sender Worker started');

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Message Sender Worker shutting down');
    QueueService::close();
    exit(0);
});

pcntl_signal(SIGINT, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Message Sender Worker shutting down');
    QueueService::close();
    exit(0);
});

try {
    QueueService::init();
    
    // Consumir do exchange com routing pattern wildcard
    // Cada worker cria uma fila temporária e faz bind no exchange
    $exchangeName = 'messaging.outbound.exchange';
    $routingPattern = 'company.*.priority.*'; // Routing key pattern
    
    echo "Consuming messages from exchange: {$exchangeName} with pattern: {$routingPattern}\n";
    
    $callback = function (array $data, $msg) {
        pcntl_signal_dispatch(); // Process signals
        
        try {
            Logger::info('Processing outbound message', [
                'message_id' => $data['message_id'] ?? null,
            ]);
            
            // Atualizar status para processing
            Message::updateStatus($data['message_id'], 'processing');
            
            // Enviar mensagem via provider
            $result = ProviderManager::sendMessage(
                $data['provider_id'],
                $data['external_instance_id'],
                $data
            );
            
            if ($result['success']) {
                // Atualizar mensagem com external_id
                Message::updateStatus($data['message_id'], 'sent', [
                    'external_id' => $result['message_id'] ?? null,
                ]);
                
                Logger::info('Message sent successfully', [
                    'message_id' => $data['message_id'],
                    'external_id' => $result['message_id'] ?? null,
                ]);
                
                return true; // ACK
            } else {
                // Marcar como failed
                Message::updateStatus($data['message_id'], 'failed', [
                    'error_message' => $result['error'] ?? 'Unknown error',
                ]);
                
                Logger::error('Failed to send message', [
                    'message_id' => $data['message_id'],
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                
                return false; // NACK (will retry)
            }
        } catch (\Exception $e) {
            Logger::error('Error processing outbound message', [
                'message_id' => $data['message_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Message::updateStatus($data['message_id'], 'error', [
                'error_message' => $e->getMessage(),
            ]);
            
            return false; // NACK
        }
    };
    
    // Consumir mensagens do exchange com prefetch de 5
    QueueService::consumeFromExchange($exchangeName, $routingPattern, $callback, 5);
    
} catch (\Exception $e) {
    Logger::critical('Message Sender Worker crashed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "Worker crashed: {$e->getMessage()}\n";
    exit(1);
}

