<?php

namespace App\Services;

use App\Models\Provider;
use App\Utils\Logger;

class HealthCheckService
{
    public static function checkAllProviders(): array
    {
        $providers = Provider::findAll(true);
        $results = [];
        
        foreach ($providers as $providerData) {
            $provider = ProviderManager::getProvider($providerData['id']);
            
            if (!$provider) {
                continue;
            }
            
            $isHealthy = $provider->healthCheck();
            $status = $isHealthy ? 'healthy' : 'unhealthy';
            
            Provider::updateHealthStatus($providerData['id'], $status);
            
            $results[] = [
                'provider_id' => $providerData['id'],
                'name' => $providerData['name'],
                'type' => $providerData['type'],
                'status' => $status,
                'base_url' => $providerData['base_url'],
            ];
            
            Logger::info("Provider health check completed", [
                'provider_id' => $providerData['id'],
                'status' => $status,
            ]);
        }
        
        return $results;
    }
    
    public static function checkProvider(int $providerId): ?array
    {
        $providerData = Provider::findById($providerId);
        
        if (!$providerData) {
            return null;
        }
        
        $provider = ProviderManager::getProvider($providerId);
        
        if (!$provider) {
            return null;
        }
        
        $isHealthy = $provider->healthCheck();
        $status = $isHealthy ? 'healthy' : 'unhealthy';
        
        Provider::updateHealthStatus($providerId, $status);
        
        return [
            'provider_id' => $providerId,
            'name' => $providerData['name'],
            'type' => $providerData['type'],
            'status' => $status,
            'base_url' => $providerData['base_url'],
        ];
    }
}

