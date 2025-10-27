<?php

namespace App\Providers\Waha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class WahaPrivacyProvider
{
    private Client $client;
    private string $baseUrl;
    private ?string $apiKey;
    
    public function __construct(Client $client, string $baseUrl, ?string $apiKey)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Buscar configurações de privacidade
     * Padrão UAZAPI: GET /instance/privacy
     */
    public function getPrivacySettings(string $externalInstanceId): array
    {
        try {
            $response = $this->client->get("/api/sessions/{$externalInstanceId}/privacy");
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA privacy settings retrieved', [
                'external_id' => $externalInstanceId
            ]);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA get privacy settings failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao buscar configurações de privacidade: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar configurações de privacidade
     * Padrão UAZAPI: PUT /instance/privacy
     */
    public function updatePrivacySettings(string $externalInstanceId, array $settings): array
    {
        try {
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/privacy", [
                'json' => $settings
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA privacy settings updated', [
                'external_id' => $externalInstanceId,
                'settings' => $settings
            ]);
            
            return [
                'success' => true,
                'message' => 'Configurações de privacidade atualizadas',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA update privacy settings failed', [
                'external_id' => $externalInstanceId,
                'settings' => $settings,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar configurações de privacidade: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar status de presença
     * Padrão UAZAPI: PUT /instance/presence
     */
    public function updatePresence(string $externalInstanceId, string $status, ?string $message = null): array
    {
        try {
            $payload = ['status' => $status];
            if ($message) {
                $payload['message'] = $message;
            }
            
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/presence", [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA presence updated', [
                'external_id' => $externalInstanceId,
                'status' => $status,
                'message' => $message
            ]);
            
            return [
                'success' => true,
                'message' => 'Status de presença atualizado',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA update presence failed', [
                'external_id' => $externalInstanceId,
                'status' => $status,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar status de presença: ' . $e->getMessage()
            ];
        }
    }
}
