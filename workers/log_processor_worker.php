<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Utils\Database;
use App\Utils\Logger;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Log Processor Worker...\n";
// Não usar Logger aqui para evitar loop - apenas echo

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Log Processor Worker shutting down', ['skip_db_log' => true]);
    QueueService::close();
    exit(0);
});

pcntl_signal(SIGINT, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Log Processor Worker shutting down', ['skip_db_log' => true]);
    QueueService::close();
    exit(0);
});

try {
    QueueService::init();
    
    $config = require __DIR__ . '/../config/rabbitmq.php';
    $logsQueue = $config['global_queues']['logs'];
    $logsExchange = $config['exchanges']['logs'];
    
    // Garantir que exchange e queue existem
    QueueService::declareExchange($logsExchange, 'topic', true);
    QueueService::declareQueue($logsQueue, true);
    QueueService::bindQueue($logsQueue, $logsExchange, 'logs.*');
    
    $batchSize = (int)($_ENV['LOG_BATCH_SIZE'] ?? 100);
    $batch = [];
    $lastInsert = time();
    $batchTimeout = 5; // Inserir batch a cada 5 segundos se não encher
    
    echo "Consuming logs from queue: {$logsQueue}\n";
    echo "Batch size: {$batchSize}\n";
    echo "Batch timeout: {$batchTimeout}s\n\n";
    
    $callback = function ($data, $msg) use (&$batch, &$lastInsert, $batchSize, $batchTimeout) {
        pcntl_signal_dispatch();
        
        try {
            // Extrair dados do log (que vem do RabbitMQ como array)
            $logData = is_array($data) ? $data : json_decode($data, true);
            
            if (!$logData) {
                // Mensagem inválida - descartar (return true = ack será feito pelo QueueService)
                return true;
            }
            
            $batch[] = $logData;
            $currentTime = time();
            
            // Processar batch se atingir tamanho ou timeout
            $shouldProcess = count($batch) >= $batchSize || 
                           ($currentTime - $lastInsert) >= $batchTimeout;
            
            if ($shouldProcess && !empty($batch)) {
                processBatch($batch);
                $batch = [];
                $lastInsert = $currentTime;
            }
            
            // Retornar true para indicar sucesso (ack será feito pelo QueueService)
            return true;
            
        } catch (\Exception $e) {
            error_log("Error processing log: " . $e->getMessage());
            
            // Retornar false para indicar erro (nack será feito pelo QueueService)
            return false;
        }
    };
    
    // Consumir da fila com prefetch maior para processar em batch
    QueueService::consume($logsQueue, $callback, $batchSize);
    
} catch (\Exception $e) {
    // Não usar Logger aqui - apenas echo para evitar loop infinito
    error_log("Log Processor Worker failed: " . $e->getMessage());
    
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
}

/**
 * Processar batch de logs inserindo múltiplos de uma vez
 */
function processBatch(array $logs): void
{
    if (empty($logs)) {
        return;
    }
    
    try {
        $db = Database::getInstance();
        
        // Preparar valores para inserção em batch
        $values = [];
        $params = [];
        $paramIndex = 1;
        
        foreach ($logs as $log) {
            $level = $log['level'] ?? 'INFO';
            $message = $log['message'] ?? '';
            $context = $log['context'] ?? [];
            $timestamp = $log['timestamp'] ?? time();
            $datetime = $log['datetime'] ?? date('Y-m-d H:i:s');
            
            // Extrair informações do contexto
            $contextStr = $context['context'] ?? null;
            $companyId = $context['company_id'] ?? null;
            $instanceId = $context['instance_id'] ?? null;
            $messageId = $context['message_id'] ?? null;
            
            // Payload JSON
            $payload = json_encode($context);
            
            $values[] = "(:level{$paramIndex}, :context{$paramIndex}, :message{$paramIndex}, :payload{$paramIndex}, :company_id{$paramIndex}, :instance_id{$paramIndex}, :message_id{$paramIndex}, :created_at{$paramIndex})";
            
            $params["level{$paramIndex}"] = $level;
            $params["context{$paramIndex}"] = $contextStr;
            $params["message{$paramIndex}"] = $message;
            $params["payload{$paramIndex}"] = $payload;
            $params["company_id{$paramIndex}"] = $companyId;
            $params["instance_id{$paramIndex}"] = $instanceId;
            $params["message_id{$paramIndex}"] = $messageId;
            $params["created_at{$paramIndex}"] = $datetime;
            
            $paramIndex++;
        }
        
        // Inserção em batch (muito mais rápido que múltiplos inserts)
        $sql = "INSERT INTO logs (level, context, message, payload, company_id, instance_id, message_id, created_at) 
                VALUES " . implode(', ', $values);
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $count = count($logs);
        echo "Processed batch of {$count} logs\n";
        
    } catch (\Exception $e) {
        echo "Error inserting batch: {$e->getMessage()}\n";
        // Logar erro mas não quebrar worker
        error_log("Log Processor Worker: Failed to insert batch - " . $e->getMessage());
    }
}

// Registrar função de shutdown para processar batch pendente
$shutdownCallback = function() use (&$batch) {
    if (!empty($batch)) {
        echo "Processing final batch on shutdown...\n";
        try {
            processBatch($batch);
        } catch (\Exception $e) {
            error_log("Error processing final batch: " . $e->getMessage());
        }
    }
};

register_shutdown_function($shutdownCallback);

