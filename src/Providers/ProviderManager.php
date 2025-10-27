<?php

namespace App\Providers;

use App\Providers\WahaProvider;
use App\Providers\UazapiProvider;
use App\Utils\Logger;

class ProviderManager
{
    /**
     * Obter provider baseado no ID (busca credenciais do banco)
     */
    public static function getProvider(int $providerId): ProviderInterface
    {
        // Buscar provider no banco de dados
        $provider = \App\Models\Provider::findById($providerId);
        
        if (!$provider) {
            Logger::error('Provider not found in database', ['provider_id' => $providerId]);
            throw new \Exception("Provider não encontrado: {$providerId}");
        }
        
        if (!$provider['is_active']) {
            Logger::error('Provider is not active', ['provider_id' => $providerId]);
            throw new \Exception("Provider não está ativo: {$providerId}");
        }
        
        // Instanciar provider baseado no tipo
        switch ($provider['type']) {
            case 'waha':
                return new WahaProvider($provider['base_url'], $provider['api_key']);
            case 'uazapi':
                return new UazapiProvider($provider['base_url'], $provider['api_key']);
            default:
                Logger::error('Unknown provider type', ['provider_id' => $providerId, 'type' => $provider['type']]);
                throw new \Exception("Tipo de provider não suportado: {$provider['type']}");
        }
    }
}
