<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\QueueService;
use App\Utils\Logger;
use App\Utils\Response;
use App\Utils\Router;

class WebhookController
{
    /**
     * Recebe webhook da WAHA
     * POST /webhook/waha/{instanceId}
     */
    public static function wahaWebhook(string $instanceId): void
    {
        try {
            $payload = Router::getJsonInput();
            
            Logger::info('WAHA webhook received', [
                'instance_id' => $instanceId,
                'event_type' => $payload['event'] ?? 'unknown',
            ]);

            // Buscar instância
            $instance = Instance::findById((int) $instanceId);
            
            if (!$instance) {
                Logger::warning('Webhook received for non-existent instance', [
                    'instance_id' => $instanceId,
                ]);

                Response::notFound('Instance not found');
                return;
            }

            // PRIMEIRO: Processar evento internamente (atualizar banco, etc)
            self::processEventInternally($payload, $instance);
            
            // DEPOIS: Traduzir evento WAHA → Formato customizado para cliente
            $translatedEvent = self::translateWahaToUazapi($payload, $instance);

            // Enviar para fila de entrada da empresa
            $routingKey = "company.{$instance['company_id']}";
            
            QueueService::publishToExchange(
                'messaging.inbound.exchange',
                $translatedEvent,
                $routingKey
            );

            Logger::info('WAHA webhook processed and sent to queue', [
                'instance_id' => $instanceId,
                'company_id' => $instance['company_id'],
                'routing_key' => $routingKey,
                'event_type' => $payload['event'] ?? 'unknown',
            ]);

            Response::success(['received' => true]);

        } catch (\Exception $e) {
            Logger::error('Failed to process WAHA webhook', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::serverError('Failed to process webhook');
        }
    }

    /**
     * Recebe webhook da UAZAPI
     * POST /webhook/uazapi/{instanceId}
     */
    public static function uazapiWebhook(string $instanceId): void
    {
        try {
            $payload = Router::getJsonInput();
            
            Logger::info('UAZAPI webhook received', [
                'instance_id' => $instanceId,
                'event_type' => $payload['event'] ?? 'unknown',
            ]);

            // Buscar instância
            $instance = Instance::findById((int) $instanceId);
            
            if (!$instance) {
                Logger::warning('Webhook received for non-existent instance', [
                    'instance_id' => $instanceId,
                ]);

                Response::notFound('Instance not found');
                return;
            }

            // UAZAPI já está no formato esperado, enviar direto para fila
            $routingKey = "company.{$instance['company_id']}";
            
            QueueService::publishToExchange(
                'messaging.inbound.exchange',
                $payload,
                $routingKey
            );

            Logger::info('UAZAPI webhook processed and sent to queue', [
                'instance_id' => $instanceId,
                'company_id' => $instance['company_id'],
                'routing_key' => $routingKey,
            ]);

            Response::success(['received' => true]);

        } catch (\Exception $e) {
            Logger::error('Failed to process UAZAPI webhook', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::serverError('Failed to process webhook');
        }
    }

    /**
     * Processa evento internamente (atualiza banco de dados, etc)
     */
    private static function processEventInternally(array $payload, array $instance): void
    {
        $eventType = $payload['event'] ?? 'unknown';
        
        try {
            switch ($eventType) {
                case 'state.change':
                case 'session.status':
                    // Atualizar status da instância
                    self::processStateChange($payload, $instance);
                    break;
                    
                case 'message.ack':
                    // Atualizar status de mensagem (enviada/entregue/lida)
                    self::processMessageAck($payload, $instance);
                    break;
                    
                case 'message':
                case 'message.any':
                    // Registrar evento de mensagem recebida
                    self::processMessageReceived($payload, $instance);
                    break;
            }
        } catch (\Exception $e) {
            Logger::warning('Failed to process event internally', [
                'event_type' => $eventType,
                'instance_id' => $instance['id'],
                'error' => $e->getMessage(),
            ]);
            // Não falhar o webhook se processamento interno falhar
        }
    }
    
    /**
     * Processa mudança de estado (conexão/desconexão)
     */
    private static function processStateChange(array $payload, array $instance): void
    {
        $state = $payload['state'] ?? $payload['status'] ?? null;
        
        if (!$state) return;
        
        // Mapear estados da WAHA para nosso sistema
        $statusMap = [
            'CONNECTED' => 'connected',
            'WORKING' => 'connected',
            'DISCONNECTED' => 'disconnected',
            'STOPPED' => 'disconnected',
            'STARTING' => 'connecting',
            'SCAN_QR_CODE' => 'connecting',
            'FAILED' => 'disconnected',
        ];
        
        $newStatus = $statusMap[$state] ?? null;
        
        if ($newStatus && $newStatus !== $instance['status']) {
            Instance::updateStatus($instance['id'], $newStatus);
            
            Logger::info('Instance status updated', [
                'instance_id' => $instance['id'],
                'old_status' => $instance['status'],
                'new_status' => $newStatus,
                'waha_state' => $state,
            ]);
        }
    }
    
    /**
     * Processa ACK de mensagem (enviada/entregue/lida)
     */
    private static function processMessageAck(array $payload, array $instance): void
    {
        $messageId = $payload['id'] ?? null;
        $ackStatus = $payload['ack'] ?? $payload['status'] ?? null;
        
        if (!$messageId || $ackStatus === null) return;
        
        // Mapear status do ACK
        $statusMap = [
            1 => 'sent',
            2 => 'delivered',
            3 => 'read',
            4 => 'played',
        ];
        
        $newStatus = $statusMap[(int)$ackStatus] ?? null;
        
        if ($newStatus) {
            // Buscar mensagem pelo external_message_id e atualizar status
            // TODO: Implementar quando tivermos external_message_id no banco
            
            Logger::debug('Message ACK received', [
                'instance_id' => $instance['id'],
                'message_id' => $messageId,
                'status' => $newStatus,
            ]);
        }
    }
    
    /**
     * Processa mensagem recebida (registrar evento)
     */
    private static function processMessageReceived(array $payload, array $instance): void
    {
        // Aqui podemos registrar estatísticas, etc
        Logger::debug('Message received', [
            'instance_id' => $instance['id'],
            'from' => $payload['from'] ?? 'unknown',
            'has_media' => !empty($payload['hasMedia']),
        ]);
    }
    
    /**
     * Traduz evento WAHA para formato customizado do cliente
     */
    private static function translateWahaToUazapi(array $wahaEvent, array $instance): array
    {
        $eventType = $wahaEvent['event'] ?? 'unknown';
        $payload = $wahaEvent['payload'] ?? $wahaEvent['data'] ?? [];
        
        // Se for mensagem, usar formato customizado
        if (in_array($eventType, ['message', 'message.any'])) {
            return self::translateWahaMessage($payload, $instance);
        }
        
        // Se for ACK (mensagem lida/entregue), usar formato de ACK
        if ($eventType === 'message.ack') {
            return self::translateMessageAck($payload, $instance);
        }
        
        // Se for mudança de estado (conexão/desconexão), usar formato de estado
        if (in_array($eventType, ['state.change', 'session.status'])) {
            return self::translateStateChange($payload, $instance);
        }
        
        // Para outros eventos, formato customizado básico
        return [
            'event' => self::mapEventType($eventType),
            'session' => (int) $instance['id'],
            'container' => "api-{$instance['id']}",
            'device' => self::extractPhoneNumber($instance['phone_number'] ?? ''),
            'data' => $payload,
            'instance_id' => $instance['external_instance_id'],
            'webhook_url' => $instance['webhook_url'] ?? null,
            'token' => $instance['token'] ?? null,
            'ambiente' => $_ENV['APP_ENV'] ?? 'dev',
            'timestamp' => time() * 1000,
        ];
    }

    /**
     * Traduz mensagem WAHA para formato customizado do cliente
     */
    private static function translateWahaMessage(array $wahaPayload, array $instance): array
    {
        // Extrair informações da mensagem WAHA
        $from = $wahaPayload['from'] ?? '';
        $id = $wahaPayload['id'] ?? '';
        $body = $wahaPayload['body'] ?? '';
        $timestamp = ($wahaPayload['timestamp'] ?? time()) * 1000; // converter para milissegundos
        $fromMe = $wahaPayload['fromMe'] ?? false;
        
        // Detectar tipo de mensagem e mídia
        $messageType = self::detectMessageType($wahaPayload);
        $isMedia = in_array($messageType, ['image', 'video', 'audio', 'document']);
        
        // Extrair número limpo (sem @c.us ou @g.us)
        $fromNumber = self::extractPhoneNumber($from);
        $deviceNumber = $instance['phone_number'] ?? '';
        $deviceNumber = self::extractPhoneNumber($deviceNumber);
        
        // Verificar se é grupo
        $isGroup = str_contains($from, '@g.us');
        
        // Extrair LID se existir
        $lid = self::extractLid($wahaPayload);
        $participantLid = self::extractParticipantLid($wahaPayload);
        
        // Montar evento no formato customizado
        $event = [
            'usalid' => !empty($lid),
            'type' => $messageType,
            'isMedia' => $isMedia,
            'de_para_json' => true,
            'container' => "api-{$instance['id']}",
            'session' => (int) $instance['id'],
            'device' => $deviceNumber,
            'event' => 'on-message',
            'pushName' => $wahaPayload['_data']['notifyName'] ?? $wahaPayload['pushName'] ?? '',
            'from' => $fromNumber,
            'lid' => $lid,
            'id' => $id,
            'content' => $messageType === 'text' ? $body : '',
            'isgroup' => $isGroup,
            'api' => 10,
            'tipo_api' => 10,
            'participant' => $isGroup ? self::extractPhoneNumber($wahaPayload['author'] ?? '') : '',
            'participant_lid' => $participantLid,
            'timestamp' => $timestamp,
            'content_msg' => self::buildContentMsg($wahaPayload, $messageType),
            'webhook' => 'webhook_wh_message',
            'ambiente' => $_ENV['APP_ENV'] ?? 'dev',
            'token' => $instance['token'] ?? '',
            
            // IMPORTANTE: Campos usados pelo worker para roteamento
            'instance_id' => $instance['external_instance_id'], // Para o worker buscar webhook_url
            'webhook_url' => $instance['webhook_url'] ?? null, // URL do webhook do cliente
        ];
        
        // Adicionar info de arquivo se for mídia
        if ($isMedia) {
            $event['file'] = self::buildFileInfo($wahaPayload, $messageType);
        }
        
        return $event;
    }
    
    /**
     * Traduz evento de ACK (mensagem lida/entregue/enviada)
     */
    private static function translateMessageAck(array $payload, array $instance): array
    {
        // Mapear status do ACK
        $ackStatus = $payload['ack'] ?? $payload['status'] ?? 1;
        $ackType = match((int)$ackStatus) {
            1 => 'sent',      // Enviada
            2 => 'delivered', // Entregue
            3 => 'read',      // Lida
            4 => 'played',    // Reproduzida (áudio/vídeo)
            default => 'sent',
        };
        
        return [
            'event' => "on-message-$ackType",
            'session' => (int) $instance['id'],
            'container' => "api-{$instance['id']}",
            'device' => self::extractPhoneNumber($instance['phone_number'] ?? ''),
            'message_id' => $payload['id'] ?? '',
            'from' => self::extractPhoneNumber($payload['from'] ?? ''),
            'to' => self::extractPhoneNumber($payload['to'] ?? ''),
            'status' => $ackType,
            'ack' => (int)$ackStatus,
            'timestamp' => ($payload['timestamp'] ?? time()) * 1000,
            'instance_id' => $instance['external_instance_id'],
            'webhook_url' => $instance['webhook_url'] ?? null,
            'token' => $instance['token'] ?? '',
            'ambiente' => $_ENV['APP_ENV'] ?? 'dev',
        ];
    }
    
    /**
     * Traduz evento de mudança de estado (conexão/desconexão)
     */
    private static function translateStateChange(array $payload, array $instance): array
    {
        $state = $payload['state'] ?? $payload['status'] ?? 'unknown';
        
        // Mapear estados da WAHA
        $stateMap = [
            'CONNECTED' => 'connected',
            'WORKING' => 'connected',
            'DISCONNECTED' => 'disconnected',
            'STOPPED' => 'disconnected',
            'STARTING' => 'connecting',
            'SCAN_QR_CODE' => 'connecting',
            'FAILED' => 'disconnected',
        ];
        
        $mappedState = $stateMap[$state] ?? strtolower($state);
        $eventName = $mappedState === 'connected' ? 'on-connected' : 'on-disconnected';
        
        return [
            'event' => $eventName,
            'session' => (int) $instance['id'],
            'container' => "api-{$instance['id']}",
            'device' => self::extractPhoneNumber($instance['phone_number'] ?? ''),
            'status' => $mappedState,
            'state' => $state,
            'phone_number' => $instance['phone_number'] ?? '',
            'instance_name' => $instance['instance_name'],
            'timestamp' => time() * 1000,
            'instance_id' => $instance['external_instance_id'],
            'webhook_url' => $instance['webhook_url'] ?? null,
            'token' => $instance['token'] ?? '',
            'ambiente' => $_ENV['APP_ENV'] ?? 'dev',
        ];
    }
    
    /**
     * Mapeia tipo de evento WAHA para nome customizado
     */
    private static function mapEventType(string $eventType): string
    {
        $eventMap = [
            'message' => 'on-message',
            'message.any' => 'on-message',
            'message.ack' => 'on-message-ack',
            'state.change' => 'on-state-change',
            'session.status' => 'on-state-change',
            'group.join' => 'on-group-join',
            'group.leave' => 'on-group-leave',
            'presence.update' => 'on-presence-update',
            'poll.vote' => 'on-poll-vote',
            'call' => 'on-call',
        ];
        
        return $eventMap[$eventType] ?? $eventType;
    }
    
    /**
     * Detecta o tipo de mensagem
     */
    private static function detectMessageType(array $payload): string
    {
        // Verificar se tem mídia
        if (isset($payload['_data']['message'])) {
            $msg = $payload['_data']['message'];
            
            if (isset($msg['imageMessage'])) return 'image';
            if (isset($msg['videoMessage'])) return 'video';
            if (isset($msg['audioMessage'])) return 'audio';
            if (isset($msg['documentMessage']) || isset($msg['documentWithCaptionMessage'])) return 'document';
            if (isset($msg['stickerMessage'])) return 'sticker';
            if (isset($msg['contactMessage'])) return 'contact';
            if (isset($msg['locationMessage'])) return 'location';
        }
        
        // Se não identificou mídia, é texto
        return 'text';
    }
    
    /**
     * Extrai número de telefone limpo
     */
    private static function extractPhoneNumber(string $jid): string
    {
        // Remove @c.us, @g.us, @lid, etc
        return preg_replace('/@.*$/', '', $jid);
    }
    
    /**
     * Extrai LID se existir
     */
    private static function extractLid(array $payload): string
    {
        $from = $payload['from'] ?? '';
        if (str_contains($from, '@lid')) {
            return $from;
        }
        
        // Buscar em _data também
        if (isset($payload['_data']['key']['participant']) && str_contains($payload['_data']['key']['participant'], '@lid')) {
            return $payload['_data']['key']['participant'];
        }
        
        return '';
    }
    
    /**
     * Extrai LID do participante (em grupos)
     */
    private static function extractParticipantLid(array $payload): string
    {
        if (isset($payload['author']) && str_contains($payload['author'], '@lid')) {
            return $payload['author'];
        }
        
        return '';
    }
    
    /**
     * Monta objeto content_msg
     */
    private static function buildContentMsg(array $payload, string $type): array
    {
        $contentMsg = [];
        
        if ($type === 'text') {
            $contentMsg['text'] = $payload['body'] ?? '';
        }
        
        // Adicionar informações do contexto se existir
        if (isset($payload['_data']['message'])) {
            $msg = $payload['_data']['message'];
            
            // Pegar o primeiro tipo de mensagem que encontrar
            foreach ($msg as $msgType => $msgData) {
                if (is_array($msgData)) {
                    $contentMsg = array_merge($contentMsg, $msgData);
                    break;
                }
            }
        }
        
        return $contentMsg;
    }
    
    /**
     * Monta objeto file para mídias
     */
    private static function buildFileInfo(array $payload, string $type): array
    {
        $file = [
            'mimetype' => '',
            'filename' => '',
            'fileLength' => 0,
            'caption' => $payload['caption'] ?? '',
        ];
        
        // Extrair dados da mídia se existir
        if (isset($payload['_data']['message'])) {
            $msg = $payload['_data']['message'];
            
            // Tentar cada tipo de mídia
            $mediaTypes = ['imageMessage', 'videoMessage', 'audioMessage', 'documentMessage', 'documentWithCaptionMessage'];
            
            foreach ($mediaTypes as $mediaType) {
                if (isset($msg[$mediaType])) {
                    $media = $msg[$mediaType];
                    
                    $file['mimetype'] = $media['mimetype'] ?? '';
                    $file['filename'] = $media['fileName'] ?? $media['title'] ?? '';
                    
                    // fileLength pode vir como objeto ou número
                    if (isset($media['fileLength'])) {
                        $fileLength = $media['fileLength'];
                        $file['fileLength'] = is_array($fileLength) ? ($fileLength['low'] ?? 0) : $fileLength;
                    }
                    
                    break;
                }
            }
        }
        
        return $file;
    }
}

