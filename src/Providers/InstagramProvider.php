<?php

namespace App\Providers;

use App\Providers\Instagram\InstagramAuthProvider;
use App\Providers\Instagram\InstagramMessagingProvider;
use App\Providers\Instagram\InstagramWebhookProvider;
use App\Utils\Logger;

class InstagramProvider implements ProviderInterface
{
    private string $appId;
    private string $appSecret;
    private ?string $accessToken;
    
    // Providers especializados
    private InstagramAuthProvider $authProvider;
    private InstagramMessagingProvider $messagingProvider;
    private InstagramWebhookProvider $webhookProvider;
    
    public function __construct(string $appId, string $appSecret, ?string $accessToken = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
        
        // Inicializar providers especializados
        $this->authProvider = new InstagramAuthProvider();
        $this->messagingProvider = new InstagramMessagingProvider();
        $this->webhookProvider = new InstagramWebhookProvider();
    }

    /**
     * Criar instância Instagram (após OAuth)
     */
    public function createInstance(string $instanceName, ?string $instanceId = null): array
    {
        try {
            // Para Instagram, a instância é criada após OAuth
            // Aqui apenas validamos se temos as credenciais necessárias
            if (empty($this->appId) || empty($this->appSecret)) {
                return [
                    'success' => false,
                    'message' => 'Instagram App não configurado. Configure App ID e App Secret primeiro.'
                ];
            }

            Logger::info('Instagram instance creation initiated', [
                'instance_name' => $instanceName,
                'app_id' => $this->appId
            ]);

            return [
                'success' => true,
                'message' => 'Instância Instagram criada. Use o fluxo OAuth para conectar.',
                'requires_oauth' => true,
                'auth_url' => $this->getAuthUrl(),
                'instance_id' => $instanceId
            ];

        } catch (\Exception $e) {
            Logger::error('Instagram instance creation failed', [
                'instance_name' => $instanceName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar instância Instagram: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deletar instância Instagram
     */
    public function deleteInstance(string $externalInstanceId): bool
    {
        // Instagram não permite deletar instâncias via API
        // Apenas remover do banco de dados
        Logger::info('Instagram instance deletion requested', [
            'external_instance_id' => $externalInstanceId
        ]);

        return true;
    }

    /**
     * Obter QR Code (não aplicável para Instagram)
     */
    public function getQRCode(string $externalInstanceId): ?string
    {
        // Instagram usa OAuth, não QR code
        return null;
    }

    /**
     * Obter URL de autenticação OAuth
     */
    public function getAuthUrl(): string
    {
        $redirectUri = $_ENV['INSTAGRAM_REDIRECT_URI'] ?? 'https://gapi.sockets.com.br/api/instagram/callback';
        return $this->authProvider->generateAuthUrl($this->appId, $redirectUri);
    }

    /**
     * Conectar instância (iniciar OAuth)
     */
    public function connect(string $externalInstanceId, ?string $phone = null): array
    {
        return [
            'success' => true,
            'message' => 'Use o fluxo OAuth para conectar Instagram',
            'auth_url' => $this->getAuthUrl(),
            'requires_oauth' => true
        ];
    }

    /**
     * Desconectar instância
     */
    public function disconnect(string $externalInstanceId): array
    {
        // Instagram não permite desconectar via API
        // Apenas remover token do banco
        return [
            'success' => true,
            'message' => 'Instagram desconectado (token removido)'
        ];
    }

    /**
     * Obter status da instância
     */
    public function getStatus(string $externalInstanceId): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'status' => 'disconnected',
                'message' => 'Instagram não conectado'
            ];
        }

        $validation = $this->authProvider->validateToken($this->accessToken);
        
        if ($validation['success'] && $validation['valid']) {
            return [
                'success' => true,
                'status' => 'connected',
                'user_id' => $validation['user_id'],
                'username' => $validation['username'],
                'account_type' => $validation['account_type'] ?? null
            ];
        }

        return [
            'success' => false,
            'status' => 'disconnected',
            'message' => 'Token inválido ou expirado'
        ];
    }

    /**
     * Atualizar nome da instância (não aplicável)
     */
    public function updateInstanceName(string $externalInstanceId, string $newName): array
    {
        return [
            'success' => false,
            'message' => 'Instagram não permite alterar nome da instância via API'
        ];
    }

    /**
     * Enviar mensagem de texto
     */
    public function sendTextMessage(string $externalInstanceId, string $phone, string $text): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->sendTextMessage($externalInstanceId, $phone, $text);
    }

