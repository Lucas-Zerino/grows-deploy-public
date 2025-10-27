<?php

namespace App\Middleware;

use App\Utils\Database;
use App\Utils\Response;
use App\Utils\Logger;

class RateLimitMiddleware
{
    public static function check(int $companyId, string $endpoint): bool
    {
        $limitPerMinute = (int) ($_ENV['RATE_LIMIT_PER_MINUTE'] ?? 60);
        $limitPerHour = (int) ($_ENV['RATE_LIMIT_PER_HOUR'] ?? 1000);
        
        // Verificar limite por minuto
        if (!self::checkWindow($companyId, $endpoint, 60, $limitPerMinute)) {
            Logger::warning('Rate limit exceeded (minute)', [
                'company_id' => $companyId,
                'endpoint' => $endpoint,
            ]);
            
            Response::tooManyRequests('Rate limit exceeded. Try again later.');
            return false;
        }
        
        // Verificar limite por hora
        if (!self::checkWindow($companyId, $endpoint, 3600, $limitPerHour)) {
            Logger::warning('Rate limit exceeded (hour)', [
                'company_id' => $companyId,
                'endpoint' => $endpoint,
            ]);
            
            Response::tooManyRequests('Hourly rate limit exceeded. Try again later.');
            return false;
        }
        
        // Incrementar contador
        self::increment($companyId, $endpoint);
        
        return true;
    }
    
    private static function checkWindow(
        int $companyId,
        string $endpoint,
        int $windowSeconds,
        int $limit
    ): bool {
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
        
        $sql = 'SELECT COALESCE(SUM(count), 0) as total 
                FROM rate_limits 
                WHERE company_id = :company_id 
                AND endpoint = :endpoint 
                AND window_start >= :window_start';
        
        $result = Database::fetchOne($sql, [
            'company_id' => $companyId,
            'endpoint' => $endpoint,
            'window_start' => $windowStart,
        ]);
        
        $total = (int) ($result['total'] ?? 0);
        
        return $total < $limit;
    }
    
    private static function increment(int $companyId, string $endpoint): void
    {
        $windowStart = date('Y-m-d H:i:00'); // Agrupa por minuto
        
        $sql = 'INSERT INTO rate_limits (company_id, endpoint, count, window_start) 
                VALUES (:company_id, :endpoint, 1, :window_start)
                ON CONFLICT (company_id, endpoint, window_start) 
                DO UPDATE SET count = rate_limits.count + 1';
        
        Database::query($sql, [
            'company_id' => $companyId,
            'endpoint' => $endpoint,
            'window_start' => $windowStart,
        ]);
    }
    
    public static function cleanup(): int
    {
        // Limpar registros com mais de 24 horas
        $sql = 'DELETE FROM rate_limits WHERE window_start < NOW() - INTERVAL \'24 hours\'';
        $stmt = Database::query($sql);
        
        $deleted = $stmt->rowCount();
        
        if ($deleted > 0) {
            Logger::info("Cleaned up {$deleted} old rate limit records");
        }
        
        return $deleted;
    }
}

