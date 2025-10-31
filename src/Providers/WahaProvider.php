<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;
use App\Providers\Waha\WahaInstanceProvider;
use App\Providers\Waha\WahaQrProvider;
use App\Providers\Waha\WahaMessageProvider;
use App\Providers\Waha\WahaPrivacyProvider;

class WahaProvider implements ProviderInterface
{
    private Client $client;
    private string $baseUrl;
    private ?string $apiKey;
    private string $webhookUrl;
    
    // Providers especializados
    private WahaInstanceProvider $instanceProvider;
    private WahaQrProvider $qrProvider;
    private WahaMessageProvider $messageProvider;
    private WahaPrivacyProvider $privacyProvider;
    
    public function __construct(string $baseUrl, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->webhookUrl = $_ENV['BACKEND_URL'] . '/webhook/waha' ?? '';
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => array_filter([
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ]),
        ]);
        
        // Inicializar providers especializados
        $this->instanceProvider = new WahaInstanceProvider($this->client, $this->baseUrl, $this->apiKey, $this->webhookUrl);
        $this->qrProvider = new WahaQrProvider($this->client, $this->baseUrl, $this->apiKey);
        $this->messageProvider = new WahaMessageProvider($this->client, $this->baseUrl, $this->apiKey);
        $this->privacyProvider = new WahaPrivacyProvider($this->client, $this->baseUrl, $this->apiKey);
    }
    
    /**
     * Criar instância
     */
    public function createInstance(string $instanceName, ?string $instanceId = null, array $webhooks = []): array
    {
        try {
            // Configurar webhook do backend se BACKEND_URL estiver configurado
            $payload = ['name' => $instanceName];
            $backendUrl = $_ENV['BACKEND_URL'] ?? null;
            
            if ($backendUrl && $instanceId) {
                $wahaWebhooks = [];
                
                // Webhook padrão do backend (sempre adicionado)
                $wahaWebhooks[] = [
                    'url' => "{$backendUrl}/webhook/waha/{$instanceId}",
                    'events' => [
                        'session.status',            // ✨ Status da sessão
                        'message',                   // ✨ Mensagens recebidas
                        'message.reaction',          // ✨ Reações
                        'message.any',               // ✨ Todas as mensagens
                        'message.ack',               // ✨ Mensagens lidas/entregues
                        'message.revoked',           // ✨ Mensagens revogadas
                        'message.edited',            // ✨ Mensagens editadas
                        'group.v2.join',             // ✨ Entrou em grupo
                        'group.v2.leave',            // ✨ Saiu do grupo
                        'group.v2.update',           // ✨ Grupo atualizado
                        'group.v2.participants',     // ✨ Participantes alterados
                        'presence.update',           // ✨ Presença atualizada
                        'poll.vote',                 // ✨ Votos em enquetes
                        'poll.vote.failed',          // ✨ Falha no voto
                        'chat.archive',              // ✨ Chat arquivado
                        'call.received',             // ✨ Chamada recebida
                        'call.accepted',             // ✨ Chamada aceita
                        'call.rejected',             // ✨ Chamada rejeitada
                        'label.upsert',              // ✨ Label criada/atualizada
                        'label.deleted',             // ✨ Label deletada
                        'label.chat.added',          // ✨ Label adicionada ao chat
                        'label.chat.deleted',        // ✨ Label removida do chat
                        'event.response',            // ✨ Resposta do evento
                        'event.response.failed',     // ✨ Falha na resposta
                        'engine.event'               // ✨ Evento interno
                    ]
                ];
                
                // Adicionar webhooks customizados se fornecidos
                foreach ($webhooks as $webhook) {
                    if (!empty($webhook['url']) && !empty($webhook['events'])) {
                        $wahaWebhooks[] = [
                            'url' => $webhook['url'],
                            'events' => $webhook['events'],
                            'hmac' => $webhook['hmac'] ?? null,
                            'retries' => $webhook['retries'] ?? null,
                            'customHeaders' => $webhook['customHeaders'] ?? null
                        ];
                    }
                }
                
                $payload['config'] = [
                    'webhooks' => $wahaWebhooks
                ];
            }

            Logger::info('WAHA createInstance request', [
                'instance_name' => $instanceName,
                'payload' => $payload,
                'base_url' => $this->baseUrl
            ]);
            
            $response = $this->client->post('/api/sessions', [
                'json' => $payload
            ]);
            
            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);
            
            Logger::info('WAHA createInstance response', [
                'instance_name' => $instanceName,
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'parsed_data' => $data
            ]);
            
            Logger::info('WAHA instance created', [
                'instance_name' => $instanceName,
                'instance_id' => $instanceId,
                'waha_id' => $data['name'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Instância criada com sucesso',
                'instance_id' => $data['name'] ?? null,  // ID da instância no WAHA (usa o campo 'name')
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA create instance failed', [
                'instance_name' => $instanceName,
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar instância: ' . $e->getMessage()
            ];
        }
    }
    
    // ===== DELEGAÇÃO PARA PROVIDERS ESPECIALIZADOS =====
    
    /**
     * Conectar instância (delega para WahaInstanceProvider)
     */
    public function connect(string $externalInstanceId, ?string $phone = null): array
    {
        return $this->instanceProvider->connect($externalInstanceId, $phone);
    }
    
    /**
     * Desconectar instância (delega para WahaInstanceProvider)
     */
    public function disconnect(string $externalInstanceId): array
    {
        return $this->instanceProvider->disconnect($externalInstanceId);
    }
    
    /**
     * Obter status da instância (delega para WahaInstanceProvider)
     */
    public function getStatus(string $externalInstanceId): array
    {
        return $this->instanceProvider->getStatus($externalInstanceId);
    }
    
    /**
     * Atualizar nome da instância (delega para WahaInstanceProvider)
     */
    public function updateInstanceName(string $externalInstanceId, string $newName): array
    {
        return $this->instanceProvider->updateName($externalInstanceId, $newName);
    }
    
    /**
     * Deletar instância (delega para WahaInstanceProvider)
     */
    public function deleteInstance(string $externalInstanceId): bool
    {
        $result = $this->instanceProvider->delete($externalInstanceId);
        return $result['success'] ?? false;
    }
    
    /**
     * Obter QR Code (delega para WahaQrProvider)
     */
    public function getQRCode(string $externalInstanceId): ?string
    {
        $result = $this->qrProvider->getQRCode($externalInstanceId);
        return $result['qrcode'] ?? null;
    }
    
    /**
     * Obter imagem do QR Code (delega para WahaQrProvider)
     */
    public function getQRCodeImage(string $externalInstanceId): array
    {
        return $this->qrProvider->getQRCodeImage($externalInstanceId);
    }
    
    /**
     * Enviar mensagem de texto (delega para WahaMessageProvider)
     */
    public function sendText(string $externalInstanceId, string $to, string $message): array
    {
        return $this->messageProvider->sendText($externalInstanceId, $to, $message);
    }
    
    /**
     * Enviar mídia (delega para WahaMessageProvider)
     */
    public function sendMedia(string $externalInstanceId, string $to, string $mediaUrl, ?string $caption = null, ?string $type = null): array
    {
        return $this->messageProvider->sendMedia($externalInstanceId, $to, $mediaUrl, $caption, $type);
    }
    
    /**
     * Enviar contato (delega para WahaMessageProvider)
     */
    public function sendContact(string $externalInstanceId, string $to, array $contact): array
    {
        return $this->messageProvider->sendContact($externalInstanceId, $to, $contact);
    }
    
    /**
     * Enviar localização (delega para WahaMessageProvider)
     */
    public function sendLocation(string $externalInstanceId, string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        return $this->messageProvider->sendLocation($externalInstanceId, $to, $latitude, $longitude, $name, $address);
    }
    
    /**
     * Buscar configurações de privacidade (delega para WahaPrivacyProvider)
     */
    public function getPrivacySettings(string $externalInstanceId): array
    {
        return $this->privacyProvider->getPrivacySettings($externalInstanceId);
    }
    
    /**
     * Atualizar configurações de privacidade (delega para WahaPrivacyProvider)
     */
    public function updatePrivacySettings(string $externalInstanceId, array $settings): array
    {
        return $this->privacyProvider->updatePrivacySettings($externalInstanceId, $settings);
    }
    
    /**
     * Atualizar status de presença (delega para WahaPrivacyProvider)
     */
    public function updatePresence(string $externalInstanceId, string $status, ?string $message = null): array
    {
        return $this->privacyProvider->updatePresence($externalInstanceId, $status, $message);
    }
    
    /**
     * Reiniciar sessão (delega para WahaInstanceProvider)
     */
    public function restartSession(string $externalInstanceId): array
    {
        return $this->instanceProvider->restartSession($externalInstanceId);
    }
    
    // ===== MÉTODOS DA INTERFACE PROVIDERINTERFACE =====
    
    /**
     * Enviar mensagem de texto (interface)
     */
    public function sendTextMessage(string $externalInstanceId, string $phone, string $text): array
    {
        return $this->messageProvider->sendText($externalInstanceId, $phone, $text);
    }
    
    /**
     * Enviar mensagem com mídia (interface)
     */
    public function sendMediaMessage(string $externalInstanceId, string $phone, string $mediaType, string $mediaUrl, string $caption = ''): array
    {
        return $this->messageProvider->sendMedia($externalInstanceId, $phone, $mediaUrl, $caption, $mediaType);
    }
    
    /**
     * Verificar status da instância (interface)
     */
    public function getInstanceStatus(string $externalInstanceId): array
    {
        return $this->instanceProvider->getStatus($externalInstanceId);
    }
    
    /**
     * Verificar se provider está saudável
     */
    public function healthCheck(): bool
    {
        try {
            Logger::info('WAHA health check request', [
                'base_url' => $this->baseUrl,
                'api_key_set' => !empty($this->apiKey)
            ]);
            
            $response = $this->client->get('/api/sessions');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Logger::error('WAHA health check failed', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
                'api_key_set' => !empty($this->apiKey)
            ]);
            return false;
        }
    }

    /**
     * Lista todas as instâncias do provider
     */
    public function listInstances(): array
    {
        try {
            $response = $this->client->get('/api/sessions');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (GuzzleException $e) {
            Logger::error('WAHA list instances failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Erro ao listar instâncias: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desconecta uma instância no provider (interface)
     */
    public function disconnectInstance(string $externalInstanceId): array
    {
        return $this->instanceProvider->disconnect($externalInstanceId);
    }
    
    /**
     * Obtém configurações de privacidade (interface)
     */
    public function getPrivacy(string $externalInstanceId): array
    {
        return $this->privacyProvider->getPrivacySettings($externalInstanceId);
    }
    
    /**
     * Atualiza configurações de privacidade (interface)
     */
    public function updatePrivacy(string $externalInstanceId, array $settings): array
    {
        return $this->privacyProvider->updatePrivacySettings($externalInstanceId, $settings);
    }
    
    /**
     * Define presença (online/offline) (interface)
     */
    public function setPresence(string $externalInstanceId, string $presence): array
    {
        return $this->privacyProvider->updatePresence($externalInstanceId, $presence);
    }
    
    /**
     * Solicita código de autenticação por telefone (interface)
     */
    public function requestAuthCode(string $externalInstanceId, string $phoneNumber): array
    {
        try {
            // Solicitar código de autenticação
            $response = $this->client->get("/api/{$externalInstanceId}/auth/qr");
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA auth code requested', [
                'external_id' => $externalInstanceId,
                'phone' => $phoneNumber,
                'response' => $data
            ]);
            
            return [
                'success' => true,
                'message' => 'Código de autenticação solicitado',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA request auth code failed', [
                'external_id' => $externalInstanceId,
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao solicitar código de autenticação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar se um número existe no WhatsApp
     * Delega para WahaMessageProvider
     */
    public function checkNumberStatus(string $externalInstanceId, string $phone): array
    {
        return $this->messageProvider->checkNumberStatus($externalInstanceId, $phone);
    }
}
