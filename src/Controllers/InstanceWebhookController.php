<?php

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;
use App\Models\Instance;
use App\Models\InstanceWebhook;

class InstanceWebhookController
{
    /**
     * Listar webhooks de uma instância
     */
    public static function list($instanceId)
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
            
            // Buscar instância
            $instance = Instance::getById($instanceId);
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Buscar webhooks da instância
            $webhooks = InstanceWebhook::getByInstanceId($instanceId);
            
            return Response::json([
                'success' => true,
                'data' => $webhooks,
                'total' => count($webhooks)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to list instance webhooks', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Criar webhook para instância
     */
    public static function create($instanceId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados obrigatórios
            if (empty($data['url'])) {
                return Response::error('URL do webhook é obrigatória', 400);
            }
            
            if (empty($data['events']) || !is_array($data['events'])) {
                return Response::error('Eventos do webhook são obrigatórios', 400);
            }
            
            // Extrair token do header Authorization
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Buscar company pelo token
            $company = \App\Models\Company::getByToken($token);
            if (!$company) {
                return Response::unauthorized('Token de empresa inválido');
            }
            
            // Buscar instância
            $instance = Instance::getById($instanceId);
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Criar webhook
            $webhookId = InstanceWebhook::create([
                'instance_id' => $instanceId,
                'webhook_url' => $data['url'],
                'events' => $data['events'],
                'is_active' => $data['is_active'] ?? true
            ]);
            
            if (!$webhookId) {
                return Response::error('Erro ao criar webhook', 500);
            }
            
            // Buscar webhook criado
            $webhook = InstanceWebhook::getById($webhookId);
            
            Logger::info('Instance webhook created', [
                'webhook_id' => $webhookId,
                'instance_id' => $instanceId,
                'webhook_url' => $data['url']
            ]);
            
            return Response::json([
                'success' => true,
                'message' => 'Webhook criado com sucesso',
                'data' => $webhook
            ], 201);
            
        } catch (\Exception $e) {
            Logger::error('Failed to create instance webhook', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Atualizar webhook
     */
    public static function update($instanceId, $webhookId)
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
            
            // Buscar instância
            $instance = Instance::getById($instanceId);
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Buscar webhook
            $webhook = InstanceWebhook::getById($webhookId);
            if (!$webhook || $webhook['instance_id'] != $instanceId) {
                return Response::notFound('Webhook não encontrado');
            }
            
            // Atualizar webhook
            $updateData = [];
            if (isset($data['url'])) $updateData['webhook_url'] = $data['url'];
            if (isset($data['events'])) $updateData['events'] = $data['events'];
            if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];
            
            if (empty($updateData)) {
                return Response::error('Nenhum dado para atualizar', 400);
            }
            
            $result = InstanceWebhook::update($webhookId, $updateData);
            
            if (!$result) {
                return Response::error('Erro ao atualizar webhook', 500);
            }
            
            // Buscar webhook atualizado
            $updatedWebhook = InstanceWebhook::getById($webhookId);
            
            Logger::info('Instance webhook updated', [
                'webhook_id' => $webhookId,
                'instance_id' => $instanceId,
                'update_data' => $updateData
            ]);
            
            return Response::json([
                'success' => true,
                'message' => 'Webhook atualizado com sucesso',
                'data' => $updatedWebhook
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to update instance webhook', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId,
                'webhook_id' => $webhookId
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Deletar webhook
     */
    public static function delete($instanceId, $webhookId)
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
            
            // Buscar instância
            $instance = Instance::getById($instanceId);
            if (!$instance || $instance['company_id'] != $company['id']) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Buscar webhook
            $webhook = InstanceWebhook::getById($webhookId);
            if (!$webhook || $webhook['instance_id'] != $instanceId) {
                return Response::notFound('Webhook não encontrado');
            }
            
            // Deletar webhook
            $result = InstanceWebhook::delete($webhookId);
            
            if (!$result) {
                return Response::error('Erro ao deletar webhook', 500);
            }
            
            Logger::info('Instance webhook deleted', [
                'webhook_id' => $webhookId,
                'instance_id' => $instanceId
            ]);
            
            return Response::json([
                'success' => true,
                'message' => 'Webhook removido com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete instance webhook', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId,
                'webhook_id' => $webhookId
            ]);
            
            return Response::error('Erro interno do servidor', 500);
        }
    }
}
