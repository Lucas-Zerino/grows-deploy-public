<?php

namespace App\Services;

use App\Utils\Database;
use App\Utils\Logger;

class QueueManagerService
{
    private static array $config = [];
    
    public static function init(): void
    {
        self::$config = require __DIR__ . '/../../config/rabbitmq.php';
    }
    
    public static function createCompanyQueues(int $companyId): bool
    {
        try {
            self::init();
            QueueService::init();
            
            $exchanges = self::$config['exchanges'];
            
            // Criar filas de saída (outbound) com prioridades
            $priorities = ['high' => 10, 'normal' => 5, 'low' => 1];
            
            foreach ($priorities as $priority => $maxPriority) {
                $queueName = "outbound.company.{$companyId}.priority.{$priority}";
                $dlqName = "{$queueName}.dlq";
                $routingKey = "company.{$companyId}.priority.{$priority}";
                
                // Criar DLQ
                QueueService::declareQueue($dlqName, true, []);
                QueueService::bindQueue($dlqName, $exchanges['dlq'], $dlqName);
                
                // Criar fila principal com configurações
                QueueService::declareQueue($queueName, true, [
                    'x-max-priority' => ['I', $maxPriority],
                    'x-dead-letter-exchange' => ['S', $exchanges['retry']],
                    'x-dead-letter-routing-key' => ['S', $dlqName],
                    'x-max-length' => ['I', 50000],
                    'x-overflow' => ['S', 'drop-head'],
                ]);
                
                QueueService::bindQueue($queueName, $exchanges['outbound'], $routingKey);
            }
            
            // Criar fila de entrada (inbound)
            $inboundQueue = "inbound.company.{$companyId}";
            QueueService::declareQueue($inboundQueue, true);
            QueueService::bindQueue($inboundQueue, $exchanges['inbound'], "company.{$companyId}");
            
            // Criar fila de eventos
            $eventsQueue = "events.company.{$companyId}";
            QueueService::declareQueue($eventsQueue, true);
            QueueService::bindQueue($eventsQueue, $exchanges['events'], "event.*.{$companyId}");
            
            // Registrar metadados das filas no banco
            foreach (array_merge(
                array_map(fn($p) => "outbound.company.{$companyId}.priority.{$p}", array_keys($priorities)),
                [$inboundQueue, $eventsQueue]
            ) as $queue) {
                Database::query(
                    'INSERT INTO queue_metadata (company_id, queue_name, last_activity, is_active) 
                     VALUES (:company_id, :queue_name, NOW(), true)
                     ON CONFLICT (queue_name) DO UPDATE SET last_activity = NOW(), is_active = true',
                    [
                        'company_id' => $companyId,
                        'queue_name' => $queue,
                    ]
                );
            }
            
            Logger::info("Company queues created successfully", ['company_id' => $companyId]);
            
            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to create company queues', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public static function deleteCompanyQueues(int $companyId): bool
    {
        try {
            $channel = QueueService::getChannel();
            
            // Buscar filas da empresa
            $queues = Database::fetchAll(
                'SELECT queue_name FROM queue_metadata WHERE company_id = :company_id',
                ['company_id' => $companyId]
            );
            
            foreach ($queues as $queue) {
                $channel->queue_delete($queue['queue_name']);
                Logger::info("Queue deleted: {$queue['queue_name']}");
            }
            
            // Remover metadados
            Database::delete('queue_metadata', 'company_id = :company_id', ['company_id' => $companyId]);
            
            Logger::info("Company queues deleted successfully", ['company_id' => $companyId]);
            
            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to delete company queues', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public static function cleanupInactiveQueues(int $inactiveDays = 7): int
    {
        $deletedCount = 0;
        
        try {
            $channel = QueueService::getChannel();
            
            $queues = Database::fetchAll(
                'SELECT queue_name FROM queue_metadata 
                 WHERE is_active = true 
                 AND last_activity < NOW() - INTERVAL \':days days\'',
                ['days' => $inactiveDays]
            );
            
            foreach ($queues as $queue) {
                // Verificar se fila está vazia
                $stats = QueueService::getQueueStats($queue['queue_name']);
                
                if ($stats && $stats['messages'] == 0) {
                    $channel->queue_delete($queue['queue_name']);
                    
                    Database::update(
                        'queue_metadata',
                        ['is_active' => false],
                        'queue_name = :queue_name',
                        ['queue_name' => $queue['queue_name']]
                    );
                    
                    $deletedCount++;
                    Logger::info("Inactive queue deleted: {$queue['queue_name']}");
                }
            }
            
            return $deletedCount;
        } catch (\Exception $e) {
            Logger::error('Failed to cleanup inactive queues', ['error' => $e->getMessage()]);
            return $deletedCount;
        }
    }
    
    public static function getQueueMetrics(): array
    {
        $queues = Database::fetchAll(
            'SELECT qm.*, c.name as company_name 
             FROM queue_metadata qm 
             LEFT JOIN companies c ON qm.company_id = c.id 
             WHERE qm.is_active = true 
             ORDER BY qm.last_activity DESC'
        );
        
        $metrics = [];
        
        foreach ($queues as $queue) {
            $stats = QueueService::getQueueStats($queue['queue_name']);
            
            $metrics[] = [
                'queue_name' => $queue['queue_name'],
                'company_id' => $queue['company_id'],
                'company_name' => $queue['company_name'],
                'messages' => $stats['messages'] ?? 0,
                'consumers' => $stats['consumers'] ?? 0,
                'last_activity' => $queue['last_activity'],
            ];
        }
        
        return $metrics;
    }
}

