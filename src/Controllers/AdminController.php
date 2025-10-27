<?php

namespace App\Controllers;

use App\Models\Company;
use App\Models\Provider;
use App\Models\Instance;
use App\Middleware\AuthMiddleware;
use App\Services\QueueManagerService;
use App\Services\HealthCheckService;
use App\Services\ProviderManager;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;

class AdminController
{
    // ===== COMPANIES =====
    
    public static function createCompany(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $input = Router::getJsonInput();
        
        if (empty($input['name'])) {
            Response::validationError(['name' => 'Company name is required']);
            return;
        }
        
        try {
            $company = Company::create($input['name']);
            
            // Criar filas da empresa
            QueueManagerService::createCompanyQueues($company['id']);
            
            Logger::info('Company created', [
                'company_id' => $company['id'],
                'name' => $company['name'],
            ]);
            
            Response::created($company, 'Company created successfully');
        } catch (\Exception $e) {
            Logger::error('Failed to create company', [
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to create company');
        }
    }
    
    public static function listCompanies(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $params = Router::getQueryParams();
        $limit = min((int) ($params['limit'] ?? 100), 500);
        $offset = (int) ($params['offset'] ?? 0);
        
        $companies = Company::findAll($limit, $offset);
        
        Response::success($companies);
    }
    
    public static function getCompany(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $company = Company::findById((int) $id);
        
        if (!$company) {
            Response::notFound('Company not found');
            return;
        }
        
        Response::success($company);
    }
    
    public static function updateCompanyStatus(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $input = Router::getJsonInput();
        
        if (empty($input['status'])) {
            Response::validationError(['status' => 'Status is required']);
            return;
        }
        
        $updated = Company::updateStatus((int) $id, $input['status']);
        
        if (!$updated) {
            Response::notFound('Company not found');
            return;
        }
        
        Logger::info('Company status updated', [
            'company_id' => $id,
            'status' => $input['status'],
        ]);
        
        Response::success(null, 'Company status updated');
    }
    
    public static function deleteCompany(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        try {
            // Deletar filas da empresa
            QueueManagerService::deleteCompanyQueues((int) $id);
            
            // Deletar empresa
            $deleted = Company::delete((int) $id);
            
            if (!$deleted) {
                Response::notFound('Company not found');
                return;
            }
            
            Logger::info('Company deleted', ['company_id' => $id]);
            
            Response::success(null, 'Company deleted successfully');
        } catch (\Exception $e) {
            Logger::error('Failed to delete company', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to delete company');
        }
    }
    
    // ===== PROVIDERS =====
    
    public static function createProvider(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $input = Router::getJsonInput();
        
        // Validação
        $errors = [];
        if (empty($input['type'])) {
            $errors['type'] = 'Provider type is required';
        } elseif (!in_array($input['type'], ['waha', 'uazapi'])) {
            $errors['type'] = 'Invalid provider type';
        }
        if (empty($input['name'])) {
            $errors['name'] = 'Provider name is required';
        }
        if (empty($input['base_url'])) {
            $errors['base_url'] = 'Base URL is required';
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        try {
            $provider = Provider::create($input);
            
            Logger::info('Provider created', [
                'provider_id' => $provider['id'],
                'type' => $provider['type'],
            ]);
            
            Response::created($provider, 'Provider created successfully');
        } catch (\Exception $e) {
            Logger::error('Failed to create provider', [
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to create provider');
        }
    }
    
    public static function listProviders(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $providers = Provider::findAll();
        
        Response::success($providers);
    }
    
    public static function getProvider(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $provider = Provider::findById((int) $id);
        
        if (!$provider) {
            Response::notFound('Provider not found');
            return;
        }
        
        Response::success($provider);
    }
    
    public static function updateProvider(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $input = Router::getJsonInput();
        
        $updated = Provider::update((int) $id, $input);
        
        if (!$updated) {
            Response::notFound('Provider not found');
            return;
        }
        
        Logger::info('Provider updated', ['provider_id' => $id]);
        
        Response::success(null, 'Provider updated successfully');
    }
    
    public static function deleteProvider(string $id): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        $deleted = Provider::delete((int) $id);
        
        if (!$deleted) {
            Response::notFound('Provider not found');
            return;
        }
        
        Logger::info('Provider deleted', ['provider_id' => $id]);
        
        Response::success(null, 'Provider deleted successfully');
    }
    
    // ===== HEALTH & MONITORING =====
    
    public static function health(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        try {
            $providerHealth = HealthCheckService::checkAllProviders();
            $queueMetrics = QueueManagerService::getQueueMetrics();
            
            Response::success([
                'status' => 'operational',
                'providers' => $providerHealth,
                'queues' => $queueMetrics,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Logger::error('Health check failed', [
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Health check failed');
        }
    }
    
    // ===== INSTANCES MANAGEMENT (SUPERADMIN) =====
    
    /**
     * Listar todas as instâncias de um provider
     * GET /api/admin/providers/{id}/instances
     */
    public static function listProviderInstances(string $providerId): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        try {
            $provider = Provider::findById((int) $providerId);
            
            if (!$provider) {
                Response::notFound('Provider not found');
                return;
            }
            
            $instances = Instance::findByProvider((int) $providerId);
            
            Response::json([
                'success' => true,
                'data' => [
                    'provider' => $provider,
                    'instances' => $instances,
                    'total' => count($instances),
                ],
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to list provider instances', [
                'provider_id' => $providerId,
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to list provider instances');
        }
    }
    
    /**
     * Listar todas as instâncias do sistema
     * GET /api/admin/instances
     */
    public static function listAllInstances(): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;

        try {
            $providers = Provider::findAll(true); // apenas ativos
            $allInstances = [];

            foreach ($providers as $provider) {
                $providerClient = ProviderManager::getProvider($provider['id']);
                $result = $providerClient->listInstances();

                if ($result['success']) {
                    $instances = $result['data'] ?? $result['instances'] ?? [];
                    foreach ($instances as $instance) {
                        $allInstances[] = [
                            'provider_id' => $provider['id'],
                            'provider_name' => $provider['name'],
                            'provider_type' => $provider['type'],
                            'external_instance_id' => $instance['name'] ?? $instance['instanceName'] ?? $instance['id'] ?? null,
                            'status' => $instance['status'] ?? 'unknown',
                            'data' => $instance,
                        ];
                    }
                } else {
                    Logger::warning('Failed to list instances from provider', [
                        'provider_id' => $provider['id'],
                        'provider_name' => $provider['name'],
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }

            Response::json([
                'success' => true,
                'data' => $allInstances,
                'total' => count($allInstances),
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to list all instances', [
                'error' => $e->getMessage(),
            ]);

            Response::serverError('Failed to list instances');
        }
    }
    
    /**
     * Desconectar instância específica (superadmin)
     * POST /api/admin/instances/{id}/disconnect
     */
    public static function disconnectInstance(string $providerId, string $externalInstanceId): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        try {
            $provider = Provider::findById((int) $providerId);
            
            if (!$provider) {
                Response::notFound('Provider not found');
                return;
            }
            
            // Desconectar no provider
            $providerClient = ProviderManager::getProvider($provider['id']);
            $result = $providerClient->disconnectInstance($externalInstanceId);
            
            if (!$result['success']) {
                Response::error('Failed to disconnect instance: ' . ($result['error'] ?? 'Unknown error'), 400);
                return;
            }
            
            Logger::warning('Instance disconnected by superadmin', [
                'provider_id' => $provider['id'],
                'provider_name' => $provider['name'],
                'external_instance_id' => $externalInstanceId,
            ]);
            
            Response::json([
                'success' => true,
                'message' => 'Instance disconnected successfully',
                'data' => [
                    'provider_id' => $provider['id'],
                    'provider_name' => $provider['name'],
                    'external_instance_id' => $externalInstanceId,
                    'status' => 'disconnected',
                ],
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to disconnect instance', [
                'provider_id' => $providerId,
                'external_instance_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to disconnect instance');
        }
    }
    
    /**
     * Deletar instância específica (superadmin)
     * DELETE /api/admin/instances/{id}
     */
    public static function deleteInstance(string $providerId, string $externalInstanceId): void
    {
        if (!AuthMiddleware::requireSuperadmin()) return;
        
        try {
            $provider = Provider::findById((int) $providerId);
            
            if (!$provider) {
                Response::notFound('Provider not found');
                return;
            }
            
            // Deletar no provider
            $providerClient = ProviderManager::getProvider($provider['id']);
            $deleted = $providerClient->deleteInstance($externalInstanceId);
            
            Logger::warning('Instance deleted by superadmin', [
                'provider_id' => $provider['id'],
                'provider_name' => $provider['name'],
                'external_instance_id' => $externalInstanceId,
                'provider_deleted' => $deleted,
            ]);
            
            Response::json([
                'success' => true,
                'message' => 'Instance deleted successfully',
                'data' => [
                    'provider_id' => $provider['id'],
                    'provider_name' => $provider['name'],
                    'external_instance_id' => $externalInstanceId,
                    'provider_deleted' => $deleted,
                ],
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete instance', [
                'provider_id' => $providerId,
                'external_instance_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            Response::serverError('Failed to delete instance');
        }
    }
}

