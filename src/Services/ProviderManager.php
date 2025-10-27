<?php

namespace App\Services;

use App\Models\Provider as ProviderModel;
use App\Providers\ProviderInterface;
use App\Providers\WahaProvider;
use App\Providers\UazapiProvider;
use App\Utils\Logger;

class ProviderManager
{
    private static array $instances = [];
    
    public static function getProvider(int $providerId): ?ProviderInterface
    {
        if (isset(self::$instances[$providerId])) {
            return self::$instances[$providerId];
        }
        
        $provider = ProviderModel::findById($providerId);
        
        if (!$provider) {
            Logger::error('Provider not found', ['provider_id' => $providerId]);
            return null;
        }
        
        $instance = match($provider['type']) {
            'waha' => new WahaProvider($provider['base_url'], $provider['api_key']),
            'uazapi' => new UazapiProvider($provider['base_url'], $provider['api_key']),
            default => null,
        };
        
        if ($instance) {
            self::$instances[$providerId] = $instance;
        }
        
        return $instance;
    }
    
    public static function sendMessage(int $providerId, string $externalInstanceId, array $messageData): array
    {
        $provider = self::getProvider($providerId);
        
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }
        
        try {
            if ($messageData['message_type'] === 'text') {
                return $provider->sendTextMessage(
                    $externalInstanceId,
                    $messageData['phone_to'],
                    $messageData['content']
                );
            } else {
                return $provider->sendMediaMessage(
                    $externalInstanceId,
                    $messageData['phone_to'],
                    $messageData['message_type'],
                    $messageData['media_url'],
                    $messageData['content'] ?? ''
                );
            }
        } catch (\Exception $e) {
            Logger::error('Provider send message failed', [
                'provider_id' => $providerId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

