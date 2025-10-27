<?php

namespace App\Controllers;

use App\Models\Company;
use App\Services\QueueManagerService;
use App\Utils\Response;
use App\Utils\Logger;

class CompanyController
{
    /**
     * Listar todas as empresas (superadmin)
     * GET /api/admin/companies
     */
    public static function index(): void
    {
        try {
            $companies = Company::getAll();
            
            Response::json([
                'success' => true,
                'data' => $companies,
                'total' => count($companies),
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to list companies', [
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'success' => false,
                'message' => 'Erro ao listar empresas',
            ], 500);
        }
    }
    
    /**
     * Buscar empresa por ID (superadmin)
     * GET /api/admin/companies/:id
     */
    public static function show(array $params): void
    {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa é obrigatório',
                ], 400);
                return;
            }
            
            // Validar se o ID é um número válido
            if (!is_numeric($id)) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa deve ser um número válido',
                ], 400);
                return;
            }
            
            $id = (int) $id;
            $company = Company::getById($id);
            
            if (!$company) {
                Response::json([
                    'success' => false,
                    'message' => 'Empresa não encontrada',
                ], 404);
                return;
            }
            
            Response::json([
                'success' => true,
                'data' => $company,
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get company', [
                'company_id' => $params['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'success' => false,
                'message' => 'Erro ao buscar empresa',
            ], 500);
        }
    }
    
    /**
     * Criar nova empresa (superadmin)
     * POST /api/admin/companies
     */
    public static function create(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            if (empty($data['name'])) {
                Response::json([
                    'success' => false,
                    'message' => 'Nome da empresa é obrigatório',
                ], 400);
                return;
            }
            
            // Criar empresa
            $companyId = Company::create($data);
            
            if (!$companyId) {
                Response::json([
                    'success' => false,
                    'message' => 'Erro ao criar empresa',
                ], 500);
                return;
            }
            
            // Buscar dados da empresa criada
            $company = Company::getById($companyId);
            
            Logger::info('Company created by superadmin', [
                'company_id' => $company['id'],
                'company_name' => $company['name'],
                'admin_id' => $_SERVER['AUTHENTICATED_ADMIN']['id'] ?? null,
            ]);
            
            // Criar filas da empresa no RabbitMQ
            try {
                QueueManagerService::createCompanyQueues($company['id']);
                
                Logger::info('Company queues created', [
                    'company_id' => $company['id'],
                ]);
                
            } catch (\Exception $e) {
                Logger::error('Failed to create company queues', [
                    'company_id' => $company['id'],
                    'error' => $e->getMessage(),
                ]);
                
                // Não falhar a criação da empresa se as filas falharem
                // As filas podem ser criadas manualmente depois
            }
            
            Response::json([
                'success' => true,
                'message' => 'Empresa criada com sucesso',
                'data' => $company,
            ], 201);
            
        } catch (\Exception $e) {
            Logger::error('Failed to create company', [
                'error' => $e->getMessage(),
                'admin_id' => $_SERVER['AUTHENTICATED_ADMIN']['id'] ?? null,
            ]);
            
            Response::json([
                'success' => false,
                'message' => 'Erro ao criar empresa: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Atualizar empresa (superadmin)
     * PUT /api/admin/companies/:id
     */
    public static function update(array $params): void
    {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa é obrigatório',
                ], 400);
                return;
            }
            
            // Validar se o ID é um número válido
            if (!is_numeric($id)) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa deve ser um número válido',
                ], 400);
                return;
            }
            
            $id = (int) $id;
            $company = Company::getById($id);
            
            if (!$company) {
                Response::json([
                    'success' => false,
                    'message' => 'Empresa não encontrada',
                ], 404);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Atualizar campos permitidos
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            
            if (empty($updateData)) {
                Response::json([
                    'success' => false,
                    'message' => 'Nenhum campo para atualizar',
                ], 400);
                return;
            }
            
            $updated = Company::update($id, $updateData);
            
            Logger::info('Company updated by superadmin', [
                'company_id' => $id,
                'updated_fields' => array_keys($updateData),
                'admin_id' => $_SERVER['AUTHENTICATED_ADMIN']['id'] ?? null,
            ]);
            
            Response::json([
                'success' => true,
                'message' => 'Empresa atualizada com sucesso',
                'data' => $updated,
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to update company', [
                'company_id' => $params['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'success' => false,
                'message' => 'Erro ao atualizar empresa',
            ], 500);
        }
    }
    
    /**
     * Deletar empresa (superadmin)
     * DELETE /api/admin/companies/:id
     */
    public static function delete(array $params): void
    {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa é obrigatório',
                ], 400);
                return;
            }
            
            // Validar se o ID é um número válido
            if (!is_numeric($id)) {
                Response::json([
                    'success' => false,
                    'message' => 'ID da empresa deve ser um número válido',
                ], 400);
                return;
            }
            
            $id = (int) $id;
            $company = Company::getById($id);
            
            if (!$company) {
                Response::json([
                    'success' => false,
                    'message' => 'Empresa não encontrada',
                ], 404);
                return;
            }
            
            // Soft delete (atualiza status para 'deleted')
            Company::update($id, ['status' => 'deleted']);
            
            Logger::warning('Company deleted by superadmin', [
                'company_id' => $id,
                'company_name' => $company['name'],
                'admin_id' => $_SERVER['AUTHENTICATED_ADMIN']['id'] ?? null,
            ]);
            
            // Aqui poderia remover as filas também, mas vamos deixar para GC
            
            Response::json([
                'success' => true,
                'message' => 'Empresa removida com sucesso',
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete company', [
                'company_id' => $params['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Response::json([
                'success' => false,
                'message' => 'Erro ao remover empresa',
            ], 500);
        }
    }
}

