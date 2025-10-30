<?php

namespace App\Controllers;

use App\Models\FacebookApp;
use App\Utils\Response;
use App\Utils\Logger;

class FacebookAppController
{
    public static function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['app_id'])) {
                return Response::error('App ID é obrigatório', 400);
            }
            if (empty($data['app_secret'])) {
                return Response::error('App Secret é obrigatório', 400);
            }

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }

            $existingApp = FacebookApp::getByCompanyId($company['id']);
            if ($existingApp) {
                return Response::error('Facebook App já configurado para esta empresa', 409);
            }

            $newId = FacebookApp::create([
                'company_id' => $company['id'],
                'app_id' => $data['app_id'],
                'app_secret' => $data['app_secret'],
                'page_id' => $data['page_id'] ?? null,
                'page_access_token' => $data['page_access_token'] ?? null,
                'webhook_verify_token' => $data['webhook_verify_token'] ?? null,
                'status' => 'pending'
            ]);

            if (!$newId) {
                return Response::error('Erro ao criar Facebook App', 500);
            }

            $app = FacebookApp::getById($newId);

            Logger::info('Facebook App created', [
                'app_id' => $newId,
                'company_id' => $company['id']
            ]);

            return Response::success([
                'id' => $app['id'],
                'app_id' => $app['app_id'],
                'page_id' => $app['page_id'],
                'status' => $app['status'],
                'created_at' => $app['created_at']
            ], 'Facebook App criado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Facebook App creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::serverError('Erro interno do servidor');
        }
    }

    public static function get()
    {
        try {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }

            $app = FacebookApp::getByCompanyId($company['id']);
            if (!$app) {
                return Response::notFound('Facebook App não configurado');
            }

            return Response::success([
                'id' => $app['id'],
                'app_id' => $app['app_id'],
                'page_id' => $app['page_id'],
                'status' => $app['status'],
                'has_page_token' => !empty($app['page_access_token']),
                'created_at' => $app['created_at'],
                'updated_at' => $app['updated_at']
            ]);

        } catch (\Exception $e) {
            Logger::error('Facebook App get failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::serverError('Erro interno do servidor');
        }
    }

    public static function update()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }

            $app = FacebookApp::getByCompanyId($company['id']);
            if (!$app) {
                return Response::notFound('Facebook App não configurado');
            }

            if (!empty($data['app_id']) || !empty($data['app_secret'])) {
                $appId = $data['app_id'] ?? $app['app_id'];
                $appSecret = $data['app_secret'] ?? $app['app_secret'];
                if (!FacebookApp::updateCredentials($app['id'], $appId, $appSecret)) {
                    return Response::error('Erro ao atualizar credenciais', 500);
                }
            }

            if (array_key_exists('page_id', $data) || array_key_exists('page_access_token', $data)) {
                $pageId = $data['page_id'] ?? $app['page_id'] ?? null;
                $pageAccessToken = $data['page_access_token'] ?? null;
                if (!FacebookApp::updatePageAccess($app['id'], $pageId, $pageAccessToken)) {
                    return Response::error('Erro ao atualizar Page Access', 500);
                }
            }

            if (!empty($data['webhook_verify_token'])) {
                FacebookApp::updateVerifyToken($app['id'], $data['webhook_verify_token']);
            }

            if (!empty($data['status'])) {
                FacebookApp::updateStatus($app['id'], $data['status']);
            }

            $updated = FacebookApp::getById($app['id']);
            Logger::info('Facebook App updated', [
                'app_id' => $app['id'],
                'company_id' => $company['id']
            ]);

            return Response::success([
                'id' => $updated['id'],
                'app_id' => $updated['app_id'],
                'page_id' => $updated['page_id'],
                'status' => $updated['status'],
                'has_page_token' => !empty($updated['page_access_token']),
                'updated_at' => $updated['updated_at']
            ], 'Facebook App atualizado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Facebook App update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::serverError('Erro interno do servidor');
        }
    }

    public static function delete()
    {
        try {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }

            $app = FacebookApp::getByCompanyId($company['id']);
            if (!$app) {
                return Response::notFound('Facebook App não configurado');
            }

            if (!FacebookApp::delete($app['id'])) {
                return Response::error('Erro ao deletar Facebook App', 500);
            }

            Logger::info('Facebook App deleted', [
                'app_id' => $app['id'],
                'company_id' => $company['id']
            ]);

            return Response::success([], 'Facebook App deletado com sucesso');

        } catch (\Exception $e) {
            Logger::error('Facebook App delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::serverError('Erro interno do servidor');
        }
    }
}


