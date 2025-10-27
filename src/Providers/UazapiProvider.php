<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class UazapiProvider implements ProviderInterface
{
    private Client $client;
    private string $baseUrl;
    private ?string $apiKey;
    
    public function __construct(string $baseUrl, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => array_filter([
                'Content-Type' => 'application/json',
                'SecretKey' => $this->apiKey,
            ]),
        ]);
    }
    
    public function createInstance(string $instanceName, ?string $instanceId = null): array
    {
        try {
            // Configurar webhook do backend se BACKEND_URL estiver configurado
            $payload = ['instanceName' => $instanceName];
            $backendUrl = $_ENV['BACKEND_URL'] ?? null;
            
            if ($backendUrl && $instanceId) {
                $payload['webhook'] = "{$backendUrl}/webhook/uazapi/{$instanceId}";

                Logger::info('Registering UAZAPI webhook', [
                    'instance_name' => $instanceName,
                    'webhook_url' => $payload['webhook'],
                ]);
            }

            $response = $this->client->post('/instance/create', [
                'json' => $payload,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI instance created', [
                'instance_name' => $instanceName,
                'webhook_registered' => !empty($backendUrl && $instanceId),
            ]);
            
            return [
                'success' => true,
                'instance_id' => $data['instance']['instanceName'] ?? $instanceName,  // ID da instância no UAZAPI
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI create instance failed', [
                'instance_name' => $instanceName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function deleteInstance(string $externalInstanceId): bool
    {
        try {
            $this->client->delete("/instance/delete/{$externalInstanceId}");
            
            Logger::info('UAZAPI instance deleted', [
                'external_id' => $externalInstanceId,
            ]);
            
            return true;
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI delete instance failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function sendTextMessage(string $externalInstanceId, string $phone, string $text): array
    {
        try {
            $response = $this->client->post("/message/sendText/{$externalInstanceId}", [
                'json' => [
                    'number' => $this->formatPhone($phone),
                    'text' => $text,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::debug('UAZAPI text message sent', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
            ]);
            
            return [
                'success' => true,
                'message_id' => $data['messageId'] ?? null,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI send text message failed', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function sendMediaMessage(
        string $externalInstanceId,
        string $phone,
        string $mediaType,
        string $mediaUrl,
        string $caption = ''
    ): array {
        try {
            $endpoint = match($mediaType) {
                'image' => '/message/sendImage',
                'video' => '/message/sendVideo',
                'audio' => '/message/sendAudio',
                'document' => '/message/sendDocument',
                default => '/message/sendFile',
            };
            
            $response = $this->client->post("{$endpoint}/{$externalInstanceId}", [
                'json' => [
                    'number' => $this->formatPhone($phone),
                    'url' => $mediaUrl,
                    'caption' => $caption,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::debug('UAZAPI media message sent', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'media_type' => $mediaType,
            ]);
            
            return [
                'success' => true,
                'message_id' => $data['messageId'] ?? null,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI send media message failed', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function getInstanceStatus(string $externalInstanceId): array
    {
        try {
            $response = $this->client->get("/instance/status/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'status' => $data['status'] ?? 'unknown',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI get instance status failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->get('/instance/list');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Logger::warning('UAZAPI health check failed', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function getQRCode(string $externalInstanceId): ?string
    {
        try {
            $response = $this->client->get("/instance/qrcode/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['qrcode'] ?? null;
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI get QR code failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    public function listInstances(): array
    {
        try {
            $response = $this->client->get('/instance/list');
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI instances listed', [
                'count' => count($data['instances'] ?? []),
            ]);
            
            return [
                'success' => true,
                'instances' => $data['instances'] ?? [],
                'total' => count($data['instances'] ?? []),
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI list instances failed', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'instances' => [],
                'total' => 0,
            ];
        }
    }

    public function disconnectInstance(string $externalInstanceId): array
    {
        try {
            $response = $this->client->post("/instance/disconnect/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI instance disconnected', [
                'external_id' => $externalInstanceId,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI disconnect instance failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Connect instance to WhatsApp (start session and get QR code)
     */
    public function connect(string $externalInstanceId, ?string $phone = null): array
    {
        try {
            $response = $this->client->post("/instance/connect/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI session started', [
                'external_id' => $externalInstanceId,
            ]);
            
            return [
                'success' => true,
                'status' => 'connecting',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI connect failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Disconnect instance from WhatsApp
     */
    public function disconnect(string $externalInstanceId): array
    {
        try {
            $response = $this->client->post("/instance/disconnect/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI session disconnected', [
                'external_id' => $externalInstanceId,
            ]);
            
            return [
                'success' => true,
                'status' => 'disconnected',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI disconnect failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get instance status and QR code
     */
    public function getStatus(string $externalInstanceId): array
    {
        try {
            $response = $this->client->get("/instance/status/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::debug('UAZAPI status retrieved', [
                'external_id' => $externalInstanceId,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI get status failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update instance name
     */
    public function updateInstanceName(string $externalInstanceId, string $newName): array
    {
        try {
            $response = $this->client->put("/instance/update/{$externalInstanceId}", [
                'json' => [
                    'instanceName' => $newName
                ]
            ]);
            
            Logger::info('UAZAPI instance name updated', [
                'external_id' => $externalInstanceId,
                'new_name' => $newName,
            ]);
            
            return [
                'success' => true,
                'message' => 'Instance name updated successfully',
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI update name failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get privacy settings
     */
    public function getPrivacy(string $externalInstanceId): array
    {
        try {
            $response = $this->client->get("/instance/privacy/{$externalInstanceId}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI get privacy failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update privacy settings
     */
    public function updatePrivacy(string $externalInstanceId, array $settings): array
    {
        try {
            $response = $this->client->put("/instance/privacy/{$externalInstanceId}", [
                'json' => $settings
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI privacy updated', [
                'external_id' => $externalInstanceId,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI update privacy failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Set presence (online/offline)
     */
    public function setPresence(string $externalInstanceId, string $presence): array
    {
        try {
            $response = $this->client->post("/instance/presence/{$externalInstanceId}", [
                'json' => [
                    'presence' => $presence
                ]
            ]);
            
            Logger::info('UAZAPI presence updated', [
                'external_id' => $externalInstanceId,
                'presence' => $presence,
            ]);
            
            return [
                'success' => true,
                'message' => 'Presence updated successfully',
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI set presence failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Request authentication code by phone number
     */
    public function requestAuthCode(string $externalInstanceId, string $phoneNumber): array
    {
        try {
            // UAZAPI não tem endpoint específico para código
            // Usa o connect com phone number que gera código automaticamente
            $response = $this->client->post("/instance/connect/{$externalInstanceId}", [
                'json' => [
                    'phone' => $phoneNumber,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('UAZAPI auth code requested', [
                'external_id' => $externalInstanceId,
                'phone' => $phoneNumber,
            ]);
            
            return [
                'success' => true,
                'code' => $data['code'] ?? null,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Logger::error('UAZAPI request auth code failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get QR code as PNG image (base64)
     */
    public function getQRCodeImage(string $externalInstanceId): ?string
    {
        try {
            // UAZAPI retorna QR como string, converter para imagem usando biblioteca
            $qrCode = $this->getQRCode($externalInstanceId);
            
            if (!$qrCode) {
                return null;
            }
            
            // TODO: Converter string QR para imagem PNG
            // Por enquanto retorna null (não suportado pela UAZAPI diretamente)
            return null;
        } catch (\Exception $e) {
            Logger::error('UAZAPI get QR code image failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    private function formatPhone(string $phone): string
    {
        // Remove caracteres não numéricos
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

