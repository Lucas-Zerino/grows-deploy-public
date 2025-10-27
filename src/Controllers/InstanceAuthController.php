<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Middleware\AuthMiddleware;
use App\Services\ProviderManager;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;

/**
 * Controller para autenticação de instâncias
 * Suporta múltiplos métodos: QR Code, Código por telefone, etc
 */
class InstanceAuthController
{
    /**
     * Helper: Garante que autenticou com token de instância
     */
    private static function getAuthenticatedInstance(): ?array
    {
        $auth = AuthMiddleware::authenticate();
        if (!$auth) return null;
        
        if ($auth['type'] !== 'instance') {
            Response::json([
                'error' => 'Invalid token type',
                'message' => 'This endpoint requires an instance token.'
            ], 401);
            return null;
        }
        
        return $auth;
    }
    
    /**
     * POST /instance/authenticate
     * Autentica instância usando o método escolhido
     */
    public static function authenticate(): void
    {
        $instance = self::getAuthenticatedInstance();
        if (!$instance) return;
        
        $input = Router::getJsonInput();
        
        // Validar método de autenticação
        $method = $input['method'] ?? 'qrcode';
        
        if (!in_array($method, ['qrcode', 'phone_code'])) {
            Response::json([
                'error' => 'Invalid authentication method',
                'message' => 'Method must be "qrcode" or "phone_code"'
            ], 400);
            return;
        }
        
        try {
            $providerClient = ProviderManager::getProvider($instance['provider_id']);
            
            if ($method === 'qrcode') {
                // Método: QR Code
                $result = self::authenticateWithQRCode($instance, $providerClient);
            } else {
                // Método: Código por telefone
                $phoneNumber = $input['phone_number'] ?? null;
                
                if (empty($phoneNumber)) {
                    Response::json([
                        'error' => 'Validation error',
                        'message' => 'phone_number is required for phone_code method'
                    ], 400);
                    return;
                }
                
                $result = self::authenticateWithPhoneCode($instance, $providerClient, $phoneNumber);
            }
            
            if (!$result['success']) {
                Response::json([
                    'error' => $result['error'] ?? 'Authentication failed',
                    'message' => $result['error'] ?? 'Failed to authenticate instance'
                ], $result['status_code'] ?? 400);
                return;
            }
            
            // Atualizar status da instância
            Instance::updateStatus($instance['id'], 'connecting');
            
            Logger::info('Instance authentication initiated', [
                'company_id' => $instance['company_id'],
                'instance_id' => $instance['id'],
                'method' => $method,
            ]);
            
            Response::json($result['data'], 200);
            
        } catch (\Exception $e) {
            Logger::error('Failed to authenticate instance', [
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /instance/authenticate/qrcode
     * Obter QR code (imagem ou raw)
     */
    public static function getQRCode(): void
    {
        $instance = self::getAuthenticatedInstance();
        if (!$instance) return;
        
        $format = $_GET['format'] ?? 'raw';
        
        try {
            $providerClient = ProviderManager::getProvider($instance['provider_id']);
            
            if ($format === 'image') {
                // Retornar QR code como imagem PNG
                $qrImage = $providerClient->getQRCodeImage($instance['external_instance_id']);
                
                if (!$qrImage) {
                    Response::error('QR code image not available', 404);
                    return;
                }
                
                // Retornar imagem PNG
                header('Content-Type: image/png');
                echo base64_decode($qrImage);
                exit;
            } else {
                // Retornar QR code raw (string)
                $qrCode = $providerClient->getQRCode($instance['external_instance_id']);
                
                if (!$qrCode) {
                    Response::error('QR code not available', 404);
                    return;
                }
                
                Response::json(['qrcode' => $qrCode], 200);
            }
            
        } catch (\Exception $e) {
            Logger::error('Failed to get QR code', [
                'error' => $e->getMessage(),
            ]);
            
            Response::error('Failed to get QR code', 500);
        }
    }
    
    /**
     * Autenticar com QR Code
     */
    private static function authenticateWithQRCode(array $instance, $providerClient): array
    {
        // Iniciar sessão para gerar QR code
        $result = $providerClient->connect($instance['external_instance_id']);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Aguardar QR code estar disponível (até 10 segundos)
        $maxAttempts = 10;
        $attempt = 0;
        $qrCode = null;
        
        while ($attempt < $maxAttempts && !$qrCode) {
            sleep(1);
            $qrCode = $providerClient->getQRCode($instance['external_instance_id']);
            $attempt++;
        }
        
        return [
            'success' => true,
            'data' => [
                'method' => 'qrcode',
                'status' => 'connecting',
                'qrcode' => $qrCode,
                'message' => $qrCode 
                    ? 'QR code generated. Scan it with your WhatsApp.' 
                    : 'QR code is being generated. Please check status.',
            ],
        ];
    }
    
    /**
     * POST /instance/restart
     * Reinicia uma sessão que está em status FAILED
     */
    public static function restart(): void
    {
        $instance = self::getAuthenticatedInstance();
        if (!$instance) return;
        
        try {
            $providerClient = ProviderManager::getProvider($instance['provider_id']);
            
            // Verificar se o provider tem método restartSession
            if (method_exists($providerClient, 'restartSession')) {
                $result = $providerClient->restartSession($instance['external_instance_id']);
            } else {
                // Fallback: usar connect para reiniciar
                $result = $providerClient->connect($instance['external_instance_id']);
            }
            
            if (!$result['success']) {
                Response::json([
                    'error' => $result['error'] ?? 'Restart failed',
                    'message' => $result['error'] ?? 'Failed to restart instance'
                ], $result['status_code'] ?? 400);
                return;
            }
            
            // Atualizar status da instância
            Instance::updateStatus($instance['id'], 'connecting');
            
            Logger::info('Instance restarted', [
                'company_id' => $instance['company_id'],
                'instance_id' => $instance['id'],
            ]);
            
            Response::json([
                'success' => true,
                'message' => 'Instance restarted successfully',
                'status' => 'connecting',
                'data' => $result['data'] ?? null,
            ], 200);
            
        } catch (\Exception $e) {
            Logger::error('Failed to restart instance', [
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'error' => 'Internal error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Autenticar com código por telefone
     */
    private static function authenticateWithPhoneCode(array $instance, $providerClient, string $phoneNumber): array
    {
        $result = $providerClient->requestAuthCode($instance['external_instance_id'], $phoneNumber);
        
        if (!$result['success']) {
            return $result;
        }
        
        return [
            'success' => true,
            'data' => [
                'method' => 'phone_code',
                'status' => 'connecting',
                'code' => $result['code'] ?? null,
                'message' => 'Authentication code sent. Enter it in WhatsApp.',
            ],
        ];
    }
}

