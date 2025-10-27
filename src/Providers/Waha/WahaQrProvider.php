<?php

namespace App\Providers\Waha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class WahaQrProvider
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
     * Obter QR Code da instância
     * Padrão UAZAPI: GET /instance/qrcode
     */
    public function getQRCode(string $externalInstanceId): array
    {
        try {
            // Primeiro verificar status da sessão
            $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
            $statusData = json_decode($statusResponse->getBody()->getContents(), true);
            $status = $statusData['status'] ?? 'UNKNOWN';
            
            Logger::info('WAHA session status checked', [
                'external_id' => $externalInstanceId,
                'status' => $status
            ]);
            
            // Se status for FAILED, tentar fazer restart
            if ($status === 'FAILED') {
                Logger::info('Session is FAILED, attempting restart', [
                    'external_id' => $externalInstanceId
                ]);
                
                $restartResponse = $this->client->post("/api/sessions/{$externalInstanceId}/restart");
                $restartData = json_decode($restartResponse->getBody()->getContents(), true);
                
                if ($restartData['status'] === 'STARTING' || $restartData['status'] === 'SCAN_QR_CODE') {
                    Logger::info('Session restarted successfully', [
                        'external_id' => $externalInstanceId,
                        'new_status' => $restartData['status']
                    ]);
                    
                    // Aguardar um pouco para a sessão estabilizar
                    sleep(2);
                    
                    // Verificar status novamente
                    $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['status'] ?? 'UNKNOWN';
                }
            }
            
            // Se status for STOPPED, tentar iniciar
            if ($status === 'STOPPED') {
                Logger::info('Session is STOPPED, attempting start', [
                    'external_id' => $externalInstanceId
                ]);
                
                $startResponse = $this->client->post("/api/sessions/{$externalInstanceId}/start");
                $startData = json_decode($startResponse->getBody()->getContents(), true);
                
                if ($startData['status'] === 'STARTING' || $startData['status'] === 'SCAN_QR_CODE') {
                    Logger::info('Session started successfully', [
                        'external_id' => $externalInstanceId,
                        'new_status' => $startData['status']
                    ]);
                    
                    // Aguardar um pouco para a sessão estabilizar
                    sleep(2);
                    
                    // Verificar status novamente
                    $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['status'] ?? 'UNKNOWN';
                }
            }
            
            // Se status ainda for FAILED, retornar erro
            if ($status === 'FAILED') {
                Logger::warning('Cannot get QR code - session is still in FAILED status after restart', [
                    'external_id' => $externalInstanceId,
                    'status' => $status
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Sessão em estado FAILED - não foi possível reiniciar',
                    'qrcode' => null
                ];
            }
            
            // Se status for SCAN_QR_CODE, obter QR code real do WAHA
            if ($status === 'SCAN_QR_CODE') {
                Logger::info('Session is ready for QR code scan', [
                    'external_id' => $externalInstanceId
                ]);
                
                try {
                    $qrResponse = $this->client->get("/api/{$externalInstanceId}/auth/qr");
                    $contentType = $qrResponse->getHeader('Content-Type')[0] ?? '';
                    
                    // Se retornar imagem PNG diretamente, converter para base64
                    if (strpos($contentType, 'image/png') !== false) {
                        $imageData = $qrResponse->getBody()->getContents();
                        $base64 = base64_encode($imageData);
                        $qrcode = 'data:image/png;base64,' . $base64;
                        
                        Logger::info('WAHA QR code image retrieved and converted to base64', [
                            'external_id' => $externalInstanceId,
                            'image_size' => strlen($imageData),
                            'base64_size' => strlen($base64)
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'QR code disponível para escaneamento',
                            'qrcode' => $qrcode,
                            'status' => $status
                        ];
                    } else {
                        // Se retornar JSON, tentar extrair o campo qr
                        $qrData = json_decode($qrResponse->getBody()->getContents(), true);
                        
                        Logger::info('WAHA QR code JSON retrieved', [
                            'external_id' => $externalInstanceId,
                            'content_type' => $contentType,
                            'has_qr' => !empty($qrData['qr'])
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'QR code disponível para escaneamento',
                            'qrcode' => $qrData['qr'] ?? null,
                            'status' => $status
                        ];
                    }
                    
                } catch (GuzzleException $e) {
                    Logger::error('Failed to get QR code from WAHA', [
                        'external_id' => $externalInstanceId,
                        'error' => $e->getMessage(),
                        'status_code' => $e->getCode()
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Erro ao obter QR code do WAHA: ' . $e->getMessage(),
                        'qrcode' => null,
                        'status' => $status,
                        'error_details' => [
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ]
                    ];
                }
            }
            
            // Para outros status, retornar sem QR code
            return [
                'success' => true,
                'message' => 'Sessão não está pronta para QR code',
                'qrcode' => null,
                'status' => $status
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA get QR code failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao obter QR code: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter imagem do QR Code
     * Padrão UAZAPI: GET /instance/qrcode/image
     */
    public function getQRCodeImage(string $externalInstanceId): array
    {
        try {
            // Primeiro verificar status da sessão
            $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
            $statusData = json_decode($statusResponse->getBody()->getContents(), true);
            $status = $statusData['status'] ?? 'UNKNOWN';
            
            Logger::info('WAHA session status checked for image', [
                'external_id' => $externalInstanceId,
                'status' => $status
            ]);
            
            // Se status for FAILED, tentar fazer restart
            if ($status === 'FAILED') {
                Logger::info('Session is FAILED, attempting restart for image', [
                    'external_id' => $externalInstanceId
                ]);
                
                $restartResponse = $this->client->post("/api/sessions/{$externalInstanceId}/restart");
                $restartData = json_decode($restartResponse->getBody()->getContents(), true);
                
                if ($restartData['status'] === 'STARTING' || $restartData['status'] === 'SCAN_QR_CODE') {
                    Logger::info('Session restarted successfully for image', [
                        'external_id' => $externalInstanceId,
                        'new_status' => $restartData['status']
                    ]);
                    
                    // Aguardar um pouco para a sessão estabilizar
                    sleep(2);
                    
                    // Verificar status novamente
                    $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['status'] ?? 'UNKNOWN';
                }
            }
            
            // Se status for STOPPED, tentar iniciar
            if ($status === 'STOPPED') {
                Logger::info('Session is STOPPED, attempting start for image', [
                    'external_id' => $externalInstanceId
                ]);
                
                $startResponse = $this->client->post("/api/sessions/{$externalInstanceId}/start");
                $startData = json_decode($startResponse->getBody()->getContents(), true);
                
                if ($startData['status'] === 'STARTING' || $startData['status'] === 'SCAN_QR_CODE') {
                    Logger::info('Session started successfully for image', [
                        'external_id' => $externalInstanceId,
                        'new_status' => $startData['status']
                    ]);
                    
                    // Aguardar um pouco para a sessão estabilizar
                    sleep(2);
                    
                    // Verificar status novamente
                    $statusResponse = $this->client->get("/api/sessions/{$externalInstanceId}");
                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['status'] ?? 'UNKNOWN';
                }
            }
            
            // Se status ainda for FAILED, retornar erro
            if ($status === 'FAILED') {
                Logger::warning('Cannot get QR code image - session is still in FAILED status after restart', [
                    'external_id' => $externalInstanceId,
                    'status' => $status
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Sessão em estado FAILED - não foi possível reiniciar',
                    'qrcode' => null
                ];
            }
            
            // Se status for SCAN_QR_CODE, obter QR code real do WAHA
            if ($status === 'SCAN_QR_CODE') {
                Logger::info('Session is ready for QR code image', [
                    'external_id' => $externalInstanceId
                ]);
                
                $qrResponse = $this->client->get("/api/{$externalInstanceId}/auth/qr");
                $contentType = $qrResponse->getHeader('Content-Type')[0] ?? '';
                
                // Se retornar imagem PNG diretamente, converter para base64
                if (strpos($contentType, 'image/png') !== false) {
                    $imageData = $qrResponse->getBody()->getContents();
                    $base64 = base64_encode($imageData);
                    $qrcode = 'data:image/png;base64,' . $base64;
                    
                    Logger::info('WAHA QR code image retrieved and converted to base64', [
                        'external_id' => $externalInstanceId,
                        'image_size' => strlen($imageData),
                        'base64_size' => strlen($base64)
                    ]);
                    
                    return [
                        'success' => true,
                        'qrcode' => $qrcode
                    ];
                } else {
                    // Se retornar JSON, tentar extrair o campo qr
                    $qrData = json_decode($qrResponse->getBody()->getContents(), true);
                    
                    Logger::info('WAHA QR code JSON retrieved for image', [
                        'external_id' => $externalInstanceId,
                        'content_type' => $contentType,
                        'has_qr' => !empty($qrData['qr'])
                    ]);
                    
                    return [
                        'success' => true,
                        'qrcode' => $qrData['qr'] ?? null
                    ];
                }
            }
            
            // Para outros status, retornar sem QR code
            return [
                'success' => true,
                'message' => 'Sessão não está pronta para QR code',
                'qrcode' => null,
                'status' => $status
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA get QR code image failed', [
                'external_id' => $externalInstanceId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao obter imagem do QR code: ' . $e->getMessage()
            ];
        }
    }
}
