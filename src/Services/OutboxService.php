<?php

namespace App\Services;

use App\Models\OutboxMessage;
use App\Utils\Database;
use App\Utils\Logger;

class OutboxService
{
    public static function enqueue(
        string $queueName,
        string $routingKey,
        array $payload,
        int $maxAttempts = 3
    ): bool {
        try {
            Database::beginTransaction();
            
            OutboxMessage::create($queueName, $routingKey, $payload, $maxAttempts);
            
            Database::commit();
            
            Logger::info('Message added to outbox', [
                'queue' => $queueName,
                'routing_key' => $routingKey,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Failed to add message to outbox', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public static function processOutbox(int $batchSize = 100): int
    {
        $processed = 0;
        
        try {
            QueueService::init();
            
            $messages = OutboxMessage::findPending($batchSize);
            
            foreach ($messages as $message) {
                // Marcar como processando
                OutboxMessage::markAsProcessing($message['id']);
                
                // Tentar publicar na fila
                $success = QueueService::publish(
                    $message['queue_name'],
                    $message['routing_key'],
                    $message['payload'],
                    $message['payload']['priority'] ?? 5
                );
                
                if ($success) {
                    OutboxMessage::markAsCompleted($message['id']);
                    $processed++;
                    
                    Logger::debug('Outbox message processed successfully', [
                        'outbox_id' => $message['id'],
                    ]);
                } else {
                    OutboxMessage::markAsFailed(
                        $message['id'],
                        'Failed to publish to RabbitMQ'
                    );
                    
                    Logger::warning('Outbox message failed to publish', [
                        'outbox_id' => $message['id'],
                    ]);
                }
            }
            
            if ($processed > 0) {
                Logger::info("Processed {$processed} outbox messages");
            }
            
            return $processed;
        } catch (\Exception $e) {
            Logger::error('Error processing outbox', [
                'error' => $e->getMessage(),
            ]);
            
            return $processed;
        }
    }
    
    public static function cleanupOldMessages(int $daysOld = 7): int
    {
        try {
            $deleted = OutboxMessage::cleanupOld($daysOld);
            
            if ($deleted > 0) {
                Logger::info("Cleaned up {$deleted} old outbox messages");
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Logger::error('Error cleaning up outbox', [
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }
}

