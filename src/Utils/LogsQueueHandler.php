<?php

namespace App\Utils;

use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use App\Services\QueueService;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Handler customizado do Monolog que publica logs na fila RabbitMQ
 * Não bloqueia a aplicação se RabbitMQ estiver indisponível
 */
class LogsQueueHandler extends AbstractProcessingHandler
{
    private string $exchange;
    private bool $initialized = false;
    
    public function __construct(string $exchange = 'logs.exchange', $level = \Monolog\Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->exchange = $exchange;
    }
    
    protected function write(LogRecord $record): void
    {
        // Inicializar RabbitMQ apenas uma vez (lazy initialization)
        if (!$this->initialized) {
            try {
                QueueService::init();
                QueueService::declareExchange($this->exchange, 'topic', true);
                $this->initialized = true;
            } catch (\Exception $e) {
                // Se falhar ao inicializar, logar em stderr mas não quebrar aplicação
                error_log("Warning: Failed to initialize logs queue handler: " . $e->getMessage());
                return;
            }
        }
        
        // Preparar dados do log
        $logData = [
            'level' => $record->level->getName(),
            'level_value' => $record->level->value,
            'message' => $record->message,
            'context' => $record->context,
            'datetime' => $record->datetime->format('Y-m-d H:i:s.u'),
            'timestamp' => $record->datetime->getTimestamp(),
            'channel' => $record->channel,
        ];
        
        // Routing key baseado no nível: logs.info, logs.error, logs.debug, etc.
        $routingKey = 'logs.' . strtolower($record->level->getName());
        
        // Prioridade baseada no nível (crítico > erro > warning > info > debug)
        $priority = match($record->level->value) {
            \Monolog\Level::Critical->value => 10,
            \Monolog\Level::Error->value => 8,
            \Monolog\Level::Warning->value => 6,
            \Monolog\Level::Info->value => 5,
            default => 3,
        };
        
        try {
            // Publicar na fila sem usar Logger (para evitar loop infinito)
            // Usar o canal diretamente sem passar pelo QueueService que loga
            $channel = QueueService::getChannel();
            
            $message = new AMQPMessage(
                json_encode($logData),
                [
                    'delivery_mode' => \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'priority' => $priority,
                    'content_type' => 'application/json',
                ]
            );
            
            $channel->basic_publish($message, $this->exchange, $routingKey);
        } catch (\Exception $e) {
            // Se falhar, apenas logar em stderr (não usar Logger para evitar loop)
            error_log("Warning: Failed to publish log to queue: " . $e->getMessage());
        }
    }
}

