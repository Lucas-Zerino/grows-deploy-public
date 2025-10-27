<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class Instance
{
    /**
     * Criar nova instância
     */
    public static function create(array $data): ?int
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO instances (company_id, provider_id, instance_name, phone_number, webhook_url, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'creating', NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $data['company_id'],
                $data['provider_id'],
                $data['instance_name'],
                $data['phone_number'] ?? null,
                $data['webhook_url'] ?? null
            ]);
            
            if ($result) {
                $id = $db->lastInsertId();
                Logger::info('Instance created', [
                    'instance_id' => $id,
                    'company_id' => $data['company_id'],
                    'instance_name' => $data['instance_name']
                ]);
                return (int) $id;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Logger::error('Database error creating instance', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }
    
    /**
     * Buscar instâncias por company_id
     */
    public static function getByCompanyId(int $companyId): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM instances 
                WHERE company_id = ? AND status != 'deleted' 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$companyId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            Logger::error('Database error getting instances by company', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);
            return [];
        }
    }
    
    /**
     * Buscar instância por token
     */
    public static function getByToken(string $token): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT i.*, c.name as company_name 
                FROM instances i 
                JOIN companies c ON i.company_id = c.id 
                WHERE i.token = ? AND i.status != 'deleted'
            ");
            $stmt->execute([$token]);
            
            $instance = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $instance ?: null;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting instance by token', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 8) . '...'
            ]);
            return null;
        }
    }
    
    /**
     * Buscar instância por ID
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT i.*, c.name as company_name 
                FROM instances i 
                JOIN companies c ON i.company_id = c.id 
                WHERE i.id = ? AND i.status != 'deleted'
            ");
            $stmt->execute([$id]);
            
            $instance = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $instance ?: null;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting instance by id', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return null;
        }
    }
    
    /**
     * Atualizar status da instância
     */
    public static function updateStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $id]);
            
            if ($result) {
                Logger::info('Instance status updated', [
                    'instance_id' => $id,
                    'status' => $status
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance status', [
                'error' => $e->getMessage(),
                'id' => $id,
                'status' => $status
            ]);
            return false;
        }
    }
    
    /**
     * Atualizar nome da instância
     */
    public static function updateName(int $id, string $name): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET name = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$name, $id]);
            
            if ($result) {
                Logger::info('Instance name updated', [
                    'instance_id' => $id,
                    'name' => $name
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance name', [
                'error' => $e->getMessage(),
                'id' => $id,
                'name' => $name
            ]);
            return false;
        }
    }
    
    /**
     * Deletar instância
     */
    public static function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET status = 'deleted', updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::info('Instance deleted', [
                    'instance_id' => $id
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error deleting instance', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Atualizar QR code
     */
    public static function updateQRCode(int $id, ?string $qrcode): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET qrcode = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$qrcode, $id]);
            
            if ($result) {
                Logger::debug('Instance QR code updated', [
                    'instance_id' => $id,
                    'has_qrcode' => !empty($qrcode)
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance QR code', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Atualizar código de pareamento
     */
    public static function updatePairCode(int $id, ?string $paircode): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET paircode = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$paircode, $id]);
            
            if ($result) {
                Logger::debug('Instance pair code updated', [
                    'instance_id' => $id,
                    'has_paircode' => !empty($paircode)
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance pair code', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Atualizar informações de perfil
     */
    public static function updateProfile(int $id, array $profileData): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET 
                    profile_name = ?,
                    profile_pic_url = ?,
                    is_business = ?,
                    platform = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $profileData['profile_name'] ?? null,
                $profileData['profile_pic_url'] ?? null,
                $profileData['is_business'] ?? false,
                $profileData['platform'] ?? 'WAHA',
                $id
            ]);
            
            if ($result) {
                Logger::info('Instance profile updated', [
                    'instance_id' => $id,
                    'profile_data' => $profileData
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance profile', [
                'error' => $e->getMessage(),
                'id' => $id,
                'profile_data' => $profileData
            ]);
            return false;
        }
    }
    
    /**
     * Atualizar informações de desconexão
     */
    public static function updateDisconnectInfo(int $id, ?string $reason = null): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instances 
                SET 
                    last_disconnect = NOW(),
                    last_disconnect_reason = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$reason, $id]);
            
            if ($result) {
                Logger::info('Instance disconnect info updated', [
                    'instance_id' => $id,
                    'reason' => $reason
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating instance disconnect info', [
                'error' => $e->getMessage(),
                'id' => $id,
                'reason' => $reason
            ]);
            return false;
        }
    }

    /**
     * Atualizar instância com dados genéricos
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                $fields[] = "{$key} = :{$key}";
                $values[$key] = $value;
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values['id'] = $id;
            $values['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE instances SET " . implode(', ', $fields) . ", updated_at = :updated_at WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                Logger::info('Instance updated', [
                    'id' => $id,
                    'data' => $data
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Instance update failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }
}