    /**
     * Enviar mensagem com mídia
     */
    public function sendMediaMessage(string $externalInstanceId, string $phone, string $mediaType, string $mediaUrl, string $caption = ''): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->sendMediaMessage($externalInstanceId, $phone, $mediaUrl, $mediaType, $caption);
    }

    /**
     * Verificar status da instância (interface)
     */
    public function getInstanceStatus(string $externalInstanceId): array
    {
        return $this->getStatus($externalInstanceId);
    }

    /**
     * Verificar se provider está saudável
     */
    public function healthCheck(): bool
    {
        try {
            if (empty($this->appId) || empty($this->appSecret)) {
                return false;
            }

            // Verificar se token é válido (se disponível)
            if ($this->accessToken) {
                $validation = $this->authProvider->validateToken($this->accessToken);
                return $validation['success'] && $validation['valid'];
            }

            // Se não tem token, pelo menos as credenciais estão configuradas
            return true;

        } catch (\Exception $e) {
            Logger::error('Instagram health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lista todas as instâncias do provider
     */
    public function listInstances(): array
    {
        // Instagram não permite listar instâncias via API
        return [
            'success' => true,
            'data' => [],
            'message' => 'Instagram não suporta listagem de instâncias'
        ];
    }

    /**
     * Desconecta uma instância no provider (interface)
     */
    public function disconnectInstance(string $externalInstanceId): array
    {
        return $this->disconnect($externalInstanceId);
    }

    /**
     * Obtém configurações de privacidade (não aplicável)
     */
    public function getPrivacy(string $externalInstanceId): array
    {
        return [
            'success' => false,
            'message' => 'Instagram não suporta configurações de privacidade via API'
        ];
    }

    /**
     * Atualiza configurações de privacidade (não aplicável)
     */
    public function updatePrivacy(string $externalInstanceId, array $settings): array
    {
        return [
            'success' => false,
            'message' => 'Instagram não suporta configurações de privacidade via API'
        ];
    }

    /**
     * Define presença (não aplicável)
     */
    public function setPresence(string $externalInstanceId, string $presence): array
    {
        return [
            'success' => false,
            'message' => 'Instagram não suporta configuração de presença via API'
        ];
    }

    /**
     * Solicita código de autenticação (não aplicável)
     */
    public function requestAuthCode(string $externalInstanceId, string $phoneNumber): array
    {
        return [
            'success' => false,
            'message' => 'Instagram usa OAuth, não códigos de autenticação'
        ];
    }

    /**
     * Obtém QR code como imagem (não aplicável)
     */
    public function getQRCodeImage(string $externalInstanceId): array
    {
        return [
            'success' => false,
            'message' => 'Instagram usa OAuth, não QR code'
        ];
    }

    // ===== MÉTODOS ESPECÍFICOS DO INSTAGRAM =====

    /**
     * Trocar código OAuth por token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $redirectUri = $_ENV['INSTAGRAM_REDIRECT_URI'] ?? 'https://gapi.sockets.com.br/api/instagram/callback';
        
        $result = $this->authProvider->exchangeCodeForToken($code, $this->appId, $this->appSecret, $redirectUri);
        
        if ($result['success']) {
            // Trocar por long-lived token
            $longLivedResult = $this->authProvider->getLongLivedToken($result['access_token'], $this->appSecret);
            
            if ($longLivedResult['success']) {
                $result['access_token'] = $longLivedResult['access_token'];
                $result['expires_in'] = $longLivedResult['expires_in'];
            }
        }
        
        return $result;
    }

    /**
     * Renovar token
     */
    public function refreshToken(): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Nenhum token para renovar'
            ];
        }

        return $this->authProvider->refreshToken($this->accessToken, $this->appSecret);
    }

    /**
     * Obter informações do usuário
     */
    public function getUserInfo(): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->authProvider->getUserInfo($this->accessToken);
    }

    /**
     * Enviar template de botões
     */
    public function sendButtonTemplate(string $externalInstanceId, string $recipientId, string $text, array $buttons): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->sendButtonTemplate($externalInstanceId, $recipientId, $text, $buttons);
    }

    /**
     * Enviar template de lista
     */
    public function sendListTemplate(string $externalInstanceId, string $recipientId, string $text, array $elements, ?string $buttonText = null): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->sendListTemplate($externalInstanceId, $recipientId, $text, $elements, $buttonText);
    }

    /**
     * Enviar template de carrossel
     */
    public function sendCarouselTemplate(string $externalInstanceId, string $recipientId, array $elements): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->sendCarouselTemplate($externalInstanceId, $recipientId, $elements);
    }

    /**
     * Marcar mensagem como lida
     */
    public function markAsRead(string $externalInstanceId, string $recipientId, string $messageId): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Instagram não conectado'
            ];
        }

        return $this->messagingProvider->markAsRead($externalInstanceId, $recipientId, $messageId);
    }

    /**
     * Processar webhook do Instagram
     */
    public function processWebhook(array $headers, string $payload): array
    {
        // Verificar signature
        if (!$this->webhookProvider->verifySignature($headers, $payload, $this->appSecret)) {
            return [
                'success' => false,
                'message' => 'Signature inválida'
            ];
        }

        // Traduzir para formato interno
        $decodedPayload = json_decode($payload, true);
        if (!$decodedPayload) {
            return [
                'success' => false,
                'message' => 'Payload inválido'
            ];
        }

        return $this->webhookProvider->translateToStandardFormat($decodedPayload);
    }
}
