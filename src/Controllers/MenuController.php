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
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            
            if (!$instance) {
                Response::notFound('Instância não encontrada');
                return;
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $number = $input['number'] ?? $input['to'] ?? '';
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $number);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                Response::error($errorMessage, 400);
                return;
            }
            
            // Usar número validado
            $validatedNumber = $validation['validated_number'];
            
            Database::beginTransaction();
            
            // Preparar payload base para WAHA
            $wahaPayload = [
                'chatId' => $validatedNumber . '@s.whatsapp.net',
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
                    // Carrossel agora é tratado no método sendCarousel separadamente
                    Response::validationError(['type' => 'Use /send/carousel para enviar carrossel']);
                    return;
                    
                default:
                    Response::validationError(['type' => 'Tipo de menu não suportado']);
                    return;
            }
            
            // Adicionar campos opcionais
            if (!empty($input['replyid'])) {
                $wahaPayload['reply_to'] = $input['replyid'];
            }
            
            // Criar mensagem no banco
            // message_type deve ser um dos valores permitidos: text, image, video, audio, document, location, contact
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $validatedNumber,
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'text', // Menu é enviado como text com botões/interatividade
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
                'phone_to' => $validatedNumber,
                'message_type' => 'text', // Menu é enviado como text com botões
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
        
        // Validação básica - aceita tanto formato antigo quanto novo
        $errors = [];
        
        // Aceita 'number' ou 'to'
        $number = $input['number'] ?? $input['to'] ?? null;
        if (empty($number)) {
            $errors['number'] = 'Número é obrigatório (use "number" ou "to")';
        }
        
        // Aceita 'text' ou usa 'description' se não tiver text
        $text = $input['text'] ?? $input['description'] ?? null;
        if (empty($text)) {
            $errors['text'] = 'Texto/Descrição é obrigatório (use "text" ou "description")';
        }
        
        // Aceita 'carousel' ou 'images' (formato novo)
        $carousel = $input['carousel'] ?? null;
        $images = $input['images'] ?? null;
        
        if (empty($carousel) && empty($images)) {
            $errors['carousel'] = 'Carrossel é obrigatório (use "carousel" ou "images")';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            
            if (!$instance) {
                Response::notFound('Instância não encontrada');
                return;
            }
            
            // Normalizar dados
            $number = $input['number'] ?? $input['to'] ?? '';
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $number);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                Response::error($errorMessage, 400);
                return;
            }
            
            // Usar número validado
            $validatedNumber = $validation['validated_number'];
            
            $text = $input['text'] ?? $input['description'] ?? '';
            $carouselData = $input['carousel'] ?? null;
            $images = $input['images'] ?? null;
            
            // Se tiver 'images' (formato novo), converter para formato interno
            if ($images && !$carouselData) {
                $carouselData = [];
                foreach ($images as $image) {
                    $carouselData[] = [
                        'image' => $image['image'] ?? $image['imageUrl'] ?? '',
                        'title' => $image['title'] ?? '',
                        'description' => $image['description'] ?? '',
                        'button' => $image['button'] ?? null
                    ];
                }
            }
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'chatId' => $validatedNumber . '@s.whatsapp.net',
                'session' => $instance['external_instance_id'],
                'body' => $text // WAHA usa 'body' para sendButtons
            ];
            
            // Processar carrossel
            $wahaPayload = self::processCarouselItems($carouselData, $wahaPayload);
            
            // Adicionar campos opcionais
            if (!empty($input['replyid'])) {
                $wahaPayload['reply_to'] = $input['replyid'];
            }
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $validatedNumber,
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'text', // Carrossel é enviado como text com botões
                'content' => json_encode([
                    'text' => $text,
                    'carousel' => $carouselData,
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
                'phone_to' => $validatedNumber,
                'message_type' => 'text', // Carrossel é enviado como text com botões
                'content' => $message['content'],
                'waha_payload' => $wahaPayload,
                'waha_method' => 'sendButtons',
                'priority' => 5,
            ]);
            
            Logger::info('Carrossel enfileirado', [
                'message_id' => $message['id'],
                'instance_id' => $instance['id'],
                'number' => $number,
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
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            
            if (!$instance) {
                Response::notFound('Instância não encontrada');
                return;
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $number = $input['number'] ?? $input['to'] ?? '';
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $number);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                Response::error($errorMessage, 400);
                return;
            }
            
            // Usar número validado
            $validatedNumber = $validation['validated_number'];
            
            Database::beginTransaction();
            
            // Preparar payload para WAHA (usar botões com solicitação de localização)
            $wahaPayload = [
                'chatId' => $validatedNumber . '@s.whatsapp.net',
                'session' => $instance['external_instance_id'],
                'body' => $input['text'], // WAHA usa 'body' para sendButtons
                'buttons' => [
                    [
                        'type' => 'reply',
                        'text' => '📍 Compartilhar Localização',
                        'id' => 'request_location'
                    ]
                ]
            ];
            
            // Criar mensagem no banco
            $message = \App\Models\Message::create([
                'instance_id' => $instance['id'],
                'company_id' => $instance['company_id'],
                'direction' => 'outbound',
                'phone_to' => $validatedNumber,
                'phone_from' => $instance['phone_number'] ?? '',
                'message_type' => 'location', // Solicitação de localização usa tipo location
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
                'phone_to' => $validatedNumber,
                'message_type' => 'location', // Solicitação de localização usa tipo location
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
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            
            if (!$instance) {
                Response::notFound('Instância não encontrada');
                return;
            }
            
            // Status não precisa validar número (não tem destinatário específico)
            
            Database::beginTransaction();
            
            // Preparar payload para WAHA
            $wahaPayload = [
                'session' => $instance['external_instance_id']
            ];
            
            // Processar por tipo de status
            switch ($input['type']) {
                case 'text':
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
                'message_type' => 'text', // Status é enviado como text (histórias)
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
                'message_type' => 'text', // Status é enviado como text (histórias)
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
        // WAHA usa 'body' ao invés de 'text'
        $wahaPayload['body'] = $input['text'];
        $wahaPayload['buttons'] = [];
        
        foreach ($input['choices'] as $choice) {
            $button = self::parseButtonChoice($choice);
            // WAHA espera: type (reply/url/call), text, e opcionalmente id, url, phoneNumber
            $wahaButton = [
                'type' => strtolower($button['type']), // reply, url, call, copy
                'text' => $button['text']
            ];
            
            // Adicionar campos específicos por tipo
            if ($button['type'] === 'URL') {
                $wahaButton['url'] = $button['id'];
            } elseif ($button['type'] === 'CALL') {
                $wahaButton['phoneNumber'] = $button['id'];
            } elseif ($button['type'] === 'COPY') {
                $wahaButton['copyCode'] = $button['id'];
            } else {
                // REPLY - usar id se diferente do text
                if ($button['id'] !== $button['text']) {
                    $wahaButton['id'] = $button['id'];
                }
            }
            
            $wahaPayload['buttons'][] = $wahaButton;
        }
        
        if (!empty($input['footerText'])) {
            $wahaPayload['footer'] = $input['footerText'];
        }
        
        return $wahaPayload;
    }
    
    private static function processList(array $input, array $wahaPayload): array
    {
        // WAHA sendList usa formato: message { title, description, footer, button, sections }
        $wahaPayload['message'] = [
            'title' => $input['text'] ?? '',
            'description' => $input['description'] ?? '',
            'footer' => $input['footerText'] ?? null,
            'button' => $input['listButton'] ?? 'Escolher',
            'sections' => []
        ];
        
        $currentSection = null;
        foreach ($input['choices'] as $choice) {
            if (strpos($choice, '[') === 0 && strpos($choice, ']') === strlen($choice) - 1) {
                // Nova seção
                $currentSection = [
                    'title' => trim($choice, '[]'),
                    'rows' => []
                ];
                $wahaPayload['message']['sections'][] = &$currentSection;
            } else {
                // Item da lista
                $item = self::parseListItem($choice);
                // WAHA espera: rowId, title, description (opcional)
                $wahaItem = [
                    'rowId' => $item['id'],
                    'title' => $item['title']
                ];
                if (!empty($item['description'])) {
                    $wahaItem['description'] = $item['description'];
                }
                
                if ($currentSection) {
                    $currentSection['rows'][] = $wahaItem;
                }
            }
        }
        
        // Remover footer se null
        if ($wahaPayload['message']['footer'] === null) {
            unset($wahaPayload['message']['footer']);
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
    
    
    private static function processCarouselItems(array $carousel, array $wahaPayload): array
    {
        // WAHA não tem suporte nativo para carrossel, então convertemos para botões com imagens no header
        $wahaPayload['buttons'] = [];
        $firstImage = null;
        
        foreach ($carousel as $item) {
            // Pegar primeira imagem para usar como headerImage
            if (!$firstImage && isset($item['image']) && !empty($item['image'])) {
                $firstImage = [
                    'url' => $item['image'],
                    'mimetype' => 'image/jpeg'
                ];
            }
            
            // Adicionar botões de cada item
            if (isset($item['button']) && is_array($item['button'])) {
                $wahaPayload['buttons'][] = [
                    'type' => 'reply',
                    'text' => $item['button']['text'] ?? 'Ver',
                    'id' => $item['button']['value'] ?? $item['button']['id'] ?? 'button_' . count($wahaPayload['buttons'])
                ];
            }
        }
        
        // Adicionar headerImage se tiver
        if ($firstImage) {
            $wahaPayload['headerImage'] = $firstImage;
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


