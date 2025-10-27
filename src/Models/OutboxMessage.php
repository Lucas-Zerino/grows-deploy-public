<?php

namespace App\Models;

use App\Utils\Database;

class OutboxMessage
{
    public static function create(string $queueName, string $routingKey, array $payload, int $maxAttempts = 3): array
    {
        $id = Database::insert('outbox_messages', [
            'queue_name' => $queueName,
            'routing_key' => $routingKey,
            'payload' => json_encode($payload),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
        ]);
        
        return self::findById($id);
    }
    
    public static function findById(int $id): ?array
    {
        $message = Database::fetchOne('SELECT * FROM outbox_messages WHERE id = :id', ['id' => $id]);
        
        if ($message && isset($message['payload'])) {
            $message['payload'] = json_decode($message['payload'], true);
        }
        
        return $message;
    }
    
    public static function findPending(int $limit = 100): array
    {
        $messages = Database::fetchAll(
            'SELECT * FROM outbox_messages 
             WHERE status = :status 
             OR (status = :failed AND next_retry_at <= NOW())
             ORDER BY created_at ASC 
             LIMIT :limit',
            [
                'status' => 'pending',
                'failed' => 'failed',
                'limit' => $limit,
            ]
        );
        
        foreach ($messages as &$message) {
            if (isset($message['payload'])) {
                $message['payload'] = json_decode($message['payload'], true);
            }
        }
        
        return $messages;
    }
    
    public static function markAsProcessing(int $id): bool
    {
        $sql = 'UPDATE outbox_messages 
                SET status = :status, attempts = attempts + 1 
                WHERE id = :id';
        
        $stmt = Database::query($sql, [
            'status' => 'processing',
            'id' => $id,
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    public static function markAsCompleted(int $id): bool
    {
        $updated = Database::update(
            'outbox_messages',
            [
                'status' => 'completed',
                'processed_at' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => $id]
        );
        
        return $updated > 0;
    }
    
    public static function markAsFailed(int $id, string $errorMessage): bool
    {
        $message = self::findById($id);
        
        if (!$message) {
            return false;
        }
        
        $data = [
            'status' => 'failed',
            'error_message' => $errorMessage,
        ];
        
        // Calcular próximo retry com backoff exponencial
        if ($message['attempts'] < $message['max_attempts']) {
            $delays = [5, 30, 120, 600, 3600]; // 5s, 30s, 2min, 10min, 1h
            $delayIndex = min($message['attempts'], count($delays) - 1);
            $delay = $delays[$delayIndex];
            
            $data['next_retry_at'] = date('Y-m-d H:i:s', time() + $delay);
        }
        
        $updated = Database::update('outbox_messages', $data, 'id = :id', ['id' => $id]);
        return $updated > 0;
    }
    
    public static function cleanupOld(int $daysOld = 7): int
    {
        $sql = 'DELETE FROM outbox_messages 
                WHERE status = :status 
                AND processed_at < NOW() - INTERVAL \':days days\'';
        
        $stmt = Database::query($sql, [
            'status' => 'completed',
            'days' => $daysOld,
        ]);
        
        return $stmt->rowCount();
    }
}

