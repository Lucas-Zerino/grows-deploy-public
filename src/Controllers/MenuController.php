<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\OutboxService;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;
use App\Utils\Database;

class MenuController
{
    /**
     * POST /send/menu
     * Enviar menu interativo (botões, lista, enquete ou carrossel)
     */
    public static function sendMenu(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['type'])) {
            $errors['type'] = 'Tipo de menu é obrigatório';
        }
        if (empty($input['text'])) {
            $errors['text'] = 'Texto é obrigatório';
        }
        if (empty($input['choices'])) {
            $errors['choices'] = 'Escolhas são obrigatórias';
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
            
            // Preparar payload base para WAHA
            $wahaPayload = [
                'chatId' => $input['number'] . '@s.whatsapp.net',
                'session' => $instance['external_instance_id']
            ];
            
            // Processar por tipo
            switch ($input['type']) {
                case 'button':
                    $wahaPayload = self::processButtons($input, $wahaPayload);
                    $wahaMethod = 'sendButtons';
                    break;
                    
                case 'list':
                    $wahaPayload = self::processList($input, $wahaPayload);
                    $wahaMethod = 'sendList';
                    break;
                    
                case 'poll':
                    $wahaPayload = self::processPoll($input, $wahaPayload);
                    $wahaMethod = 'sendPoll';
                    break;
                    
                case 'carousel':
                    $wahaPayload = self::processCarousel($input, $wahaPayload);
                    $wahaMethod = 'sendButtons'; // WAHA usa sendButtons para carrossel
                    break;
                    
                default:
                    Response::validationError(['type' => 'Tipo de menu não suportado']);
                    return;
            }
            
            // Adicionar campos opcionais
            if (!empty($input['replyid'])) {
                $wahaPayload['reply_to'] = $input['replyid'];
            }
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $input['number'],
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'menu_' . $input['type'],
                'content' => json_encode([
                    'type' => $input['type'],
                    'text' => $input['text'],
                    'choices' => $input['choices'],
                    'footerText' => $input['footerText'] ?? null,
                    'listButton' => $input['listButton'] ?? null,
                    'selectableCount' => $input['selectableCount'] ?? null,
                ]),
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
                'message_type' => 'menu_' . $input['type'],
                'content' => $message['content'],
                'waha_payload' => $wahaPayload,
                'waha_method' => $wahaMethod,
                'priority' => 5,
            ]);
            
            Logger::info('Menu interativo enfileirado', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'type' => $input['type'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'id' => $message['id'],
                'status' => 'queued',
                'message' => 'Menu interativo enfileirado'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Erro ao enfileirar menu interativo', [
                'error' => $e->getMessage(),
                'type' => $input['type'] ?? null,
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar menu interativo');
        }
    }
    
    /**
     * POST /send/carousel
     * Enviar carrossel de mídia com botões
     */
    public static function sendCarousel(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['text'])) {
            $errors['text'] = 'Texto é obrigatório';
        }
        if (empty($input['carousel'])) {
            $errors['carousel'] = 'Carrossel é obrigatório';
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
                'session' => $instance['external_instance_id'],
                'text' => $input['text']
            ];
            
            // Processar carrossel
            $wahaPayload = self::processCarouselItems($input['carousel'], $wahaPayload);
            
            // Adicionar campos opcionais
            if (!empty($input['replyid'])) {
                $wahaPayload['reply_to'] = $input['replyid'];
            }
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $input['number'],
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'carousel',
                'content' => json_encode([
                    'text' => $input['text'],
                    'carousel' => $input['carousel'],
                ]),
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
                'message_type' => 'carousel',
                'content' => $message['content'],
                'waha_payload' => $wahaPayload,
                'waha_method' => 'sendButtons',
                'priority' => 5,
            ]);
            
            Logger::info('Carrossel enfileirado', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'id' => $message['id'],
                'status' => 'queued',
                'message' => 'Carrossel enfileirado'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Erro ao enfileirar carrossel', [
                'error' => $e->getMessage(),
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar carrossel');
        }
    }
    
    /**
     * POST /send/location-button
     * Solicitar localização do usuário
     */
    public static function requestLocation(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['number'])) {
            $errors['number'] = 'Número é obrigatório';
        }
        if (empty($input['text'])) {
            $errors['text'] = 'Texto é obrigatório';
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
            
            // Preparar payload para WAHA (usar botões com solicitação de localização)
            $wahaPayload = [
                'chatId' => $input['number'] . '@s.whatsapp.net',
                'session' => $instance['external_instance_id'],
                'text' => $input['text'],
                'buttons' => [
                    [
                        'id' => 'request_location',
                        'text' => '📍 Compartilhar Localização',
                        'type' => 'LOCATION'
                    ]
                ]
            ];
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $input['number'],
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'location_request',
                'content' => $input['text'],
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
                'message_type' => 'location_request',
                'content' => $input['text'],
                'waha_payload' => $wahaPayload,
                'waha_method' => 'sendButtons',
                'priority' => 5,
            ]);
            
            Logger::info('Solicitação de localização enfileirada', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'number' => $input['number'],
            ]);
            
            Response::success([
                'id' => $message['id'],
                'status' => 'queued',
                'message' => 'Solicitação de localização enfileirada'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Erro ao enfileirar solicitação de localização', [
                'error' => $e->getMessage(),
                'number' => $input['number'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar solicitação de localização');
        }
    }
    
    /**
     * POST /send/status
     * Enviar Stories (Status)
     */
    public static function sendStatus(): void
    {
        $input = Router::getJsonInput();
        
        // Validação básica
        $errors = [];
        if (empty($input['type'])) {
            $errors['type'] = 'Tipo de status é obrigatório';
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
                'session' => $instance['external_instance_id']
            ];
            
            // Processar por tipo de status
            switch ($input['type']) {
                case 'text':
                    $wahaPayload['text'] = $input['text'] ?? '';
                    // WAHA não suporta cores de fundo para status de texto
                    break;
                    
                case 'image':
                    $wahaPayload['image'] = $input['file'];
                    if (!empty($input['text'])) {
                        $wahaPayload['caption'] = $input['text'];
                    }
                    break;
                    
                case 'video':
                    $wahaPayload['video'] = $input['file'];
                    if (!empty($input['text'])) {
                        $wahaPayload['caption'] = $input['text'];
                    }
                    break;
                    
                case 'audio':
                    $wahaPayload['audio'] = $input['file'];
                    break;
            }
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => 'status', // Status não tem destinatário específico
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'status_' . $input['type'],
                'content' => json_encode([
                    'type' => $input['type'],
                    'text' => $input['text'] ?? null,
                    'file' => $input['file'] ?? null,
                    'background_color' => $input['background_color'] ?? null,
                    'font' => $input['font'] ?? null,
                ]),
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
                'phone_to' => 'status',
                'message_type' => 'status_' . $input['type'],
                'content' => $message['content'],
                'waha_payload' => $wahaPayload,
                'waha_method' => 'sendStatus',
                'priority' => 5,
            ]);
            
            Logger::info('Status enfileirado', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'type' => $input['type'],
            ]);
            
            Response::success([
                'id' => $message['id'],
                'status' => 'queued',
                'message' => 'Status enfileirado'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error('Erro ao enfileirar status', [
                'error' => $e->getMessage(),
                'type' => $input['type'] ?? null,
            ]);
            
            Response::serverError('Erro ao enfileirar status');
        }
    }
    
    // Métodos auxiliares para processar diferentes tipos de menu
    
    private static function processButtons(array $input, array $wahaPayload): array
    {
        $wahaPayload['text'] = $input['text'];
        $wahaPayload['buttons'] = [];
        
        foreach ($input['choices'] as $choice) {
            $button = self::parseButtonChoice($choice);
            $wahaPayload['buttons'][] = $button;
        }
        
        if (!empty($input['footerText'])) {
            $wahaPayload['footer'] = $input['footerText'];
        }
        
        return $wahaPayload;
    }
    
    private static function processList(array $input, array $wahaPayload): array
    {
        $wahaPayload['text'] = $input['text'];
        $wahaPayload['sections'] = [];
        
        $currentSection = null;
        foreach ($input['choices'] as $choice) {
            if (strpos($choice, '[') === 0 && strpos($choice, ']') === strlen($choice) - 1) {
                // Nova seção
                $currentSection = [
                    'title' => trim($choice, '[]'),
                    'rows' => []
                ];
                $wahaPayload['sections'][] = $currentSection;
            } else {
                // Item da lista
                $item = self::parseListItem($choice);
                if ($currentSection) {
                    $currentSection['rows'][] = $item;
                }
            }
        }
        
        if (!empty($input['listButton'])) {
            $wahaPayload['buttonText'] = $input['listButton'];
        }
        
        if (!empty($input['footerText'])) {
            $wahaPayload['footer'] = $input['footerText'];
        }
        
        return $wahaPayload;
    }
    
    private static function processPoll(array $input, array $wahaPayload): array
    {
        $wahaPayload['poll'] = [
            'name' => $input['text'],
            'options' => $input['choices']
        ];
        
        if (!empty($input['selectableCount'])) {
            $wahaPayload['poll']['multipleAnswers'] = $input['selectableCount'] > 1;
        }
        
        return $wahaPayload;
    }
    
    private static function processCarousel(array $input, array $wahaPayload): array
    {
        $wahaPayload['text'] = $input['text'];
        $wahaPayload['buttons'] = [];
        
        foreach ($input['choices'] as $choice) {
            if (strpos($choice, '[') === 0) {
                // Texto do cartão - ignorar por enquanto
                continue;
            } elseif (strpos($choice, '{') === 0) {
                // Imagem do cartão - ignorar por enquanto
                continue;
            } else {
                // Botão do cartão
                $button = self::parseButtonChoice($choice);
                $wahaPayload['buttons'][] = $button;
            }
        }
        
        return $wahaPayload;
    }
    
    private static function processCarouselItems(array $carousel, array $wahaPayload): array
    {
        $wahaPayload['buttons'] = [];
        
        foreach ($carousel as $item) {
            if (isset($item['buttons'])) {
                foreach ($item['buttons'] as $button) {
                    $wahaPayload['buttons'][] = [
                        'id' => $button['id'],
                        'text' => $button['text'],
                        'type' => $button['type']
                    ];
                }
            }
        }
        
        return $wahaPayload;
    }
    
    private static function parseButtonChoice(string $choice): array
    {
        // Formato: "texto|id" ou "texto" (id = texto)
        $parts = explode('|', $choice, 2);
        $text = trim($parts[0]);
        $id = isset($parts[1]) ? trim($parts[1]) : $text;
        
        // Detectar tipo de botão
        if (strpos($id, 'http') === 0) {
            return ['id' => $id, 'text' => $text, 'type' => 'URL'];
        } elseif (strpos($id, 'call:') === 0) {
            return ['id' => substr($id, 5), 'text' => $text, 'type' => 'CALL'];
        } elseif (strpos($id, 'copy:') === 0) {
            return ['id' => substr($id, 5), 'text' => $text, 'type' => 'COPY'];
        } else {
            return ['id' => $id, 'text' => $text, 'type' => 'REPLY'];
        }
    }
    
    private static function parseListItem(string $choice): array
    {
        // Formato: "texto|id|descrição"
        $parts = explode('|', $choice, 3);
        
        return [
            'id' => $parts[1] ?? $parts[0],
            'title' => trim($parts[0]),
            'description' => $parts[2] ?? ''
        ];
    }
}
