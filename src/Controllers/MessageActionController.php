<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\OutboxService;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;
use App\Utils\Database;

class MessageActionController
{
    /**
     * POST /message/presence
     * Enviar atualização de presença
     */
    public static function updatePresence(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['presence'])) {
            $errors['presence'] = 'Presença é obrigatória';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada');
                return;
            }
            
            // Mapear presença UazAPI para WAHA
            $presenceMap = [
                'composing' => 'startTyping',
                'recording' => 'startTyping', // WAHA não tem recording específico
                'paused' => 'stopTyping'
            ];
            
            $wahaMethod = $presenceMap[$input['presence']] ?? 'startTyping';
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'chatId' => $input['number'] . '@s.whatsapp.net',
                'session' => $instance['external_instance_id']
            ];
            
            // Enviar para fila de saída (presença não precisa salvar no banco)
            $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
            $routingKey = "company.{$instance['company_id']}.priority.normal";
            
            OutboxService::enqueue($queueName, $routingKey, [
                'instance_id' => $instance['id'],
                'provider_id' => $instance['provider_id'],
                'external_instance_id' => $instance['external_instance_id'],
                'phone_to' => $input['number'],
                'message_type' => 'presence',
                'waha_payload' => $wahaPayload,
                'waha_method' => $wahaMethod,
                'priority' => 8, // Alta prioridade para presença
            ]);
            
            Logger::info('Presença enfileirada', [
                'instance_id' => $instance['id'],
                'presence' => $input['presence'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'status' => 'queued',
                'message' => 'Presença enfileirada'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao enfileirar presença', [
                'error' => $e->getMessage(),
                'presence' => $input['presence'] ?? null,
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar presença');
        }
    }
    
    /**
     * POST /message/react
     * Enviar reação
     */
    public static function sendReaction(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['id'])) {
            $errors['id'] = 'ID da mensagem é obrigatório';
        }
        if (!isset($input['text'])) {
            $errors['text'] = 'Texto da reação é obrigatório';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada');
                return;
            }
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'messageId' => $input['id'],
                'reaction' => $input['text'],
                'session' => $instance['external_instance_id']
            ];
            
            // Enviar para fila de saída (reação não precisa salvar no banco)
            $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
            $routingKey = "company.{$instance['company_id']}.priority.normal";
            
            OutboxService::enqueue($queueName, $routingKey, [
                'instance_id' => $instance['id'],
                'provider_id' => $instance['provider_id'],
                'external_instance_id' => $instance['external_instance_id'],
                'phone_to' => $input['number'],
                'message_type' => 'reaction',
                'waha_payload' => $wahaPayload,
                'waha_method' => 'reaction',
                'priority' => 8, // Alta prioridade para reações
            ]);
            
            Logger::info('Reação enfileirada', [
                'instance_id' => $instance['id'],
                'message_id' => $input['id'],
                'reaction' => $input['text'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'status' => 'queued',
                'message' => 'Reação enfileirada'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao enfileirar reação', [
                'error' => $e->getMessage(),
                'message_id' => $input['id'] ?? null,
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar reação');
        }
    }
    
    /**
     * POST /message/markread
     * Marcar mensagens como lidas
     */
    public static function markAsRead(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['id']) || !is_array($input['id'])) {
            $errors['id'] = 'Lista de IDs é obrigatória';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada');
                return;
            }
            
            // Para cada mensagem, enviar comando de marcação como lida
            $markedMessages = [];
            
            foreach ($input['id'] as $messageId) {
                // Preparar payload para WAHA
                $wahaPayload = [
                    'messageId' => $messageId,
                    'session' => $instance['external_instance_id']
                ];
                
                // Enviar para fila de saída
                $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
                $routingKey = "company.{$instance['company_id']}.priority.normal";
                
                OutboxService::enqueue($queueName, $routingKey, [
                    'instance_id' => $instance['id'],
                    'provider_id' => $instance['provider_id'],
                    'external_instance_id' => $instance['external_instance_id'],
                    'message_type' => 'markread',
                    'waha_payload' => $wahaPayload,
                    'waha_method' => 'sendSeen',
                    'priority' => 7, // Prioridade alta para marcação como lida
                ]);
                
                $markedMessages[] = [
                    'id' => $messageId,
                    'timestamp' => time() * 1000
                ];
            }
            
            Logger::info('Mensagens marcadas como lidas', [
                'instance_id' => $instance['id'],
                'count' => count($input['id']),
            ]);
            
            Response::success([
                'success' => true,
                'message' => 'Messages marked as read',
                'markedMessages' => $markedMessages
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao marcar mensagens como lidas', [
                'error' => $e->getMessage(),
                'message_count' => count($input['id'] ?? []),
            ]);
            
            Response::serverError('Erro ao marcar mensagens como lidas');
        }
    }
    
    /**
     * POST /message/download
     * Baixar arquivo de uma mensagem
     */
    public static function downloadFile(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['id'])) {
            $errors['id'] = 'ID da mensagem é obrigatório';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada');
                return;
            }
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'messageId' => $input['id'],
                'session' => $instance['external_instance_id']
            ];
            
            // Adicionar parâmetros opcionais
            if (isset($input['return_base64'])) {
                $wahaPayload['return_base64'] = $input['return_base64'];
            }
            
            if (isset($input['return_link'])) {
                $wahaPayload['return_link'] = $input['return_link'];
            }
            
            // Enviar para fila de saída
            $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
            $routingKey = "company.{$instance['company_id']}.priority.normal";
            
            OutboxService::enqueue($queueName, $routingKey, [
                'instance_id' => $instance['id'],
                'provider_id' => $instance['provider_id'],
                'external_instance_id' => $instance['external_instance_id'],
                'message_type' => 'download',
                'waha_payload' => $wahaPayload,
                'waha_method' => 'downloadMedia',
                'priority' => 6, // Prioridade média para download
            ]);
            
            Logger::info('Download de arquivo enfileirado', [
                'instance_id' => $instance['id'],
                'message_id' => $input['id'],
            ]);
            
            Response::success([
                'status' => 'queued',
                'message' => 'Download enfileirado'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao enfileirar download', [
                'error' => $e->getMessage(),
                'message_id' => $input['id'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar download');
        }
    }
    
    /**
     * POST /message/forward
     * Encaminhar mensagem
     */
    public static function forwardMessage(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['messageId'])) {
            $errors['messageId'] = 'ID da mensagem é obrigatório';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada');
                return;
            }
            
            Database::beginTransaction();
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'chatId' => $input['number'] . '@s.whatsapp.net',
                'messageId' => $input['messageId'],
                'session' => $instance['external_instance_id']
            ];
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $input['number'],
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'forward',
                'content' => json_encode(['forwarded_message_id' => $input['messageId']]),
                'status' => 'queued',
                'priority' => 5,
            ]);
            
            Database::commit();
            
            // Enviar para fila de saída (após commit da transação)
            $queueName = $GLOBALS['rabbitmq_config']['exchanges']['outbound'];
            $routingKey = "company.{$instance['company_id']}.priority.normal";
            
            OutboxService::enqueue($queueName, $routingKey, [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'provider_id' => $instance['provider_id'],
                'external_instance_id' => $instance['external_instance_id'],
                'phone_to' => $input['number'],
                'message_type' => 'forward',
                'content' => $message['content'],
                'waha_payload' => $wahaPayload,
                'waha_method' => 'forwardMessage',
                'priority' => 5,
            ]);
            
            Logger::info('Encaminhamento enfileirado', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'forwarded_message_id' => $input['messageId'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'id' => $message['id'],
                'status' => 'queued',
                'message' => 'Encaminhamento enfileirado'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Erro ao enfileirar encaminhamento', [
                'error' => $e->getMessage(),
                'message_id' => $input['messageId'] ?? null,
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar encaminhamento');
        }
    }
}
