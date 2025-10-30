<?php

namespace App\Controllers;

use App\Models\InstagramApp;
use App\Models\Instance;
use App\Providers\Instagram\InstagramAuthProvider;
use App\Utils\Response;
use App\Utils\Logger;

class InstagramAuthController
{
    /**
     * Callback OAuth do Instagram
     * GET /api/instagram/callback
     */
    public static function callback()
    {
        try {
            $code = $_GET['code'] ?? null;
            $state = $_GET['state'] ?? null;
            $error = $_GET['error'] ?? null;
            
            // Verificar se houve erro na autorização
            if ($error) {
                $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
                Logger::warning('Instagram OAuth error', [
                    'error' => $error,
                    'error_description' => $errorDescription
                ]);
                
                return self::renderCallbackPage(false, "Erro na autorização: {$errorDescription}");
            }
            
            // Verificar se tem código
            if (!$code) {
                Logger::warning('Instagram OAuth callback without code');
                return self::renderCallbackPage(false, 'Código de autorização não fornecido');
            }
            
            // Decodificar state para obter company_id e app_id
            $stateData = null;
            if ($state) {
                $decodedState = base64_decode($state);
                $stateData = json_decode($decodedState, true);
            }
            
            if (!$stateData || !isset($stateData['company_id']) || !isset($stateData['app_id'])) {
                Logger::error('Instagram OAuth invalid state', ['state' => $state]);
                return self::renderCallbackPage(false, 'Estado de autorização inválido');
            }
            
            $companyId = $stateData['company_id'];
            $appId = $stateData['app_id'];
            
            // Buscar Instagram App
            $app = InstagramApp::getById($appId);
            if (!$app || $app['company_id'] != $companyId) {
                Logger::error('Instagram App not found or invalid', [
                    'app_id' => $appId,
                    'company_id' => $companyId
                ]);
                return self::renderCallbackPage(false, 'Instagram App não encontrado');
            }
            
            // Trocar código por token
            $authProvider = new InstagramAuthProvider();
            $redirectUri = $_ENV['INSTAGRAM_REDIRECT_URI'] ?? 'https://gapi.sockets.com.br/api/instagram/callback';
            
            $tokenResult = $authProvider->exchangeCodeForToken($code, $app['app_id'], $app['app_secret'], $redirectUri);
            
            if (!$tokenResult['success']) {
                Logger::error('Instagram token exchange failed', [
                    'error' => $tokenResult['message'],
                    'app_id' => $appId
                ]);
                return self::renderCallbackPage(false, 'Erro ao obter token: ' . $tokenResult['message']);
            }
            
            // Obter long-lived token
            $longLivedResult = $authProvider->getLongLivedToken($tokenResult['access_token'], $app['app_secret']);
            
            if (!$longLivedResult['success']) {
                Logger::error('Instagram long-lived token failed', [
                    'error' => $longLivedResult['message'],
                    'app_id' => $appId
                ]);
                return self::renderCallbackPage(false, 'Erro ao obter token de longa duração: ' . $longLivedResult['message']);
            }
            
            // Atualizar token no banco
            $expiresIn = $longLivedResult['expires_in'] ?? 5184000; // 60 dias em segundos
            InstagramApp::updateToken($appId, $longLivedResult['access_token'], $expiresIn);
            
            // Obter informações do usuário
            $userInfo = $authProvider->getUserInfo();
            $instagramUserId = $userInfo['user_id'] ?? null;
            $instagramUsername = $userInfo['username'] ?? null;
            
            // Criar ou atualizar instância Instagram
            $instance = self::createOrUpdateInstagramInstance($companyId, $instagramUserId, $instagramUsername);
            
            Logger::info('Instagram OAuth completed successfully', [
                'company_id' => $companyId,
                'app_id' => $appId,
                'instagram_user_id' => $instagramUserId,
                'instagram_username' => $instagramUsername,
                'instance_id' => $instance['id'] ?? null
            ]);
            
            return self::renderCallbackPage(true, 'Instagram conectado com sucesso!', [
                'instagram_user_id' => $instagramUserId,
                'instagram_username' => $instagramUsername,
                'instance_id' => $instance['id'] ?? null
            ]);

        } catch (\Exception $e) {
            Logger::error('Instagram OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::renderCallbackPage(false, 'Erro interno: ' . $e->getMessage());
        }
    }

    /**
     * Verificar status de autenticação
     * GET /api/instagram/auth-status
     */
    public static function getStatus()
    {
        try {
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Buscar Instagram App
            $app = InstagramApp::getByCompanyId($company['id']);
            if (!$app) {
                return Response::notFound('Instagram App não configurado');
            }
            
            // Verificar se tem token
            if (!$app['access_token']) {
                return Response::success([
                    'authenticated' => false,
                    'status' => 'not_connected',
                    'message' => 'Instagram não conectado'
                ]);
            }
            
            // Verificar se token está expirado
            if (InstagramApp::isTokenExpired($app)) {
                // Tentar renovar token
                $authProvider = new InstagramAuthProvider();
                $refreshResult = $authProvider->refreshToken($app['access_token'], $app['app_secret']);
                
                if ($refreshResult['success']) {
                    // Atualizar token no banco
                    $expiresIn = $refreshResult['expires_in'] ?? 5184000;
                    InstagramApp::updateToken($app['id'], $refreshResult['access_token'], $expiresIn);
                    
                    $app['access_token'] = $refreshResult['access_token'];
                    $app['token_expires_at'] = date('Y-m-d H:i:s', time() + $expiresIn);
                } else {
                    // Token não pode ser renovado
                    InstagramApp::updateStatus($app['id'], 'expired');
                    
                    return Response::success([
                        'authenticated' => false,
                        'status' => 'expired',
                        'message' => 'Token expirado e não pode ser renovado'
                    ]);
                }
            }
            
            // Validar token
            $authProvider = new InstagramAuthProvider();
            $validation = $authProvider->validateToken($app['access_token']);
            
            if ($validation['success'] && $validation['valid']) {
                return Response::success([
                    'authenticated' => true,
                    'status' => 'connected',
                    'user_id' => $validation['user_id'],
                    'username' => $validation['username'],
                    'account_type' => $validation['account_type'] ?? null,
                    'token_expires_at' => $app['token_expires_at']
                ]);
            } else {
                InstagramApp::updateStatus($app['id'], 'error');
                
                return Response::success([
                    'authenticated' => false,
                    'status' => 'error',
                    'message' => 'Token inválido'
                ]);
            }

        } catch (\Exception $e) {
            Logger::error('Instagram auth status check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }

    /**
     * Criar ou atualizar instância Instagram
     */
    private static function createOrUpdateInstagramInstance(int $companyId, string $instagramUserId, string $instagramUsername): ?array
    {
        try {
            // Buscar instância existente por instagram_user_id
            $existingInstance = Instance::getByInstagramUserId($instagramUserId);
            
            if ($existingInstance) {
                // Atualizar instância existente
                $success = Instance::updateInstagramData($existingInstance['id'], $instagramUserId, $instagramUsername);
                
                if ($success) {
                    return Instance::getById($existingInstance['id']);
                }
            } else {
                // Criar nova instância
                // Primeiro, buscar provider Instagram
                $provider = \App\Models\Provider::getByType('instagram');
                if (!$provider) {
                    Logger::error('Instagram provider not found');
                    return null;
                }
                
                $instanceId = Instance::create([
                    'company_id' => $companyId,
                    'provider_id' => $provider['id'],
                    'instance_name' => 'instagram-' . $instagramUsername,
                    'instagram_user_id' => $instagramUserId,
                    'instagram_username' => $instagramUsername,
                    'status' => 'connected'
                ]);
                
                if ($instanceId) {
                    return Instance::getById($instanceId);
                }
            }
            
            return null;

        } catch (\Exception $e) {
            Logger::error('Failed to create/update Instagram instance', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'instagram_user_id' => $instagramUserId
            ]);
            return null;
        }
    }

    /**
     * Renderizar página de callback
     */
    private static function renderCallbackPage(bool $success, string $message, array $data = []): void
    {
        $title = $success ? 'Instagram Conectado' : 'Erro na Conexão';
        $color = $success ? '#25D366' : '#FF0000';
        
        echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }
        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .data {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-family: monospace;
            font-size: 14px;
            text-align: left;
        }
        .close-btn {
            background: {$color};
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .close-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='icon'>" . ($success ? '✅' : '❌') . "</div>
        <div class='title'>{$title}</div>
        <div class='message'>{$message}</div>";
        
        if (!empty($data)) {
            echo "<div class='data'>" . json_encode($data, JSON_PRETTY_PRINT) . "</div>";
        }
        
        echo "<button class='close-btn' onclick='window.close()'>Fechar</button>
    </div>
    
    <script>
        // Fechar automaticamente após 3 segundos se for sucesso
        if (" . ($success ? 'true' : 'false') . ") {
            setTimeout(() => {
                window.close();
            }, 3000);
        }
        
        // Notificar parent window se estiver em iframe
        if (window.parent !== window) {
            window.parent.postMessage({
                type: 'instagram_auth_result',
                success: " . ($success ? 'true' : 'false') . ",
                message: '{$message}',
                data: " . json_encode($data) . "
            }, '*');
        }
    </script>
</body>
</html>";
    }
}
