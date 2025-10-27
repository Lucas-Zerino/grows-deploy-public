<?php

namespace App\Models;

use App\Utils\Database;

class Event
{
    public static function create(array $data): array
    {
        $id = Database::insert('events', [
            'message_id' => $data['message_id'] ?? null,
            'instance_id' => $data['instance_id'],
            'company_id' => $data['company_id'],
            'event_type' => $data['event_type'],
            'payload' => json_encode($data['payload'] ?? []),
        ]);
        
        return self::findById($id);
    }
    
    public static function findById(int $id): ?array
    {
        $event = Database::fetchOne('SELECT * FROM events WHERE id = :id', ['id' => $id]);
        
        if ($event && isset($event['payload'])) {
            $event['payload'] = json_decode($event['payload'], true);
        }
        
        return $event;
    }
    
    public static function findByCompany(int $companyId, int $limit = 50, int $offset = 0): array
    {
        $events = Database::fetchAll(
            'SELECT * FROM events 
             WHERE company_id = :company_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset',
            [
                'company_id' => $companyId,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );
        
        foreach ($events as &$event) {
            if (isset($event['payload'])) {
                $event['payload'] = json_decode($event['payload'], true);
            }
        }
        
        return $events;
    }
    
    public static function findByMessage(int $messageId): array
    {
        $events = Database::fetchAll(
            'SELECT * FROM events WHERE message_id = :message_id ORDER BY created_at ASC',
            ['message_id' => $messageId]
        );
        
        foreach ($events as &$event) {
            if (isset($event['payload'])) {
                $event['payload'] = json_decode($event['payload'], true);
            }
        }
        
        return $events;
    }
    
    public static function findByInstance(int $instanceId, int $limit = 50, int $offset = 0): array
    {
        $events = Database::fetchAll(
            'SELECT * FROM events 
             WHERE instance_id = :instance_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset',
            [
                'instance_id' => $instanceId,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );
        
        foreach ($events as &$event) {
            if (isset($event['payload'])) {
                $event['payload'] = json_decode($event['payload'], true);
            }
        }
        
        return $events;
    }
}

