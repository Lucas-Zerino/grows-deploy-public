<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\WahaService;
use App\Utils\Response;
use App\Utils\Logger;
use App\Utils\Database;
use App\Utils\Validator;

class CommunitiesController
{
    /**
     * POST /community/create
     * Criar uma comunidade
     */
    public static function createCommunity(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('name', 'Nome da comunidade é obrigatório');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->createCommunity(
                $instance['external_instance_id'],
                $input['name']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao criar comunidade: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $community = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $input['name'],
                'created' => true,
                'announcements' => $wahaResponse['data']['announcements'] ?? null,
            ];

            Logger::info('Comunidade criada com sucesso', [
                'instance_id' => $instance['id'],
                'community_name' => $input['name'],
            ]);

            Response::success($community);

        } catch (\Exception $e) {
            Logger::error('Erro ao criar comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /community/editgroups
     * Gerenciar grupos em uma comunidade
     */
    public static function editGroups(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('community', 'JID da comunidade é obrigatório');
            $validator->required('action', 'Ação é obrigatória');
            $validator->required('groupjids', 'Lista de grupos é obrigatória');
            $validator->array('groupjids', 'groupjids deve ser um array');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API baseado na ação
            $wahaService = new WahaService();
            $wahaResponse = [];

            switch ($input['action']) {
                case 'add':
                    $wahaResponse = $wahaService->addGroupsToCommunity(
                        $instance['external_instance_id'],
                        $input['community'],
                        $input['groupjids']
                    );
                    break;
                case 'remove':
                    $wahaResponse = $wahaService->removeGroupsFromCommunity(
                        $instance['external_instance_id'],
                        $input['community'],
                        $input['groupjids']
                    );
                    break;
                default:
                    Response::error('Ação inválida. Use: add ou remove', 400);
                    return;
            }

            if (!$wahaResponse['success']) {
                Response::error('Erro ao gerenciar grupos da comunidade: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Grupos da comunidade gerenciados', [
                'instance_id' => $instance['id'],
                'community' => $input['community'],
                'action' => $input['action'],
                'groups_count' => count($input['groupjids']),
            ]);

            Response::success([
                'community' => $input['community'],
                'action' => $input['action'],
                'groupjids' => $input['groupjids'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao gerenciar grupos da comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /community/info
     * Obter informações de uma comunidade
     */
    public static function getCommunityInfo(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('community', 'JID da comunidade é obrigatório');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->getCommunityInfo(
                $instance['external_instance_id'],
                $input['community']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao buscar informações da comunidade: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $communityInfo = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? $input['community'],
                'name' => $wahaResponse['data']['name'] ?? null,
                'description' => $wahaResponse['data']['description'] ?? null,
                'groups' => $wahaResponse['data']['groups'] ?? [],
                'announcements' => $wahaResponse['data']['announcements'] ?? null,
                'created' => $wahaResponse['data']['created'] ?? null,
                'participantsCount' => $wahaResponse['data']['participantsCount'] ?? 0,
            ];

            Logger::info('Informações da comunidade obtidas', [
                'instance_id' => $instance['id'],
                'community' => $input['community'],
            ]);

            Response::success($communityInfo);

        } catch (\Exception $e) {
            Logger::error('Erro ao buscar informações da comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /community/leave
     * Sair de uma comunidade
     */
    public static function leaveCommunity(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('community', 'JID da comunidade é obrigatório');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->leaveCommunity(
                $instance['external_instance_id'],
                $input['community']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao sair da comunidade: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Saída da comunidade realizada', [
                'instance_id' => $instance['id'],
                'community' => $input['community'],
            ]);

            Response::success([
                'community' => $input['community'],
                'left' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao sair da comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /community/updateDescription
     * Atualizar descrição da comunidade
     */
    public static function updateDescription(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('community', 'JID da comunidade é obrigatório');
            $validator->required('description', 'Descrição é obrigatória');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->updateCommunityDescription(
                $instance['external_instance_id'],
                $input['community'],
                $input['description']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar descrição: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Descrição da comunidade atualizada', [
                'instance_id' => $instance['id'],
                'community' => $input['community'],
            ]);

            Response::success([
                'community' => $input['community'],
                'description' => $input['description'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar descrição da comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /community/updateName
     * Atualizar nome da comunidade
     */
    public static function updateName(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('community', 'JID da comunidade é obrigatório');
            $validator->required('name', 'Nome é obrigatório');

            if (!$validator->isValid()) {
                Response::error('Dados inválidos: ' . implode(', ', $validator->getErrors()), 400);
                return;
            }

            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->updateCommunityName(
                $instance['external_instance_id'],
                $input['community'],
                $input['name']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar nome: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Nome da comunidade atualizado', [
                'instance_id' => $instance['id'],
                'community' => $input['community'],
                'new_name' => $input['name'],
            ]);

            Response::success([
                'community' => $input['community'],
                'name' => $input['name'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar nome da comunidade', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * GET /community/list
     * Listar todas as comunidades
     */
    public static function listCommunities(): void
    {
        try {
            // Buscar instância ativa
            $instance = Instance::findFirstActive();
            if (!$instance) {
                Response::error('Nenhuma instância ativa encontrada', 400);
                return;
            }

            // Chamar WAHA API
            $wahaService = new WahaService();
            $wahaResponse = $wahaService->listCommunities(
                $instance['external_instance_id']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao listar comunidades: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $communities = [];
            if (isset($wahaResponse['data']) && is_array($wahaResponse['data'])) {
                foreach ($wahaResponse['data'] as $community) {
                    $communities[] = [
                        'id' => $community['id'] ?? null,
                        'jid' => $community['jid'] ?? null,
                        'name' => $community['name'] ?? null,
                        'description' => $community['description'] ?? null,
                        'participantsCount' => $community['participantsCount'] ?? 0,
                        'groupsCount' => $community['groupsCount'] ?? 0,
                        'created' => $community['created'] ?? null,
                    ];
                }
            }

            Logger::info('Lista de comunidades retornada', [
                'instance_id' => $instance['id'],
                'total_communities' => count($communities),
            ]);

            Response::success($communities);

        } catch (\Exception $e) {
            Logger::error('Erro ao listar comunidades', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }
}
