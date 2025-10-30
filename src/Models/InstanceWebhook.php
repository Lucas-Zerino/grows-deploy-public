<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class InstanceWebhook
{
    /**
     * Criar novo webhook para instância
     */
    public static function create(array $data): ?int
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO instance_webhooks (instance_id, webhook_url, events, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            // Se não especificar eventos, usar todos os eventos disponíveis
            $defaultEvents = [
                'message', 'message.any', 'message.ack', 'message.reaction', 'message.revoked', 'message.edited',
                'session.status', 'presence.update', 'group.v2.join', 'group.v2.leave', 'group.v2.update', 'group.v2.participants',
                'poll.vote', 'poll.vote.failed', 'chat.archive', 'call.received', 'call.accepted', 'call.rejected',
                'label.upsert', 'label.deleted', 'label.chat.added', 'label.chat.deleted', 'event.response', 'event.response.failed', 'engine.event'
            ];
            
            $result = $stmt->execute([
                $data['instance_id'],
                $data['webhook_url'],
                json_encode($data['events'] ?? $defaultEvents),
                $data['is_active'] ?? true
            ]);
            
            if ($result) {
                $id = $db->lastInsertId();
                Logger::info('Instance webhook created', [
                    'webhook_id' => $id,
                    'instance_id' => $data['instance_id'],
                    'webhook_url' => $data['webhook_url']
                ]);
                return (int) $id;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Logger::error('Database error creating instance webhook', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }
    
    /**
     * Buscar webhooks ativos por instância
     */
    public static function getActiveByInstanceId(int $instanceId): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM instance_webhooks 
                WHERE instance_id = ? AND is_active = true 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$instanceId]);
            
            $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decodificar JSON events
            foreach ($webhooks as &$webhook) {
                $webhook['events'] = json_decode($webhook['events'], true) ?? [];
            }
            
            return $webhooks;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting instance webhooks', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId
            ]);
            return [];
        }
    }
    
    /**
     * Buscar todos os webhooks por instância
     */
    public static function getByInstanceId(int $instanceId): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM instance_webhooks 
                WHERE instance_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$instanceId]);
            
            $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Decodificar JSON events
            foreach ($webhooks as &$webhook) {
                $webhook['events'] = json_decode($webhook['events'], true) ?? [];
            }
            
            return $webhooks;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting instance webhooks', [
                'error' => $e->getMessage(),
                'instance_id' => $instanceId
            ]);
            return [];
        }
    }
    
    /**
     * Atualizar webhook
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key === 'events') {
                    $fields[] = "{$key} = :{$key}";
                    $values[$key] = json_encode($value);
                } else {
                    $fields[] = "{$key} = :{$key}";
                    $values[$key] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values['id'] = $id;
            $values['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE instance_webhooks SET " . implode(', ', $fields) . ", updated_at = :updated_at WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                Logger::info('Instance webhook updated', [
                    'id' => $id,
                    'data' => $data
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Instance webhook update failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Deletar webhook
     */
    public static function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                DELETE FROM instance_webhooks 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::info('Instance webhook deleted', [
                    'id' => $id
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error deleting instance webhook', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Incrementar contador de tentativas
     */
    public static function incrementRetryCount(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instance_webhooks 
                SET retry_count = retry_count + 1, 
                    last_retry_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::debug('Instance webhook retry count incremented', [
                    'id' => $id
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error incrementing webhook retry count', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Resetar contador de tentativas
     */
    public static function resetRetryCount(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instance_webhooks 
                SET retry_count = 0, 
                    last_retry_at = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::debug('Instance webhook retry count reset', [
                    'id' => $id
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error resetting webhook retry count', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Buscar webhook por ID
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM instance_webhooks 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            $webhook = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($webhook) {
                $webhook['events'] = json_decode($webhook['events'], true) ?? [];
            }
            
            return $webhook ?: null;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting webhook by id', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return null;
        }
    }
}
