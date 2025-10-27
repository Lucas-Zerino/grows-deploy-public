<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class Company
{
    /**
     * Buscar empresa por ID
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM companies 
                WHERE id = ? AND status != 'deleted'
            ");
            $stmt->execute([$id]);
            
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $company ?: null;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting company by id', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return null;
        }
    }
    
    
    /**
     * Criar nova empresa
     */
    public static function create(array $data): ?int
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO companies (name, status, created_at, updated_at) 
                VALUES (?, 'active', NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $data['name']
            ]);
            
            if ($result) {
                $id = $db->lastInsertId();
                Logger::info('Company created', [
                    'company_id' => $id,
                    'name' => $data['name']
                ]);
                return (int) $id;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Logger::error('Database error creating company', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }
    
    /**
     * Atualizar empresa
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE companies 
                SET 
                    name = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['name'] ?? '',
                $id
            ]);
            
            if ($result) {
                Logger::info('Company updated', [
                    'company_id' => $id,
                    'data' => $data
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error updating company', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Deletar empresa
     */
    public static function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE companies 
                SET status = 'deleted', updated_at = NOW() 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::info('Company deleted', [
                    'company_id' => $id
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Database error deleting company', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }
    
    /**
     * Listar todas as empresas
     */
    public static function getAll(): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM companies 
                WHERE status != 'deleted' 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            Logger::error('Database error getting all companies', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Alias para getAll() para compatibilidade
     */
    public static function all(): array
    {
        return self::getAll();
    }
    
    /**
     * Alias para getById() para compatibilidade
     */
    public static function findById(int $id): ?array
    {
        return self::getById($id);
    }
    
    /**
     * Buscar empresa por token
     */
    public static function getByToken(string $token): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM companies 
                WHERE token = ? AND status != 'deleted'
            ");
            $stmt->execute([$token]);
            
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $company ?: null;
            
        } catch (\Exception $e) {
            Logger::error('Database error getting company by token', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);
            return null;
        }
    }
}