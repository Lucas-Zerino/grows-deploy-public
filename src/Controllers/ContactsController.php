<?php

namespace App\Controllers;

use App\Models\Instance;
use App\Services\WahaService;
use App\Utils\Response;
use App\Utils\Logger;
use App\Utils\Database;
use App\Utils\Validator;

class ContactsController
{
    /**
     * GET /contacts
     * Retorna lista de contatos do WhatsApp
     */
    public static function getAllContacts(): void
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
            $wahaResponse = $wahaService->getContacts($instance['external_instance_id']);

            if (!$wahaResponse['success']) {
                Response::error('Erro ao buscar contatos: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $contacts = [];
            if (isset($wahaResponse['data']) && is_array($wahaResponse['data'])) {
                foreach ($wahaResponse['data'] as $contact) {
                    $contacts[] = [
                        'id' => $contact['id'] ?? null,
                        'jid' => $contact['jid'] ?? null,
                        'name' => $contact['name'] ?? null,
                        'pushname' => $contact['pushname'] ?? null,
                        'phone' => $contact['phone'] ?? null,
                        'profilePictureUrl' => $contact['profilePictureUrl'] ?? null,
                        'isBusiness' => $contact['isBusiness'] ?? false,
                        'isEnterprise' => $contact['isEnterprise'] ?? false,
                        'isMe' => $contact['isMe'] ?? false,
                        'isGroup' => $contact['isGroup'] ?? false,
                        'isUser' => $contact['isUser'] ?? false,
                        'isWAContact' => $contact['isWAContact'] ?? false,
                        'isMyContact' => $contact['isMyContact'] ?? false,
                        'isBlocked' => $contact['isBlocked'] ?? false,
                        'labels' => $contact['labels'] ?? [],
                    ];
                }
            }

            Logger::info('Lista de contatos retornada', [
                'instance_id' => $instance['id'],
                'total_contacts' => count($contacts),
            ]);

            Response::success($contacts);

        } catch (\Exception $e) {
            Logger::error('Erro ao buscar contatos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /contact/add
     * Adiciona um contato à agenda
     */
    public static function addContact(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('phone', 'Número de telefone é obrigatório');
            $validator->required('name', 'Nome é obrigatório');
            $validator->phone('phone', 'Número de telefone inválido');

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
            $wahaResponse = $wahaService->createContact(
                $instance['external_instance_id'],
                $input['phone'],
                $input['name']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao adicionar contato: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $contact = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $input['name'],
                'phone' => $input['phone'],
                'pushname' => $wahaResponse['data']['pushname'] ?? null,
                'profilePictureUrl' => $wahaResponse['data']['profilePictureUrl'] ?? null,
                'isBusiness' => $wahaResponse['data']['isBusiness'] ?? false,
                'isEnterprise' => $wahaResponse['data']['isEnterprise'] ?? false,
                'isMe' => $wahaResponse['data']['isMe'] ?? false,
                'isGroup' => $wahaResponse['data']['isGroup'] ?? false,
                'isUser' => $wahaResponse['data']['isUser'] ?? false,
                'isWAContact' => $wahaResponse['data']['isWAContact'] ?? false,
                'isMyContact' => $wahaResponse['data']['isMyContact'] ?? false,
                'isBlocked' => $wahaResponse['data']['isBlocked'] ?? false,
                'labels' => $wahaResponse['data']['labels'] ?? [],
            ];

            Logger::info('Contato adicionado com sucesso', [
                'instance_id' => $instance['id'],
                'phone' => $input['phone'],
                'name' => $input['name'],
            ]);

            Response::success($contact);

        } catch (\Exception $e) {
            Logger::error('Erro ao adicionar contato', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /contact/remove
     * Remove um contato da agenda
     */
    public static function removeContact(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('phone', 'Número de telefone é obrigatório');
            $validator->phone('phone', 'Número de telefone inválido');

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
            $wahaResponse = $wahaService->deleteContact(
                $instance['external_instance_id'],
                $input['phone']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao remover contato: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Contato removido com sucesso', [
                'instance_id' => $instance['id'],
                'phone' => $input['phone'],
            ]);

            Response::success([
                'phone' => $input['phone'],
                'removed' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao remover contato', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /chat/details
     * Obter detalhes completos de um contato ou chat
     */
    public static function getChatDetails(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('number', 'Número é obrigatório');

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
            $wahaResponse = $wahaService->getContactInfo(
                $instance['external_instance_id'],
                $input['number']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao buscar detalhes do chat: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $chatDetails = [
                'id' => $wahaResponse['data']['id'] ?? null,
                'jid' => $wahaResponse['data']['jid'] ?? null,
                'name' => $wahaResponse['data']['name'] ?? null,
                'phone' => $input['number'],
                'pushname' => $wahaResponse['data']['pushname'] ?? null,
                'profilePictureUrl' => $wahaResponse['data']['profilePictureUrl'] ?? null,
                'isBusiness' => $wahaResponse['data']['isBusiness'] ?? false,
                'isEnterprise' => $wahaResponse['data']['isEnterprise'] ?? false,
                'isMe' => $wahaResponse['data']['isMe'] ?? false,
                'isGroup' => $wahaResponse['data']['isGroup'] ?? false,
                'isUser' => $wahaResponse['data']['isUser'] ?? false,
                'isWAContact' => $wahaResponse['data']['isWAContact'] ?? false,
                'isMyContact' => $wahaResponse['data']['isMyContact'] ?? false,
                'isBlocked' => $wahaResponse['data']['isBlocked'] ?? false,
                'labels' => $wahaResponse['data']['labels'] ?? [],
                'about' => $wahaResponse['data']['about'] ?? null,
                'status' => $wahaResponse['data']['status'] ?? null,
                'lastSeen' => $wahaResponse['data']['lastSeen'] ?? null,
            ];

            Logger::info('Detalhes do chat retornados', [
                'instance_id' => $instance['id'],
                'number' => $input['number'],
            ]);

            Response::success($chatDetails);

        } catch (\Exception $e) {
            Logger::error('Erro ao buscar detalhes do chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /chat/check
     * Verificar números no WhatsApp
     */
    public static function checkNumbers(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('numbers', 'Lista de números é obrigatória');
            $validator->array('numbers', 'Numbers deve ser um array');

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
            $wahaResponse = $wahaService->checkContacts(
                $instance['external_instance_id'],
                $input['numbers']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao verificar números: ' . $wahaResponse['error'], 500);
                return;
            }

            // Formatar resposta no padrão UAZAPI
            $results = [];
            if (isset($wahaResponse['data']) && is_array($wahaResponse['data'])) {
                foreach ($wahaResponse['data'] as $result) {
                    $results[] = [
                        'number' => $result['number'] ?? null,
                        'jid' => $result['jid'] ?? null,
                        'exists' => $result['exists'] ?? false,
                        'name' => $result['name'] ?? null,
                        'pushname' => $result['pushname'] ?? null,
                        'isBusiness' => $result['isBusiness'] ?? false,
                        'isEnterprise' => $result['isEnterprise'] ?? false,
                        'isGroup' => $result['isGroup'] ?? false,
                        'isUser' => $result['isUser'] ?? false,
                        'isWAContact' => $result['isWAContact'] ?? false,
                    ];
                }
            }

            Logger::info('Verificação de números concluída', [
                'instance_id' => $instance['id'],
                'total_numbers' => count($input['numbers']),
                'results' => count($results),
            ]);

            Response::success($results);

        } catch (\Exception $e) {
            Logger::error('Erro ao verificar números', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /contact/block
     * Bloquear contato
     */
    public static function blockContact(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('phone', 'Número de telefone é obrigatório');
            $validator->phone('phone', 'Número de telefone inválido');

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
            $wahaResponse = $wahaService->blockContact(
                $instance['external_instance_id'],
                $input['phone']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao bloquear contato: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Contato bloqueado com sucesso', [
                'instance_id' => $instance['id'],
                'phone' => $input['phone'],
            ]);

            Response::success([
                'phone' => $input['phone'],
                'blocked' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao bloquear contato', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }

    /**
     * POST /contact/unblock
     * Desbloquear contato
     */
    public static function unblockContact(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar entrada
            $validator = new Validator($input);
            $validator->required('phone', 'Número de telefone é obrigatório');
            $validator->phone('phone', 'Número de telefone inválido');

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
            $wahaResponse = $wahaService->unblockContact(
                $instance['external_instance_id'],
                $input['phone']
            );

            if (!$wahaResponse['success']) {
                Response::error('Erro ao desbloquear contato: ' . $wahaResponse['error'], 500);
                return;
            }

            Logger::info('Contato desbloqueado com sucesso', [
                'instance_id' => $instance['id'],
                'phone' => $input['phone'],
            ]);

            Response::success([
                'phone' => $input['phone'],
                'unblocked' => true,
            ]);

        } catch (\Exception $e) {
            Logger::error('Erro ao desbloquear contato', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Response::error('Erro interno do servidor', 500);
        }
    }
}
