<?php

namespace App\Utils;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
    
    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
    
    public static function created(mixed $data = null, string $message = 'Resource created'): void
    {
        self::success($data, $message, 201);
    }
    
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }
    
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }
    
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }
    
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }
    
    public static function validationError(array $errors): void
    {
        self::error('Validation failed', 422, $errors);
    }
    
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500);
    }
    
    public static function tooManyRequests(string $message = 'Too many requests'): void
    {
        self::error($message, 429);
    }
}

