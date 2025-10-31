<?php

namespace App\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class Logger
{
    private static ?MonologLogger $instance = null;
    
    private static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            $logLevel = $_ENV['LOG_LEVEL'] ?? 'INFO';
            $logLevelConstant = constant(MonologLogger::class . "::{$logLevel}");
            
            self::$instance = new MonologLogger('growhub');
            
            // Handler RabbitMQ - publica logs na fila (assíncrono, não bloqueia)
            $exchange = $_ENV['LOG_EXCHANGE'] ?? 'logs.exchange';
            try {
                $queueHandler = new LogsQueueHandler($exchange, $logLevelConstant);
                self::$instance->pushHandler($queueHandler);
            } catch (\Exception $e) {
                // Se falhar ao criar handler, continuar sem ele (logs só vão para stdout/stderr)
                error_log("Warning: Failed to create logs queue handler: " . $e->getMessage());
            }
            
            // Console handler para desenvolvimento (stdout/stderr)
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                $stdoutHandler = new StreamHandler('php://stdout', $logLevelConstant);
                $stdoutHandler->setFormatter(new JsonFormatter());
                self::$instance->pushHandler($stdoutHandler);
                
                // stderr para WARNING e acima
                $stderrHandler = new StreamHandler('php://stderr', \Monolog\Level::Warning);
                $stderrHandler->setFormatter(new JsonFormatter());
                self::$instance->pushHandler($stderrHandler);
            }
        }
        
        return self::$instance;
    }
    
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }
    
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }
    
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }
    
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
    }
}

