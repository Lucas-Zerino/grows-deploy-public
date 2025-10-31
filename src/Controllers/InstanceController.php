<?php

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;
use App\Models\Instance;
use App\Providers\ProviderManager;

class InstanceController
{
    /**
     * Formatar nome da instância com company_id
     */
    private static function formatInstanceName($instance)
    {
        return $instance['company_id'] . '-' . $instance['instance_name'];
    }

    /**
     * Criar nova instância
     */
    public static function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados obrigatórios
            if (empty($data['instance_name'])) {
                return Response::error('Nome da instância é obrigatório', 400);
            }
            
            if (empty($data['provider_id'])) {
                return Response::error('ID do provider é obrigatório', 400);
            }
            
            // Buscar provider para verificar tipo
            $provider = \App\Models\Provider::findById($data['provider_id']);
            if (!$provider) {
                return Response::error('Provider não encontrado', 400);
            }
            
            // Para Instagram, phone_number não é obrigatório
            if ($provider['type'] !== 'instagram' && empty($data['phone_number'])) {
                return Response::error('Número de telefone é obrigatório para este tipo de provider', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Criar instância
            $instanceId = Instance::create([
                'company_id' => $company['id'],
                'provider_id' => $data['provider_id'],
                'instance_name' => $data['instance_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'webhook_url' => $data['webhook_url'] ?? null // Manter para compatibilidade
            ]);
            
            if (!$instanceId) {
                return Response::error('Erro ao criar instância', 500);
            }
            
            // Buscar dados da instância criada
            $instance = Instance::getById($instanceId);
            
            if (!$instance) {
                return Response::error('Erro ao buscar dados da instância criada', 500);
            }
            
            // Processar múltiplos webhooks se fornecidos
            $customWebhooks = [];
            if (!empty($data['webhooks']) && is_array($data['webhooks'])) {
                foreach ($data['webhooks'] as $webhookData) {
                    if (!empty($webhookData['url'])) {
                        try {
                            // Salvar webhook no banco (se não especificar eventos, usa todos por padrão)
                            $webhookId = \App\Models\InstanceWebhook::create([
                                'instance_id' => $instanceId,
                                'webhook_url' => $webhookData['url'],
                                'events' => $webhookData['events'] ?? null, // null = usar padrão
                                'is_active' => $webhookData['is_active'] ?? true
                            ]);
                            
                            // Adicionar ao array de customWebhooks mesmo se não salvar no banco (para enviar ao provider)
                            $customWebhooks[] = [
                                'url' => $webhookData['url'],
                                'events' => $webhookData['events'],
                                'hmac' => $webhookData['hmac'] ?? null,
                                'retries' => $webhookData['retries'] ?? null,
                                'customHeaders' => $webhookData['customHeaders'] ?? null
                            ];
                        } catch (\Exception $e) {
                            // Se a tabela não existir, apenas logar mas continuar (webhook ainda será enviado ao provider)
                            Logger::warning('Failed to save webhook to database (table may not exist)', [
                                'instance_id' => $instanceId,
                                'webhook_url' => $webhookData['url'],
                                'error' => $e->getMessage()
                            ]);
                            
                            // Mesmo assim, adicionar ao array para enviar ao provider
                            $customWebhooks[] = [
                                'url' => $webhookData['url'],
                                'events' => $webhookData['events'],
                                'hmac' => $webhookData['hmac'] ?? null,
                                'retries' => $webhookData['retries'] ?? null,
                                'customHeaders' => $webhookData['customHeaders'] ?? null
                            ];
                        }
                    }
                }
            }
            
            // Se passou webhook_url mas não especificou array webhooks, criar webhook na tabela instance_webhooks
            if (empty($customWebhooks) && !empty($data['webhook_url'])) {
                try {
                    $webhookId = \App\Models\InstanceWebhook::create([
                        'instance_id' => $instanceId,
                        'webhook_url' => $data['webhook_url'],
                        'events' => null, // null = usar todos os eventos por padrão
                        'is_active' => true
                    ]);
                    
                    if ($webhookId) {
                        Logger::info('Webhook created for instance', [
                            'instance_id' => $instanceId,
                            'webhook_id' => $webhookId,
                            'webhook_url' => $data['webhook_url']
                        ]);
                    }
                } catch (\Exception $e) {
                    // Se a tabela não existir, apenas logar o erro mas não falhar a criação da instância
                    Logger::warning('Failed to create instance webhook (table may not exist)', [
                        'instance_id' => $instanceId,
                        'webhook_url' => $data['webhook_url'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Criar instância no provider
            try {
                $provider = \App\Models\Provider::findById($instance['provider_id']);
                if ($provider) {
                    Logger::info('Provider found for instance creation', [
                        'instance_id' => $instanceId,
                        'provider_id' => $provider['id'],
                        'provider_type' => $provider['type']
                    ]);
                    
                    $providerInstance = \App\Providers\ProviderManager::getProvider($provider['id']);
                    
                    // Criar sessão no provider com nome formatado (company_id-instance_name)
                    $formattedInstanceName = $company['id'] . '-' . $instance['instance_name'];
                    
                    Logger::info('Creating instance in provider', [
                        'instance_id' => $instanceId,
                        'formatted_name' => $formattedInstanceName,
                        'provider_type' => $provider['type']
                    ]);
                    
                    // Passar external_instance_id (formattedInstanceName) para o webhook URL em vez do ID numérico
                    $providerResult = $providerInstance->createInstance($formattedInstanceName, $formattedInstanceName, $customWebhooks);
                    
                    Logger::info('Provider createInstance result', [
                        'instance_id' => $instanceId,
                        'provider_result' => $providerResult
                    ]);
                    
                    if ($providerResult && isset($providerResult['success']) && $providerResult['success']) {
                        // Atualizar external_instance_id se retornado pelo provider
                        if (isset($providerResult['instance_id'])) {
                            Instance::update($instanceId, ['external_instance_id' => $providerResult['instance_id']]);
                        }
                        
                        // Atualizar status para connected se criado com sucesso
                        Instance::updateStatus($instanceId, 'connected');
                        
                        Logger::info('Instance created in provider', [
                            'instance_id' => $instanceId,
                            'provider_type' => $provider['type'],
                            'provider_result' => $providerResult
                        ]);
                    } else {
                        Logger::warning('Failed to create instance in provider', [
                            'instance_id' => $instanceId,
                            'provider_type' => $provider['type'],
                            'provider_result' => $providerResult,
                            'success_field' => isset($providerResult['success']) ? $providerResult['success'] : 'not_set'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Logger::warning('Failed to create instance in provider', [
                    'instance_id' => $instanceId,
                    'error' => $e->getMessage()
                ]);
                // Continuar mesmo se falhar no provider
            }
            
            // Buscar dados atualizados da instância
            $instance = Instance::getById($instanceId);
            
            Logger::info('Instance created', [
                'instance_id' => $instance['id'],
                'company_id' => $company['id'],
                'instance_name' => $data['instance_name']
            ]);
            
            // Preparar resposta com campos apropriados para o tipo de provider
            $responseData = $instance;
            
            // Remover campos do Instagram se não for provider Instagram
            if ($provider['type'] !== 'instagram') {
                unset($responseData['instagram_user_id']);
                unset($responseData['instagram_username']);
            }
            
            // Remover campos do Facebook se não for provider Facebook
            if ($provider['type'] !== 'facebook') {
                unset($responseData['facebook_page_id']);
                unset($responseData['facebook_page_name']);
            }
            
            // Para Instagram, adicionar auth_url na resposta
            if ($provider['type'] === 'instagram') {
                try {
                    $instagramApp = \App\Models\InstagramApp::getByCompanyId($company['id']);
                    if ($instagramApp) {
                        $redirectUri = $_ENV['INSTAGRAM_REDIRECT_URI'] ?? 'https://gapi.sockets.com.br/api/instagram/callback';
                        $scopes = [
                            'instagram_business_basic',
                            'instagram_business_manage_messages',
                            'instagram_business_manage_comments',
                            'instagram_business_content_publish'
                        ];
                        
                        $authUrl = "https://www.instagram.com/oauth/authorize?" . http_build_query([
                            'client_id' => $instagramApp['app_id'],
                            'redirect_uri' => $redirectUri,
                            'response_type' => 'code',
                            'scope' => implode(',', $scopes),
                            'state' => base64_encode(json_encode([
                                'company_id' => $company['id'],
                                'app_id' => $instagramApp['id'],
                                'instance_id' => $instance['id']
                            ]))
                        ]);
                        
                        $responseData['auth_url'] = $authUrl;
                        $responseData['requires_oauth'] = true;
                    }
                } catch (\Exception $e) {
                    Logger::warning('Failed to generate Instagram auth URL', [
                        'error' => $e->getMessage(),
                        'instance_id' => $instance['id']
                    ]);
                }
            }
            
            return Response::json([
                'success' => true,
                'message' => 'Instância criada com sucesso',
                'data' => $responseData
            ], 201);
            
        } catch (\Exception $e) {
            Logger::error('Failed to create instance', [
                'error' => $e->getMessage()
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Listar instâncias da empresa
     */
    public static function list()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            $instances = Instance::getByCompanyId($company['id']);
            
            return Response::json([
                'success' => true,
                'data' => $instances,
                'total' => count($instances)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to list instances', [
                'error' => $e->getMessage()
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Buscar instância por ID
     */
    public static function get($id)
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            $instance = Instance::getById($id);
            
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            return Response::json([
                'success' => true,
                'data' => $instance
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get instance', [
                'error' => $e->getMessage(),
                'instance_id' => $id
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Conectar instância ao WhatsApp
     */
    public function connect()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (isset($data['phone']) && !is_string($data['phone'])) {
                return Response::error('Phone must be a string', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e conectar
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->connect($instance['external_instance_id'] ?? self::formatInstanceName($instance), $data['phone'] ?? null);
            
            // Atualizar status da instância
            Instance::updateStatus($instance['id'], 'connecting');
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance connect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Desconectar instância do WhatsApp
     */
    public function disconnect()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e desconectar
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->disconnect($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            // Atualizar status da instância
            Instance::updateStatus($instance['id'], 'disconnected');
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance disconnect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Verificar status da instância
     * Retorna formato padronizado sempre o mesmo, independente do provider
     * 
     * Formato de resposta:
     * {
     *   "instance": {
     *     "id": "...",
     *     "token": "...",
     *     "status": "connected", // sempre um dos: stopped, connecting, scan_qr_code, connected, failed
     *     "name": "...",
     *     "qrcode": "...", // apenas quando status = scan_qr_code
     *     "profileName": "...",
     *     ...
     *   },
     *   "status": {
     *     "connected": true,
     *     "loggedIn": true,
     *     "jid": {...}
     *   }
     * }
     */
    public function status()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e buscar status (retorna formato padronizado)
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $statusResult = $provider->getStatus($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            // Se houver erro no provider, retornar erro
            if (!$statusResult['success']) {
                return Response::error($statusResult['message'] ?? 'Erro ao obter status', 500);
            }
            
            // Formatar resposta final usando formato padronizado + dados do banco
            $formattedResponse = $this->formatStatusResponse($instance, $statusResult);
            
            return Response::success($formattedResponse);
            
        } catch (\Exception $e) {
            Logger::error('Instance status failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Formatar resposta de status no formato padronizado
     * Usa dados do banco + formato padronizado retornado pelo provider
     * 
     * @param array $instance Dados da instância do banco
     * @param array $statusResult Formato padronizado retornado pelo provider
     * @return array Resposta formatada
     */
    private function formatStatusResponse(array $instance, array $statusResult): array
    {
        return [
            'instance' => [
                'id' => (string)$instance['id'],
                'token' => $instance['token'],
                'status' => $statusResult['status'] ?? 'unknown', // Status padronizado
                'name' => $instance['instance_name'],
                'qrcode' => $statusResult['qrcode'] ?? null, // Apenas quando scan_qr_code
                'paircode' => null, // WAHA não usa paircode
                'profileName' => $statusResult['profile_name'] ?? null,
                'profilePicUrl' => $statusResult['profile_pic_url'] ?? null,
                'isBusiness' => false, // WAHA não fornece essa informação
                'platform' => 'WAHA',
                'systemName' => 'waha',
                'owner' => null,
                'lastDisconnect' => null,
                'lastDisconnectReason' => null,
                'created' => $instance['created_at'],
                'updated' => $instance['updated_at'],
            ],
            'status' => [
                'connected' => $statusResult['connected'] ?? false,
                'loggedIn' => $statusResult['loggedIn'] ?? false,
                'jid' => $statusResult['jid'] ?? null
            ]
        ];
    }
    
    /**
     * Buscar QR code da instância
     */
    public function getQRCode()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e buscar QR code
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->getQRCode($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance QR code failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Buscar imagem do QR code
     */
    public function getQRCodeImage()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e buscar imagem QR code
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->getQRCodeImage($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            // Verificar se o resultado foi bem-sucedido
            if (!$result['success']) {
                return Response::error($result['message'] ?? 'Erro ao obter QR code');
            }
            
            // Verificar se há QR code
            if (empty($result['qrcode'])) {
                return Response::error('QR code não disponível');
            }
            
            // Extrair apenas o base64 (remover o prefixo data:image/png;base64,)
            $base64Data = $result['qrcode'];
            if (strpos($base64Data, 'data:image/png;base64,') === 0) {
                $base64Data = substr($base64Data, 22); // Remove o prefixo
            }
            
            // Retornar imagem diretamente
            header('Content-Type: image/png');
            echo base64_decode($base64Data);
            exit;
            
        } catch (\Exception $e) {
            Logger::error('Instance QR code image failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Atualizar nome da instância
     */
    public function updateName()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['name']) || !is_string($data['name']) || empty(trim($data['name']))) {
                return Response::error('Name is required and must be a non-empty string', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Atualizar nome no banco
            Instance::update($instance['id'], ['instance_name' => $data['name']]);
            
            // Detectar provider e atualizar nome
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $formattedInstanceName = $instance['company_id'] . '-' . $data['name'];
            $result = $provider->updateInstanceName($instance['external_instance_id'] ?? $instance['instance_name'], $formattedInstanceName);
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance update name failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Deletar instância por ID (API)
     */
    public static function deleteById($id)
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Validar se o ID é um número válido
            if (!is_numeric($id)) {
                return Response::error('ID da instância deve ser um número válido', 400);
            }
            
            $id = (int) $id;
            
            // Buscar instância
            $instance = Instance::getById($id);
            
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Desconectar e deletar instância no provider
            try {
                $provider = \App\Models\Provider::findById($instance['provider_id']);
                if ($provider) {
                    $providerManager = new \App\Providers\ProviderManager();
                    $providerInstance = $providerManager->getProvider($provider['id']);
                    
                    if ($providerInstance) {
                        $externalInstanceId = $instance['external_instance_id'] ?? self::formatInstanceName($instance);
                        
                        // Desconectar no provider se estiver conectada
                        if (in_array($instance['status'], ['connected', 'active', 'connecting'])) {
                            $disconnectResult = $providerInstance->disconnectInstance($externalInstanceId);
                            
                            if ($disconnectResult) {
                                Logger::info('Instance disconnected from provider before deletion', [
                                    'instance_id' => $id,
                                    'provider_type' => $provider['type'],
                                    'external_instance_id' => $externalInstanceId
                                ]);
                            }
                        }
                        
                        // Deletar no provider (sempre tentar deletar, mesmo se não estiver conectada)
                        $deleteResult = $providerInstance->deleteInstance($externalInstanceId);
                        
                        if ($deleteResult) {
                            Logger::info('Instance deleted from provider', [
                                'instance_id' => $id,
                                'provider_type' => $provider['type'],
                                'external_instance_id' => $externalInstanceId
                            ]);
                        } else {
                            Logger::warning('Failed to delete instance from provider', [
                                'instance_id' => $id,
                                'provider_type' => $provider['type'],
                                'external_instance_id' => $externalInstanceId
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Logger::warning('Failed to delete instance from provider', [
                    'instance_id' => $id,
                    'error' => $e->getMessage()
                ]);
                // Continuar com a deleção do banco mesmo se a deleção no provider falhar
            }
            
            // Deletar instância
            $result = Instance::delete($id);
            
            if ($result) {
                Logger::info('Instance deleted by company', [
                    'instance_id' => $id,
                    'company_id' => $company['id']
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Instância removida com sucesso'
                ]);
            } else {
                return Response::error('Erro ao remover instância', 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete instance', [
                'error' => $e->getMessage(),
                'instance_id' => $id
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Deletar instância (por token)
     */
    public function delete()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e deletar
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->deleteInstance($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            // Deletar do banco
            Instance::delete($instance['id']);
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Buscar configurações de privacidade
     */
    public function getPrivacySettings()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e buscar configurações
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->getPrivacy($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance privacy settings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Atualizar configurações de privacidade
     */
    public function updatePrivacySettings()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            $validKeys = ['readreceipts', 'groups', 'calladd', 'last', 'status', 'profile'];
            $validValues = ['all', 'contacts', 'contact_blacklist', 'none'];
            
            foreach ($data as $key => $value) {
                if (!in_array($key, $validKeys)) {
                    return Response::error("Invalid privacy setting key: $key", 400);
                }
                if (!in_array($value, $validValues)) {
                    return Response::error("Invalid privacy setting value for $key: $value", 400);
                }
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e atualizar configurações
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->updatePrivacy($instance['external_instance_id'] ?? self::formatInstanceName($instance), $data);
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance update privacy settings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Atualizar status de presença
     */
    public function updatePresence()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['status']) || !is_string($data['status']) || empty(trim($data['status']))) {
                return Response::error('Status is required and must be a non-empty string', 400);
            }
            
            $validStatuses = ['available', 'composing', 'recording', 'paused'];
            if (!in_array($data['status'], $validStatuses)) {
                return Response::error('Invalid status value', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            Logger::info('Instance connect attempt', [
                'auth_header' => $authHeader,
                'token' => $token
            ]);
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($token);
        if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e atualizar presença
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->setPresence(
                $instance['external_instance_id'] ?? self::formatInstanceName($instance), 
                $data['status'], 
                $data['message'] ?? null
            );
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance update presence failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
}
