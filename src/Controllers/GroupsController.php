<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\WahaService;
use App\Utils\Response;
use App\Utils\Logger;
use App\Utils\Database;
use App\Utils\Validator;

class GroupsController
{
    /**
     * POST /group/create
     * Criar um novo grupo
     */
    public static function createGroup(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('name', 'Nome do grupo é obrigatório');
            $validator->required('participants', 'Lista de participantes é obrigatória');
            $validator->array('participants', 'Participants deve ser um array');

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
            $wahaResponse = $wahaService->createGroup(
                $instance['external_instance_id'],
                $input['name'],
                $input['participants']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao criar grupo: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $group = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $input['name'],
                'participants' => $wahaResponse['data']['participants'] ?? $input['participants'],
                'created' => $wahaResponse['data']['created'] ?? true,
            ];

            Logger::info('Grupo criado com sucesso', [
                'instance_id' => $instance['id'],
                'group_name' => $input['name'],
                'participants_count' => count($input['participants']),
            ]);

            Response::success($group);

        } catch (\Exception $e) {
            Logger::error('Erro ao criar grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/info
     * Obter informações detalhadas de um grupo
     */
    public static function getGroupInfo(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');

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
            $wahaResponse = $wahaService->getGroupInfo(
                $instance['external_instance_id'],
                $input['groupjid']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao buscar informações do grupo: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $groupInfo = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? $input['groupjid'],
                'name' => $wahaResponse['data']['name'] ?? null,
                'description' => $wahaResponse['data']['description'] ?? null,
                'participants' => $wahaResponse['data']['participants'] ?? [],
                'admins' => $wahaResponse['data']['admins'] ?? [],
                'inviteCode' => $input['getInviteLink'] ? ($wahaResponse['data']['inviteCode'] ?? null) : null,
                'isLocked' => $wahaResponse['data']['isLocked'] ?? false,
                'isAnnouncement' => $wahaResponse['data']['isAnnouncement'] ?? false,
                'created' => $wahaResponse['data']['created'] ?? null,
                'subjectTime' => $wahaResponse['data']['subjectTime'] ?? null,
                'subjectOwner' => $wahaResponse['data']['subjectOwner'] ?? null,
            ];

            Logger::info('Informações do grupo obtidas', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
            ]);

            Response::success($groupInfo);

        } catch (\Exception $e) {
            Logger::error('Erro ao buscar informações do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/inviteInfo
     * Obter informações de um grupo pelo código de convite
     */
    public static function getInviteInfo(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('inviteCode', 'Código de convite é obrigatório');

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
            $wahaResponse = $wahaService->getGroupJoinInfo(
                $instance['external_instance_id'],
                $input['inviteCode']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao buscar informações do convite: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $inviteInfo = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $wahaResponse['data']['name'] ?? null,
                'description' => $wahaResponse['data']['description'] ?? null,
                'participantsCount' => $wahaResponse['data']['participantsCount'] ?? 0,
                'isInviteV4' => $wahaResponse['data']['isInviteV4'] ?? false,
                'inviteCode' => $input['inviteCode'],
            ];

            Logger::info('Informações do convite obtidas', [
                'instance_id' => $instance['id'],
                'inviteCode' => $input['inviteCode'],
            ]);

            Response::success($inviteInfo);

        } catch (\Exception $e) {
            Logger::error('Erro ao buscar informações do convite', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/join
     * Entrar em um grupo usando código de convite
     */
    public static function joinGroup(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('inviteCode', 'Código de convite é obrigatório');

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
            $wahaResponse = $wahaService->joinGroup(
                $instance['external_instance_id'],
                $input['inviteCode']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao entrar no grupo: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $group = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $wahaResponse['data']['name'] ?? null,
                'joined' => true,
            ];

            Logger::info('Entrada no grupo realizada', [
                'instance_id' => $instance['id'],
                'inviteCode' => $input['inviteCode'],
                'groupJid' => $group['jid'],
            ]);

            Response::success($group);

        } catch (\Exception $e) {
            Logger::error('Erro ao entrar no grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * GET /group/invitelink/:groupJID
     * Gerar link de convite para um grupo
     */
    public static function getInviteLink(string $groupJID): void
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
            $wahaResponse = $wahaService->getGroupInviteCode(
                $instance['external_instance_id'],
                $groupJID
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao gerar link de convite: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $inviteLink = [
                'groupJID' => $groupJID,
                'inviteCode' => $wahaResponse['data']['inviteCode'] ?? null,
                'inviteLink' => $wahaResponse['data']['inviteLink'] ?? null,
            ];

            Logger::info('Link de convite gerado', [
                'instance_id' => $instance['id'],
                'groupJID' => $groupJID,
            ]);

            Response::success($inviteLink);

        } catch (\Exception $e) {
            Logger::error('Erro ao gerar link de convite', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/updateDescription
     * Atualizar descrição do grupo
     */
    public static function updateDescription(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');
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
            $wahaResponse = $wahaService->updateGroupDescription(
                $instance['external_instance_id'],
                $input['groupjid'],
                $input['description']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar descrição: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Descrição do grupo atualizada', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
            ]);

            Response::success([
                'groupjid' => $input['groupjid'],
                'description' => $input['description'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar descrição do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/updateName
     * Atualizar nome do grupo
     */
    public static function updateName(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');
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
            $wahaResponse = $wahaService->updateGroupSubject(
                $instance['external_instance_id'],
                $input['groupjid'],
                $input['name']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar nome: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Nome do grupo atualizado', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
                'new_name' => $input['name'],
            ]);

            Response::success([
                'groupjid' => $input['groupjid'],
                'name' => $input['name'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar nome do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/updateImage
     * Atualizar imagem do grupo
     */
    public static function updateImage(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');
            $validator->required('image', 'Imagem é obrigatória');

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
            $wahaResponse = $wahaService->updateGroupPicture(
                $instance['external_instance_id'],
                $input['groupjid'],
                $input['image']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar imagem: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Imagem do grupo atualizada', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
            ]);

            Response::success([
                'groupjid' => $input['groupjid'],
                'image' => $input['image'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar imagem do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/updateParticipants
     * Gerenciar participantes do grupo
     */
    public static function updateParticipants(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');
            $validator->required('action', 'Ação é obrigatória');
            $validator->required('participants', 'Lista de participantes é obrigatória');

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
                    $wahaResponse = $wahaService->addGroupParticipants(
                        $instance['external_instance_id'],
                        $input['groupjid'],
                        $input['participants']
                    );
                    break;
                case 'remove':
                    $wahaResponse = $wahaService->removeGroupParticipants(
                        $instance['external_instance_id'],
                        $input['groupjid'],
                        $input['participants']
                    );
                    break;
                case 'promote':
                    $wahaResponse = $wahaService->promoteGroupParticipants(
                        $instance['external_instance_id'],
                        $input['groupjid'],
                        $input['participants']
                    );
                    break;
                case 'demote':
                    $wahaResponse = $wahaService->demoteGroupParticipants(
                        $instance['external_instance_id'],
                        $input['groupjid'],
                        $input['participants']
                    );
                    break;
                default:
                    Response::error('Ação inválida. Use: add, remove, promote ou demote', 400);
                    return;
            }

            if (!$wahaResponse['success']) {
                Response::error('Erro ao atualizar participantes: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Participantes do grupo atualizados', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
                'action' => $input['action'],
                'participants_count' => count($input['participants']),
            ]);

            Response::success([
                'groupjid' => $input['groupjid'],
                'action' => $input['action'],
                'participants' => $input['participants'],
                'updated' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar participantes do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /group/leave
     * Sair do grupo
     */
    public static function leaveGroup(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('groupjid', 'JID do grupo é obrigatório');

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
            $wahaResponse = $wahaService->leaveGroup(
                $instance['external_instance_id'],
                $input['groupjid']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao sair do grupo: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Saída do grupo realizada', [
                'instance_id' => $instance['id'],
                'groupjid' => $input['groupjid'],
            ]);

            Response::success([
                'groupjid' => $input['groupjid'],
                'left' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao sair do grupo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }
}
