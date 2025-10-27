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
                'webhook_url' => $data['webhook_url'] ?? null
            ]);
            
            if (!$instanceId) {
                return Response::error('Erro ao criar instância', 500);
            }
            
            // Buscar dados da instância criada
            $instance = Instance::getById($instanceId);
            
            if (!$instance) {
                return Response::error('Erro ao buscar dados da instância criada', 500);
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
                    
                    $providerResult = $providerInstance->createInstance($formattedInstanceName, $data['phone_number'] ?? null);
                    
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
            
            return Response::json([
                'success' => true,
                'message' => 'Instância criada com sucesso',
                'data' => $instance
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
     */
    public function status()
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
            
            // Detectar provider e buscar status
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->getStatus($instance['external_instance_id'] ?? self::formatInstanceName($instance));
            
            // Adicionar informações da instância
            $result['instance'] = $instance;
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Instance status failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
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
            
            // Desconectar instância no provider se estiver conectada
            if (in_array($instance['status'], ['connected', 'active', 'connecting'])) {
                try {
                    $provider = \App\Models\Provider::findById($instance['provider_id']);
                    if ($provider) {
                        $providerManager = new \App\Providers\ProviderManager();
                        $providerInstance = $providerManager->getProvider($provider['id']);
                        
                        if ($providerInstance) {
                            // Desconectar no provider
                            $disconnectResult = $providerInstance->disconnectInstance($instance['external_instance_id'] ?? self::formatInstanceName($instance));
                            
                            if ($disconnectResult) {
                                Logger::info('Instance disconnected from provider before deletion', [
                                    'instance_id' => $id,
                                    'provider_type' => $provider['type'],
                                    'external_instance_id' => $instance['external_instance_id']
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Logger::warning('Failed to disconnect instance from provider before deletion', [
                        'instance_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                    // Continuar com a deleção mesmo se a desconexão falhar
                }
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
