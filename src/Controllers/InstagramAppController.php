<?php

namespace App\Controllers;

use App\Models\InstagramApp;
use App\Utils\Response;
use App\Utils\Logger;

class InstagramAppController
{
    /**
     * Criar Instagram App
     * POST /api/instagram/app
     */
    public static function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados obrigatórios
            if (empty($data['app_id'])) {
                return Response::error('App ID é obrigatório', 400);
            }
            
            if (empty($data['app_secret'])) {
                return Response::error('App Secret é obrigatório', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Verificar se já existe app para esta company
            $existingApp = InstagramApp::getByCompanyId($company['id']);
            if ($existingApp) {
                return Response::error('Instagram App já configurado para esta empresa', 409);
            }
            
            // Criar Instagram App
            $appId = InstagramApp::create([
                'company_id' => $company['id'],
                'app_id' => $data['app_id'],
                'app_secret' => $data['app_secret'],
                'status' => 'pending'
            ]);
            
            if (!$appId) {
                return Response::error('Erro ao criar Instagram App', 500);
            }
            
            // Buscar dados do app criado
            $app = InstagramApp::getById($appId);
            
            Logger::info('Instagram App created', [
                'app_id' => $appId,
                'company_id' => $company['id'],
                'instagram_app_id' => $data['app_id']
            ]);
            
            return Response::success([
                'id' => $app['id'],
                'app_id' => $app['app_id'],
                'status' => $app['status'],
                'created_at' => $app['created_at']
            ], 'Instagram App criado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Instagram App creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }

    /**
     * Obter Instagram App
     * GET /api/instagram/app
     */
    public static function get()
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
            
            // Retornar dados sem expor app_secret
            return Response::success([
                'id' => $app['id'],
                'app_id' => $app['app_id'],
                'status' => $app['status'],
                'has_access_token' => !empty($app['access_token']),
                'token_expires_at' => $app['token_expires_at'],
                'created_at' => $app['created_at'],
                'updated_at' => $app['updated_at']
            ]);

        } catch (\Exception $e) {
            Logger::error('Instagram App get failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }

    /**
     * Atualizar Instagram App
     * PUT /api/instagram/app
     */
    public static function update()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Buscar Instagram App existente
            $app = InstagramApp::getByCompanyId($company['id']);
            if (!$app) {
                return Response::notFound('Instagram App não configurado');
            }
            
            // Atualizar credenciais se fornecidas
            if (!empty($data['app_id']) || !empty($data['app_secret'])) {
                $appId = $data['app_id'] ?? $app['app_id'];
                $appSecret = $data['app_secret'] ?? $app['app_secret'];
                
                $success = InstagramApp::updateCredentials($app['id'], $appId, $appSecret);
                
                if (!$success) {
                    return Response::error('Erro ao atualizar credenciais', 500);
                }
            }
            
            // Atualizar status se fornecido
            if (!empty($data['status'])) {
                InstagramApp::updateStatus($app['id'], $data['status']);
            }
            
            // Buscar dados atualizados
            $updatedApp = InstagramApp::getById($app['id']);
            
            Logger::info('Instagram App updated', [
                'app_id' => $app['id'],
                'company_id' => $company['id']
            ]);
            
            return Response::success([
                'id' => $updatedApp['id'],
                'app_id' => $updatedApp['app_id'],
                'status' => $updatedApp['status'],
                'has_access_token' => !empty($updatedApp['access_token']),
                'token_expires_at' => $updatedApp['token_expires_at'],
                'updated_at' => $updatedApp['updated_at']
            ], 'Instagram App atualizado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Instagram App update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }

    /**
     * Deletar Instagram App
     * DELETE /api/instagram/app
     */
    public static function delete()
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
            
            // Deletar Instagram App
            $success = InstagramApp::delete($app['id']);
            
            if (!$success) {
                return Response::error('Erro ao deletar Instagram App', 500);
            }
            
            Logger::info('Instagram App deleted', [
                'app_id' => $app['id'],
                'company_id' => $company['id']
            ]);
            
            return Response::success([], 'Instagram App deletado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Instagram App delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }

    /**
     * Obter URL de autenticação OAuth
     * GET /api/instagram/auth-url
     */
    public static function getAuthUrl()
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
            
            // Gerar URL de autenticação
            $redirectUri = $_ENV['INSTAGRAM_REDIRECT_URI'] ?? 'https://gapi.sockets.com.br/api/instagram/callback';
            
            $scopes = [
                'instagram_business_basic',
                'instagram_business_manage_messages',
                'instagram_business_manage_comments',
                'instagram_business_content_publish'
            ];
            
            $authUrl = "https://www.instagram.com/oauth/authorize?" . http_build_query([
                'client_id' => $app['app_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => implode(',', $scopes),
                'state' => base64_encode(json_encode([
                    'company_id' => $company['id'],
                    'app_id' => $app['id']
                ]))
            ]);
            
            Logger::info('Instagram auth URL generated', [
                'company_id' => $company['id'],
                'app_id' => $app['id']
            ]);
            
            return Response::success([
                'auth_url' => $authUrl,
                'redirect_uri' => $redirectUri,
                'scopes' => $scopes
            ]);

        } catch (\Exception $e) {
            Logger::error('Instagram auth URL generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::serverError('Erro interno do servidor');
        }
    }
}
