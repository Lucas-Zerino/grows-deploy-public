<?php

namespace App\Models;

use App\Utils\Database;

class Admin
{
    public static function create(array $data): array
    {
        // Hash da senha se fornecida
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        $id = Database::insert('admins', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_superadmin' => $data['is_superadmin'] ?? false,
            'status' => $data['status'] ?? 'active',
        ]);
        
        return self::findById($id);
    }
    
    public static function findById(int $id): ?array
    {
        $admin = Database::fetchOne('SELECT * FROM admins WHERE id = :id', ['id' => $id]);
        
        if ($admin) {
            unset($admin['password']); // Nunca retornar senha
        }
        
        return $admin;
    }
    
    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM admins WHERE email = :email', ['email' => $email]);
    }
    
    public static function findByToken(string $token): ?array
    {
        $admin = Database::fetchOne('SELECT * FROM admins WHERE token = :token', ['token' => $token]);
        
        if ($admin) {
            unset($admin['password']); // Nunca retornar senha
        }
        
        return $admin;
    }
    
    public static function findAll(int $limit = 100, int $offset = 0): array
    {
        $admins = Database::fetchAll(
            'SELECT id, name, email, token, is_superadmin, status, last_login, created_at, updated_at 
             FROM admins 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset',
            ['limit' => $limit, 'offset' => $offset]
        );
        
        return $admins;
    }
    
    public static function verifyPassword(string $email, string $password): ?array
    {
        $admin = Database::fetchOne('SELECT * FROM admins WHERE email = :email', ['email' => $email]);
        
        if (!$admin) {
            return null;
        }
        
        if (!password_verify($password, $admin['password'])) {
            return null;
        }
        
        // Atualizar last_login
        Database::update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $admin['id']]);
        
        unset($admin['password']);
        return $admin;
    }
    
    public static function updateStatus(int $id, string $status): bool
    {
        $updated = Database::update(
            'admins',
            ['status' => $status],
            'id = :id',
            ['id' => $id]
        );
        
        return $updated > 0;
    }
    
    public static function updatePassword(int $id, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $updated = Database::update(
            'admins',
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $id]
        );
        
        return $updated > 0;
    }
    
    public static function delete(int $id): bool
    {
        // Não permitir deletar o último superadmin
        $superadminCount = Database::fetchOne(
            'SELECT COUNT(*) as count FROM admins WHERE is_superadmin = true'
        );
        
        if ($superadminCount['count'] <= 1) {
            $admin = self::findById($id);
            if ($admin && $admin['is_superadmin']) {
                return false; // Não pode deletar o último superadmin
            }
        }
        
        $deleted = Database::delete('admins', 'id = :id', ['id' => $id]);
        return $deleted > 0;
    }
    
    public static function exists(string $email): bool
    {
        $result = Database::fetchOne('SELECT COUNT(*) as count FROM admins WHERE email = :email', ['email' => $email]);
        return $result['count'] > 0;
    }
}

