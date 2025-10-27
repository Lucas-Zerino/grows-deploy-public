<?php

namespace App\Models;

use App\Utils\Database;

class Provider
{
    public static function create(array $data): array
    {
        $id = Database::insert('providers', [
            'type' => $data['type'],
            'name' => $data['name'],
            'base_url' => $data['base_url'],
            'api_key' => $data['api_key'] ?? null,
            'max_instances' => $data['max_instances'] ?? 100,
            'is_active' => $data['is_active'] ?? true,
        ]);
        
        return self::findById($id);
    }
    
    public static function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM providers WHERE id = :id', ['id' => $id]);
    }
    
    public static function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM providers';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = true';
        }
        $sql .= ' ORDER BY created_at DESC';
        
        return Database::fetchAll($sql);
    }
    
    public static function findAvailableProvider(string $type = null): ?array
    {
        $sql = 'SELECT * FROM providers 
                WHERE is_active = true 
                AND health_status = :health_status 
                AND current_instances < max_instances';
        
        $params = ['health_status' => 'healthy'];
        
        if ($type) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        
        $sql .= ' ORDER BY current_instances ASC LIMIT 1';
        
        return Database::fetchOne($sql, $params);
    }
    
    public static function updateHealthStatus(int $id, string $status): bool
    {
        $updated = Database::update(
            'providers',
            [
                'health_status' => $status,
                'last_check' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => $id]
        );
        
        return $updated > 0;
    }
    
    public static function incrementInstances(int $id): bool
    {
        $sql = 'UPDATE providers SET current_instances = current_instances + 1 WHERE id = :id';
        $stmt = Database::query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    public static function decrementInstances(int $id): bool
    {
        $sql = 'UPDATE providers SET current_instances = GREATEST(0, current_instances - 1) WHERE id = :id';
        $stmt = Database::query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    public static function update(int $id, array $data): bool
    {
        $updated = Database::update('providers', $data, 'id = :id', ['id' => $id]);
        return $updated > 0;
    }
    
    public static function delete(int $id): bool
    {
        $deleted = Database::delete('providers', 'id = :id', ['id' => $id]);
        return $deleted > 0;
    }
}

