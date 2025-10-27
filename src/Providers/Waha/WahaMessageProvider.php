<?php

namespace App\Providers\Waha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class WahaMessageProvider
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
     * Enviar mensagem de texto
     * Padrão UAZAPI: POST /send/text
     */
    public function sendText(string $externalInstanceId, string $to, string $message): array
    {
        try {
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/send-message", [
                'json' => [
                    'chatId' => $to,
                    'text' => $message
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA text message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send text message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar mídia (imagem, vídeo, áudio, documento)
     * Padrão UAZAPI: POST /send/media
     */
    public function sendMedia(string $externalInstanceId, string $to, string $mediaUrl, ?string $caption = null, ?string $type = null): array
    {
        try {
            $payload = [
                'chatId' => $to,
                'url' => $mediaUrl
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            if ($type) {
                $payload['type'] = $type;
            }
            
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/send-message", [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA media message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'media_url' => $mediaUrl,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Mídia enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send media message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'media_url' => $mediaUrl,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar mídia: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar contato
     * Padrão UAZAPI: POST /send/contact
     */
    public function sendContact(string $externalInstanceId, string $to, array $contact): array
    {
        try {
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/send-message", [
                'json' => [
                    'chatId' => $to,
                    'contact' => $contact
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA contact message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'contact_name' => $contact['name'] ?? 'Unknown',
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Contato enviado com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send contact message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar contato: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar localização
     * Padrão UAZAPI: POST /send/location
     */
    public function sendLocation(string $externalInstanceId, string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        try {
            $payload = [
                'chatId' => $to,
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];
            
            if ($name) {
                $payload['location']['name'] = $name;
            }
            
            if ($address) {
                $payload['location']['address'] = $address;
            }
            
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/send-message", [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA location message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Localização enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send location message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar localização: ' . $e->getMessage()
            ];
        }
    }
}
