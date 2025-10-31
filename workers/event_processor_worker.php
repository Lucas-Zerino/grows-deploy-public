<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Models\Instance;
use App\Models\InstanceWebhook;
use App\Models\Company;
use App\Utils\Logger;
use App\Utils\Database;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Starting Event Processor Worker...\n";
Logger::info('Event Processor Worker started');

// Handle graceful shutdown
pcntl_signal(SIGTERM, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Event Processor Worker shutting down');
    QueueService::close();
    exit(0);
});

pcntl_signal(SIGINT, function () {
    echo "Shutting down gracefully...\n";
    Logger::info('Event Processor Worker shutting down');
    QueueService::close();
    exit(0);
});

try {
    QueueService::init();
    
    $httpClient = new Client(['timeout' => 10]);
    
    // Callback compartilhado para processar eventos
    $callback = function (array $data, $msg) use ($httpClient) {
        pcntl_signal_dispatch();
        
        try {
            $instanceId = $data['instance']['instanceId'] ?? $data['instance_id'] ?? null;
            
            Logger::info('Processing inbound event', [
                'instance_id' => $instanceId,
                'event_type' => $data['event'] ?? 'unknown',
                'payload' => $data,
                'context' => 'worker_inbound'
            ]);

            // Buscar webhooks da instância
            $webhooks = [];
            $eventType = $data['event'] ?? 'unknown';
            
            // Detectar se é evento do Instagram
            $isInstagramEvent = isset($data['source']) && $data['source'] === 'instagram';
            
            if ($instanceId) {
                // Para eventos do Instagram, buscar instância pelo company_id
                if ($isInstagramEvent && isset($data['company_id'])) {
                    $instance = Instance::getByInstagramUserId($data['user_id'] ?? '');
                    if (!$instance) {
                        // Buscar por company_id se não encontrou por user_id
                        $instances = Instance::getByCompanyId($data['company_id']);
                        $instance = $instances[0] ?? null; // Pegar primeira instância Instagram da company
                    }
                } else {
                    // Buscar instância pelo external_instance_id (WAHA/UAZAPI)
                    // O external_instance_id vem no formato: company_id-instance_name (ex: "11-1515")
                    $instance = Instance::getByExternalInstanceId($instanceId);
                }
                
                if ($instance) {
                    // Buscar webhooks ativos da instância específica
                    $webhooks = InstanceWebhook::getActiveByInstanceId($instance['id']);
                    
                    Logger::debug('Instance found, checking webhooks', [
                        'instance_id' => $instance['id'],
                        'instance_name' => $instance['instance_name'] ?? null,
                        'webhooks_count' => count($webhooks),
                        'legacy_webhook_url' => $instance['webhook_url'] ?? null
                    ]);
                    
                    // Adicionar webhook legado se existir (para compatibilidade)
                    if ($instance['webhook_url']) {
                        $defaultEvents = ['message', 'message.any', 'message.ack', 'message.reaction', 'message.revoked', 'message.edited', 'session.status', 'state.change', 'presence.update', 'group.v2.join', 'group.v2.leave', 'group.v2.update', 'group.v2.participants', 'poll.vote', 'chat.archive', 'call.received', 'call.accepted', 'call.rejected', 'label.upsert', 'label.deleted', 'label.chat.added', 'label.chat.deleted', 'event.response', 'event.response.failed', 'engine.event', 'on-connected', 'on-disconnected', 'on-message', 'on-message-sent', 'on-message-delivered', 'on-message-read'];
                        
                        // Adicionar eventos do Instagram se for instância Instagram
                        if ($isInstagramEvent) {
                            $defaultEvents = array_merge($defaultEvents, [
                                'instagram.webhook', 'instagram.messaging', 'instagram.message', 
                                'instagram.message.postback', 'instagram.message.referral', 
                                'instagram.message.reaction', 'instagram.message.ack',
                                'instagram.typing.start', 'instagram.typing.stop'
                            ]);
                        }
                        
                        $webhooks[] = [
                            'id' => 'legacy',
                            'webhook_url' => $instance['webhook_url'],
                            'events' => $defaultEvents,
                            'is_active' => true
                        ];
                    }
                } else {
                    Logger::warning('Instance not found for event', [
                        'instance_id' => $instanceId,
                        'event_type' => $eventType,
                        'payload_keys' => array_keys($data)
                    ]);
                }
            }
            
            // Enviar evento para todos os webhooks compatíveis
            if (!empty($webhooks)) {
                // Formatar evento para cliente: extrair apenas o nome da instância (sem company_id-)
                $clientEvent = formatEventForClient($data, $instance ?? null);
                
                foreach ($webhooks as $webhook) {
                    // Verificar se o webhook deve receber este tipo de evento
                    if (shouldSendToWebhook($eventType, $webhook['events'])) {
                        try {
                            $response = $httpClient->post($webhook['webhook_url'], [
                                'json' => $clientEvent, // Evento formatado para cliente (instance_id sem company_id-)
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'X-Webhook-Source' => 'GrowHub-Gateway',
                                    'User-Agent' => 'GrowHub-Gateway/1.0',
                                ],
                            ]);
                            
                            Logger::info('Webhook notification sent to client', [
                                'instance_id' => $instanceId,
                                'webhook_id' => $webhook['id'],
                                'webhook_url' => $webhook['webhook_url'],
                                'event_type' => $eventType,
                                'status_code' => $response->getStatusCode(),
                                'context' => 'webhook_outbound'
                            ]);
                            
                            // Resetar contador de tentativas em caso de sucesso
                            if ($webhook['id'] !== 'legacy') {
                                InstanceWebhook::resetRetryCount($webhook['id']);
                            }
                            
                        } catch (\Exception $e) {
                            Logger::warning('Failed to send webhook notification to client', [
                                'instance_id' => $instanceId,
                                'webhook_id' => $webhook['id'],
                                'webhook_url' => $webhook['webhook_url'],
                                'event_type' => $eventType,
                                'error' => $e->getMessage(),
                            ]);
                            
                            // Incrementar contador de tentativas em caso de erro
                            if ($webhook['id'] !== 'legacy') {
                                InstanceWebhook::incrementRetryCount($webhook['id']);
                            }
                            
                            // Não falhar o job por erro no webhook do cliente
                        }
                    } else {
                        Logger::debug('Webhook skipped - event type not configured', [
                            'instance_id' => $instanceId,
                            'webhook_id' => $webhook['id'],
                            'webhook_url' => $webhook['webhook_url'],
                            'event_type' => $eventType,
                            'configured_events' => $webhook['events']
                        ]);
                        
                        // Log mais detalhado para eventos de conexão/desconexão
                        if (in_array($eventType, ['on-connected', 'on-disconnected', 'session.status', 'state.change'])) {
                            Logger::info('Connection event skipped by webhook filter', [
                                'instance_id' => $instanceId,
                                'webhook_url' => $webhook['webhook_url'],
                                'event_type' => $eventType,
                                'configured_events' => $webhook['events']
                            ]);
                        }
                    }
                }
            } else {
                if ($instanceId) {
                    Logger::warning('No webhooks configured for instance', [
                        'instance_id' => $instanceId,
                        'event_type' => $eventType,
                        'note' => 'Check if instance exists and has webhook_url or active instance_webhooks'
                    ]);
                } else {
                    Logger::warning('No instance_id found in event payload', [
                        'event_type' => $eventType,
                        'payload_keys' => array_keys($data)
                    ]);
                }
            }
            
            return true; // ACK
        } catch (\Exception $e) {
            Logger::error('Error processing inbound event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false; // NACK
        }
    };
    
    // Consumir do exchange com routing pattern wildcard
    // Isso vai receber TODAS as novas mensagens que chegarem
    // Para mensagens antigas presas nas filas persistentes, será necessário
    // processar manualmente ou usar um script separado
    $exchangeName = 'messaging.inbound.exchange';
    $routingPattern = 'company.*';
    
    echo "Consuming events from exchange: {$exchangeName} with pattern: {$routingPattern}\n";
    echo "Prefetch: 50 (para processar mensagens mais rápido)\n";
    echo "NOTA: Mensagens antigas nas filas persistentes precisam ser processadas separadamente\n\n";
    
    // Prefetch maior para processar mais rápido
    QueueService::consumeFromExchange($exchangeName, $routingPattern, $callback, 50);
    
} catch (\Exception $e) {
    Logger::critical('Event Processor Worker crashed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "Worker crashed: {$e->getMessage()}\n";
    exit(1);
}

/**
 * Formata evento para enviar ao cliente final
 * Extrai apenas o nome da instância (sem company_id-) do instance_id
 */
function formatEventForClient(array $event, ?array $instance): array
{
    $clientEvent = $event;
    
    // Prioridade 1: Se tiver a instância do banco, usar instance_name diretamente
    if ($instance && isset($instance['instance_name'])) {
        $clientEvent['instance_id'] = $instance['instance_name'];
    }
    // Prioridade 2: Se o instance_id estiver no formato company_id-instance_name, extrair apenas o nome
    elseif (isset($clientEvent['instance_id'])) {
        $instanceId = $clientEvent['instance_id'];
        
        // Se for formato "11-1515", extrair apenas "1515"
        if (strpos($instanceId, '-') !== false) {
            $parts = explode('-', $instanceId, 2);
            if (count($parts) === 2 && is_numeric($parts[0])) {
                $clientEvent['instance_id'] = $parts[1]; // Retorna apenas o nome da instância
            }
        }
    }
    // Prioridade 3: Se tiver instance_name no próprio evento, usar
    elseif (isset($clientEvent['instance_name'])) {
        $clientEvent['instance_id'] = $clientEvent['instance_name'];
    }
    
    // Também formatar o campo 'session' se existir e estiver no mesmo formato
    if (isset($clientEvent['session']) && is_string($clientEvent['session']) && strpos($clientEvent['session'], '-') !== false) {
        $parts = explode('-', $clientEvent['session'], 2);
        if (count($parts) === 2 && is_numeric($parts[0])) {
            $clientEvent['session'] = $parts[1];
        }
    }
    
    return $clientEvent;
}

/**
 * Verifica se um webhook deve receber um evento baseado nos eventos configurados
 */
function shouldSendToWebhook(string $eventType, array $configuredEvents): bool
{
    // Se não há eventos configurados, não enviar
    if (empty($configuredEvents)) {
        return false;
    }
    
    // Mapear tipos de eventos para categorias mais amplas
    $eventMappings = [
        // Eventos de mensagem
        'on-message' => ['message', 'message.any'],
        'message' => ['message', 'message.any'],
        'message.any' => ['message', 'message.any'],
        'message.ack' => ['message.ack'],
        'on-message-delivered' => ['message.ack'],
        'on-message-read' => ['message.ack'],
        'message.reaction' => ['message.reaction'],
        'message.revoked' => ['message.revoked'],
        'message.edited' => ['message.edited'],
        
        // Eventos de sessão
        'session.status' => ['session.status', 'state.change'],
        'state.change' => ['session.status', 'state.change'],
        'on-connected' => ['session.status', 'state.change', 'on-connected'],
        'on-disconnected' => ['session.status', 'state.change', 'on-disconnected'],
        
        // Eventos de presença
        'presence.update' => ['presence.update'],
        
        // Eventos de grupo
        'group.v2.join' => ['group.v2.join'],
        'group.v2.leave' => ['group.v2.leave'],
        'group.v2.update' => ['group.v2.update'],
        'group.v2.participants' => ['group.v2.participants'],
        
        // Eventos de enquete
        'poll.vote' => ['poll.vote'],
        'poll.vote.failed' => ['poll.vote.failed'],
        
        // Eventos de chat
        'chat.archive' => ['chat.archive'],
        
        // Eventos de chamada
        'call.received' => ['call.received'],
        'call.accepted' => ['call.accepted'],
        'call.rejected' => ['call.rejected'],
        
        // Eventos de etiqueta
        'label.upsert' => ['label.upsert'],
        'label.deleted' => ['label.deleted'],
        'label.chat.added' => ['label.chat.added'],
        'label.chat.deleted' => ['label.chat.deleted'],
        
        // Eventos de resposta
        'event.response' => ['event.response'],
        'event.response.failed' => ['event.response.failed'],
        
        // Eventos internos
        'engine.event' => ['engine.event'],
        
        // Eventos do Instagram
        'instagram.webhook' => ['instagram.webhook'],
        'instagram.messaging' => ['instagram.messaging'],
        'instagram.message' => ['message', 'message.any'],
        'instagram.message.postback' => ['message.postback'],
        'instagram.message.referral' => ['message.referral'],
        'instagram.message.reaction' => ['message.reaction'],
        'instagram.message.ack' => ['message.ack'],
        'instagram.typing.start' => ['typing.start'],
        'instagram.typing.stop' => ['typing.stop']
    ];
    
    // Verificar se o evento está diretamente configurado
    if (in_array($eventType, $configuredEvents)) {
        return true;
    }
    
    // Verificar se algum dos eventos mapeados está configurado
    $mappedEvents = $eventMappings[$eventType] ?? [];
    foreach ($mappedEvents as $mappedEvent) {
        if (in_array($mappedEvent, $configuredEvents)) {
            return true;
        }
    }
    
    return false;
}

