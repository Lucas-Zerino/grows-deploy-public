<?php

namespace App\Models;

use App\Utils\Database;

class Message
{
    public static function create(array $data): array
    {
        $id = Database::insert('messages', [
            'instance_id' => $data['instance_id'],
            'company_id' => $data['company_id'],
            'direction' => $data['direction'],
            'phone_to' => $data['phone_to'],
            'phone_from' => $data['phone_from'],
            'message_type' => $data['message_type'] ?? 'text',
            'content' => $data['content'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'status' => $data['status'] ?? 'queued',
            'priority' => $data['priority'] ?? 5,
            'external_id' => $data['external_id'] ?? null,
        ]);
        
        return self::findById($id);
    }
    
    public static function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM messages WHERE id = :id', ['id' => $id]);
    }
    
    public static function findByCompany(int $companyId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT * FROM messages 
             WHERE company_id = :company_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset',
            [
                'company_id' => $companyId,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );
    }
    
    public static function findByInstance(int $instanceId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT * FROM messages 
             WHERE instance_id = :instance_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset',
            [
                'instance_id' => $instanceId,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );
    }
    
    public static function updateStatus(int $id, string $status, array $extra = []): bool
    {
        $data = ['status' => $status];
        
        if ($status === 'sent' && !isset($extra['sent_at'])) {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }
        
        if ($status === 'delivered' && !isset($extra['delivered_at'])) {
            $data['delivered_at'] = date('Y-m-d H:i:s');
        }
        
        if ($status === 'read' && !isset($extra['read_at'])) {
            $data['read_at'] = date('Y-m-d H:i:s');
        }
        
        $data = array_merge($data, $extra);
        
        $updated = Database::update('messages', $data, 'id = :id', ['id' => $id]);
        return $updated > 0;
    }
    
    public static function findByExternalId(string $externalId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM messages WHERE external_id = :external_id',
            ['external_id' => $externalId]
        );
    }
}

