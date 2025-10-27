<?php

namespace App\Providers\Waha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class WahaInstanceProvider
{
    /** @var \GuzzleHttp\Client */
    private $client;
    private string $baseUrl;
    private ?string $apiKey;
    private string $webhookUrl;
    
    public function __construct(\GuzzleHttp\Client $client, string $baseUrl, ?string $apiKey, string $webhookUrl)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->webhookUrl = $webhookUrl;
    }
    
    /**
     * Conectar instância (iniciar sessão)
     * Padrão UAZAPI: POST /instance/connect
     */
    public function connect(string $externalInstanceId, ?string $phone = null): array
    {
        try {
            // Iniciar sessão
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/start", [
                'json' => [
                    'webhook' => $this->webhookUrl,
                    'webhookByEvents' => false,
                    'events' => ['message', 'status', 'session.status']
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA session started', [
                'external_id' => $externalInstanceId,
                'status' => $data['status'] ?? 'unknown'
            ]);
            
            // Se passou telefone e a sessão não está em SCAN_QR_CODE, solicitar código de pareamento
            if ($phone && $data['status'] !== 'SCAN_QR_CODE') {
                $authResult = $this->requestAuthCode($externalInstanceId, $phone);
                if (!$authResult['success']) {
                    return $authResult;
                }
            }
            
            // Se a sessão está em SCAN_QR_CODE, obter QR code real do WAHA
            $qrcode = null;
            if ($data['status'] === 'SCAN_QR_CODE') {
                try {
                    $qrResponse = $this->client->get("/api/{$externalInstanceId}/auth/qr");
                    $contentType = $qrResponse->getHeader('Content-Type')[0] ?? '';
                    
                    // Se retornar imagem PNG diretamente, converter para base64
                    if (strpos($contentType, 'image/png') !== false) {
                        $imageData = $qrResponse->getBody()->getContents();
                        $base64 = base64_encode($imageData);
                        $qrcode = 'data:image/png;base64,' . $base64;
                        
                        Logger::info('WAHA QR code image retrieved for connect', [
                            'external_id' => $externalInstanceId,
                            'image_size' => strlen($imageData),
                            'base64_size' => strlen($base64)
                        ]);
                    } else {
                        // Se retornar JSON, tentar extrair o campo qr
                        $qrData = json_decode($qrResponse->getBody()->getContents(), true);
                        $qrcode = $qrData['qr'] ?? null;
                        
                        Logger::info('WAHA QR code JSON retrieved for connect', [
                            'external_id' => $externalInstanceId,
                            'content_type' => $contentType,
                            'has_qr' => !empty($qrData['qr'])
                        ]);
                    }
                    
                } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                    Logger::error('Failed to get QR code during connect', [
                        'external_id' => $externalInstanceId,
                        'error' => $e->getMessage(),
                        'status_code' => $e->getCode()
                    ]);
                    
                    // Se falhar, retornar null (sem mock)
                    $qrcode = null;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Sessão iniciada com sucesso',
                'connected' => $data['status'] === 'WORKING',
                'loggedIn' => $data['status'] === 'WORKING',
                'jid' => $data['me']['jid'] ?? null,
                'qrcode' => $qrcode,
                'status' => $data['status'],
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA connect failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao conectar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Desconectar instância
     * Padrão UAZAPI: POST /instance/disconnect
     */
    public function disconnect(string $externalInstanceId): array
    {
        try {
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/stop", [
                'json' => ['logout' => true]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA session stopped', [
                'external_id' => $externalInstanceId,
                'status' => $data['status'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'message' => 'Sessão desconectada com sucesso',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA disconnect failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao desconectar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter status da instância
     * Padrão UAZAPI: GET /instance/status
     */
    public function getStatus(string $externalInstanceId): array
    {
        try {
            $response = $this->client->get("/api/sessions/{$externalInstanceId}/status");
            $data = json_decode($response->getBody()->getContents(), true);
            
            $status = $data['status'] ?? 'UNKNOWN';
            $mappedStatus = $this->mapWahaStatusToUazapi($status);
            
            // Se status for FAILED, retornar warning em vez de erro
            if ($status === 'FAILED') {
                Logger::warning('WAHA session is in FAILED status', [
                    'external_id' => $externalInstanceId,
                    'status' => $status
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Sessão em estado FAILED - precisa ser reiniciada',
                    'connected' => false,
                    'loggedIn' => false,
                    'jid' => null,
                    'status' => $mappedStatus,
                    'needs_restart' => true,
                    'warnings' => ['Session status is FAILED - restart required']
                ];
            }
            
            Logger::info('WAHA status retrieved', [
                'external_id' => $externalInstanceId,
                'status' => $status,
                'mapped_status' => $mappedStatus
            ]);
            
            return [
                'success' => true,
                'connected' => $mappedStatus === 'connected',
                'loggedIn' => $mappedStatus === 'connected',
                'jid' => $data['jid'] ?? null,
                'status' => $mappedStatus,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA get status failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao obter status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar nome da instância
     * Padrão UAZAPI: PUT /instance/name
     */
    public function updateName(string $externalInstanceId, string $name): array
    {
        try {
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/profile/name", [
                'json' => ['name' => $name]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA profile name updated', [
                'external_id' => $externalInstanceId,
                'name' => $name
            ]);
            
            return [
                'success' => true,
                'message' => 'Nome da instância atualizado com sucesso',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA update name failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar nome: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Deletar instância
     * Padrão UAZAPI: DELETE /instance
     */
    public function delete(string $externalInstanceId): array
    {
        try {
            // Primeiro parar a sessão
            $this->client->post("/api/sessions/{$externalInstanceId}/stop", [
                'json' => ['logout' => true]
            ]);
            
            // Depois deletar
            $response = $this->client->delete("/api/sessions/{$externalInstanceId}");
            
            Logger::info('WAHA instance deleted', [
                'external_id' => $externalInstanceId
            ]);
            
            return [
                'success' => true,
                'message' => 'Instância deletada com sucesso'
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA delete instance failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao deletar instância: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reiniciar sessão (para casos de FAILED)
     */
    public function restartSession(string $externalInstanceId): array
    {
        try {
            // Primeiro parar
            $this->client->post("/api/sessions/{$externalInstanceId}/stop");
            
            // Aguardar um pouco
            sleep(2);
            
            // Depois iniciar novamente
            $response = $this->client->post("/api/sessions/{$externalInstanceId}/start", [
                'json' => [
                    'webhook' => $this->webhookUrl,
                    'webhookByEvents' => false,
                    'events' => ['message', 'status', 'session.status']
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA session restarted', [
                'external_id' => $externalInstanceId,
                'status' => $data['status'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'message' => 'Sessão reiniciada com sucesso',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA restart session failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao reiniciar sessão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Solicitar código de autenticação
     */
    private function requestAuthCode(string $externalInstanceId, string $phone): array
    {
        try {
            $response = $this->client->get("/api/sessions/{$externalInstanceId}/auth/request-code");
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA auth code requested', [
                'external_id' => $externalInstanceId,
                'phone' => $phone
            ]);
            
            return [
                'success' => true,
                'message' => 'Código de autenticação solicitado',
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA request auth code failed', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao solicitar código de autenticação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mapear status WAHA para UAZAPI
     */
    private function mapWahaStatusToUazapi(string $wahaStatus): string
    {
        return match ($wahaStatus) {
            'WORKING' => 'connected',
            'STARTING' => 'connecting',
            'SCAN_QR_CODE' => 'connecting',
            'STOPPED' => 'disconnected',
            'FAILED' => 'disconnected',
            default => 'disconnected',
        };
    }
}
