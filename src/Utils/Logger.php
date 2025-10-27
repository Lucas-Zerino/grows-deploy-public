<?php

namespace App\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

class Logger
{
    private static ?MonologLogger $instance = null;
    
    private static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            $logFile = $_ENV['LOG_FILE'] ?? 'logs/app.log';
            $logLevel = $_ENV['LOG_LEVEL'] ?? 'INFO';
            
            self::$instance = new MonologLogger('growhub');
            
            // File handler com rotação diária
            $fileHandler = new RotatingFileHandler($logFile, 30, constant(MonologLogger::class . "::{$logLevel}"));
            $fileHandler->setFormatter(new JsonFormatter());
            self::$instance->pushHandler($fileHandler);
            
            // Console handler para desenvolvimento
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                $consoleHandler = new StreamHandler('php://stdout', constant(MonologLogger::class . "::{$logLevel}"));
                self::$instance->pushHandler($consoleHandler);
            }
        }
        
        return self::$instance;
    }
    
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
        self::logToDatabase('DEBUG', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
        self::logToDatabase('INFO', $message, $context);
    }
    
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
        self::logToDatabase('WARNING', $message, $context);
    }
    
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
        self::logToDatabase('ERROR', $message, $context);
    }
    
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
        self::logToDatabase('CRITICAL', $message, $context);
    }
    
    private static function logToDatabase(string $level, string $message, array $context): void
    {
        try {
            // Evitar recursão se o erro for do próprio banco
            if (isset($context['skip_db_log']) && $context['skip_db_log']) {
                return;
            }
            
            $sql = "INSERT INTO logs (level, context, message, payload, company_id, instance_id, message_id, created_at) 
                    VALUES (:level, :context, :message, :payload, :company_id, :instance_id, :message_id, NOW())";
            
            $params = [
                'level' => $level,
                'context' => $context['context'] ?? null,
                'message' => $message,
                'payload' => json_encode($context),
                'company_id' => $context['company_id'] ?? null,
                'instance_id' => $context['instance_id'] ?? null,
                'message_id' => $context['message_id'] ?? null,
            ];
            
            Database::query($sql, $params);
        } catch (\Exception $e) {
            // Silenciar erros de log no banco para evitar loops
        }
    }
}

