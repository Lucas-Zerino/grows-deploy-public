<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Models\Message;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Services\OutboxService;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;
use App\Utils\Database;

class MessageController
{
    public static function send(): void
    {
        $company = AuthMiddleware::authenticate();
        if (!$company) return;
        
        if (!RateLimitMiddleware::check($company['id'], 'send_message')) {
            return;
        }
        
        $input = Router::getJsonInput();
        
        // Validação
        $errors = [];
        if (empty($input['instance_id'])) {
            $errors['instance_id'] = 'Instance ID is required';
        }
        if (empty($input['phone_to'])) {
            $errors['phone_to'] = 'Phone number is required';
        }
        if (empty($input['message_type'])) {
            $input['message_type'] = 'text';
        }
        if ($input['message_type'] === 'text' && empty($input['content'])) {
            $errors['content'] = 'Content is required for text messages';
        }
        if ($input['message_type'] !== 'text' && empty($input['media_url'])) {
            $errors['media_url'] = 'Media URL is required for media messages';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        // Verificar se instância pertence à empresa
        $instance = Instance::findByIdAndCompany($input['instance_id'], $company['id']);
        
        if (!$instance) {
            Response::notFound('Instance not found or does not belong to your company');
            return;
        }
        
        if ($instance['status'] !== 'active') {
            Response::error('Instance is not active');
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Criar mensagem no banco
            $message = Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $company['id'],
                'direction' => 'outbound',
                'phone_to' => $input['phone_to'],
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => $input['message_type'],
                'content' => $input['content'] ?? null,
                'media_url' => $input['media_url'] ?? null,
                'status' => 'queued',
                'priority' => $input['priority'] ?? 5,
            ]);
            
            // Adicionar ao outbox para garantir entrega
            $priority = $input['priority'] ?? 5;
            $priorityName = $priority >= 8 ? 'high' : ($priority >= 4 ? 'normal' : 'low');
            
            $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
            $routingKey = "company.{$company['id']}.priority.{$priorityName}";
            
            OutboxService::enqueue($queueName, $routingKey, [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'provider_id' => $instance['provider_id'],
                'external_instance_id' => $instance['external_instance_id'],
                'phone_to' => $message['phone_to'],
                'message_type' => $message['message_type'],
                'content' => $message['content'],
                'media_url' => $message['media_url'],
                'priority' => $priority,
            ]);
            
            Database::commit();
            
            Logger::info('Message queued for sending', [
                'company_id' => $company['id'],
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
            ]);
            
            Response::created($message, 'Message queued for sending');
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Failed to queue message', [
                'company_id' => $company['id'],
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to queue message');
        }
    }
    
    public static function list(): void
    {
        $company = AuthMiddleware::authenticate();
        if (!$company) return;
        
        $params = Router::getQueryParams();
        $limit = min((int) ($params['limit'] ?? 50), 100);
        $offset = (int) ($params['offset'] ?? 0);
        $instanceId = $params['instance_id'] ?? null;
        
        if ($instanceId) {
            // Verificar se instância pertence à empresa
            $instance = Instance::findByIdAndCompany((int) $instanceId, $company['id']);
            if (!$instance) {
                Response::notFound('Instance not found');
                return;
            }
            
            $messages = Message::findByInstance((int) $instanceId, $limit, $offset);
        } else {
            $messages = Message::findByCompany($company['id'], $limit, $offset);
        }
        
        Response::success($messages);
    }
    
    public static function get(string $id): void
    {
        $company = AuthMiddleware::authenticate();
        if (!$company) return;
        
        $message = Message::findById((int) $id);
        
        if (!$message || $message['company_id'] != $company['id']) {
            Response::notFound('Message not found');
            return;
        }
        
        Response::success($message);
    }
}

