<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use App\Utils\Logger;

class QueueService
{
    private static ?AMQPStreamConnection $connection = null;
    private static ?AMQPChannel $channel = null;
    private static array $config = [];
    
    public static function init(): void
    {
        if (self::$connection !== null) {
            return;
        }
        
        self::$config = require __DIR__ . '/../../config/rabbitmq.php';
        
        try {
            self::$connection = new AMQPStreamConnection(
                self::$config['host'],
                self::$config['port'],
                self::$config['user'],
                self::$config['password'],
                self::$config['vhost']
            );
            
            self::$channel = self::$connection->channel();
            
            Logger::info('RabbitMQ connection established');
        } catch (\Exception $e) {
            Logger::critical('RabbitMQ connection failed', [
                'error' => $e->getMessage(),
                'skip_db_log' => true,
            ]);
            throw $e;
        }
    }
    
    public static function getChannel(): AMQPChannel
    {
        if (self::$channel === null) {
            self::init();
        }
        
        return self::$channel;
    }
    
    /**
     * Declare an exchange
     */
    public static function declareExchange(
        string $exchangeName,
        string $type = 'topic',
        bool $durable = true
    ): void {
        $channel = self::getChannel();
        
        $channel->exchange_declare(
            $exchangeName,
            $type,
            false,    // passive
            $durable, // durable
            false     // auto_delete
        );
        
        Logger::debug("Exchange declared", [
            'exchange' => $exchangeName,
            'type' => $type,
        ]);
    }
    
    /**
     * Declare a queue
     */
    public static function declareQueue(
        string $queueName,
        bool $durable = true,
        array $arguments = []
    ): void {
        $channel = self::getChannel();
        
        $channel->queue_declare(
            $queueName,
            false,    // passive
            $durable, // durable
            false,    // exclusive
            false,    // auto_delete
            false,    // nowait
            $arguments
        );
        
        Logger::debug("Queue declared", ['queue' => $queueName]);
    }
    
    /**
     * Bind a queue to an exchange
     */
    public static function bindQueue(
        string $queueName,
        string $exchangeName,
        string $routingKey = ''
    ): void {
        $channel = self::getChannel();
        
        $channel->queue_bind($queueName, $exchangeName, $routingKey);
        
        Logger::debug("Queue bound to exchange", [
            'queue' => $queueName,
            'exchange' => $exchangeName,
            'routing_key' => $routingKey,
        ]);
    }
    
    public static function publish(
        string $exchange,
        string $routingKey,
        array $data,
        int $priority = 5
    ): bool {
        try {
            $channel = self::getChannel();
            
            $message = new AMQPMessage(
                json_encode($data),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'priority' => $priority,
                    'content_type' => 'application/json',
                ]
            );
            
            $channel->basic_publish($message, $exchange, $routingKey);
            
            Logger::debug('Message published to queue', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'priority' => $priority,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to publish message to queue', [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Alias para publish() - Publicar mensagem em um exchange
     */
    public static function publishToExchange(
        string $exchangeName,
        array $data,
        string $routingKey,
        int $priority = 5
    ): bool {
        return self::publish($exchangeName, $routingKey, $data, $priority);
    }
    
    public static function consume(
        string $queueName,
        callable $callback,
        int $prefetchCount = 5
    ): void {
        $channel = self::getChannel();
        $channel->basic_qos(null, $prefetchCount, null);
        
        $consumerCallback = function (AMQPMessage $msg) use ($callback) {
            try {
                $data = json_decode($msg->body, true);
                
                Logger::debug('Message consumed from queue', [
                    'queue' => $msg->getRoutingKey(),
                ]);
                
                $result = $callback($data, $msg);
                
                if ($result === true) {
                    $msg->ack();
                    Logger::debug('Message acknowledged');
                } else {
                    $msg->nack(false, true); // Requeue
                    Logger::warning('Message rejected and requeued');
                }
            } catch (\Exception $e) {
                Logger::error('Error processing message', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Incrementar contador de tentativas
                $retryCount = (int) ($msg->get_properties()['application_headers']['x-retry-count'] ?? 0);
                $retryCount++;
                
                if ($retryCount >= 3) {
                    $msg->nack(false, false); // Enviar para DLQ
                    Logger::error('Message sent to DLQ after max retries');
                } else {
                    $msg->nack(false, true); // Requeue
                    Logger::warning('Message requeued for retry', ['retry_count' => $retryCount]);
                }
            }
        };
        
        $channel->basic_consume($queueName, '', false, false, false, false, $consumerCallback);
        
        Logger::info("Started consuming queue: {$queueName}");
        
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
    
    /**
     * Consume from exchange with routing pattern (for dynamic queues)
     * Creates a temporary exclusive queue and binds to exchange
     */
    public static function consumeFromExchange(
        string $exchangeName,
        string $routingPattern,
        callable $callback,
        int $prefetchCount = 1
    ): void {
        $channel = self::getChannel();
        
        // Declarar fila exclusiva (auto-delete quando worker desconectar)
        list($queueName, ,) = $channel->queue_declare(
            '', // Nome vazio = fila temporária com nome gerado
            false, // passive
            false, // durable (não precisa persistir fila temporária)
            true,  // exclusive (só este worker usa)
            true   // auto_delete (remove quando worker desconectar)
        );
        
        // Bind na exchange com routing pattern
        $channel->queue_bind($queueName, $exchangeName, $routingPattern);
        
        Logger::info("Worker consuming from exchange", [
            'exchange' => $exchangeName,
            'routing_pattern' => $routingPattern,
            'temp_queue' => $queueName,
        ]);
        
        // Set prefetch
        $channel->basic_qos(0, $prefetchCount, false);
        
        // Consumer callback
        $consumerCallback = function ($msg) use ($callback) {
            try {
                $data = json_decode($msg->body, true);
                
                // Call user callback
                $result = $callback($data, $msg);
                
                if ($result === true || $result === null) {
                    $msg->ack();
                    Logger::debug('Message acknowledged');
                } else {
                    $msg->nack(false, true); // Requeue
                    Logger::warning('Message rejected and requeued');
                }
            } catch (\Exception $e) {
                Logger::error('Error processing message', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Retry logic
                $retryCount = (int) ($msg->get_properties()['application_headers']['x-retry-count'] ?? 0);
                $retryCount++;
                
                if ($retryCount >= 3) {
                    $msg->nack(false, false); // Send to DLQ
                    Logger::error('Message sent to DLQ after max retries');
                } else {
                    $msg->nack(false, true); // Requeue
                    Logger::warning('Message requeued for retry', ['retry_count' => $retryCount]);
                }
            }
        };
        
        $channel->basic_consume($queueName, '', false, false, false, false, $consumerCallback);
        
        Logger::info("Started consuming from exchange");
        
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
    
    public static function close(): void
    {
        if (self::$channel !== null) {
            self::$channel->close();
            self::$channel = null;
        }
        
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
        
        Logger::info('RabbitMQ connection closed');
    }
    
    public static function getQueueStats(string $queueName): ?array
    {
        try {
            $channel = self::getChannel();
            list($queue, $messageCount, $consumerCount) = $channel->queue_declare($queueName, true);
            
            return [
                'queue' => $queue,
                'messages' => $messageCount,
                'consumers' => $consumerCount,
            ];
        } catch (\Exception $e) {
            Logger::error('Failed to get queue stats', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}

