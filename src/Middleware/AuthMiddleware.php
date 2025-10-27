<?php

namespace App\Middleware;

use App\Models\Company;
use App\Models\Admin;
use App\Models\Instance;
use App\Utils\Response;
use App\Utils\Logger;

class AuthMiddleware
{
    public static function authenticate(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            Response::unauthorized('Authorization header missing');
            return null;
        }
        
        // Extrair token do formato "Bearer {token}"
        if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            Response::unauthorized('Invalid authorization format');
            return null;
        }
        
        $token = trim($matches[1]);
        
        // Validar token
        if (empty($token) || strlen($token) < 10) {
            Response::unauthorized('Invalid token format');
            return null;
        }
        
        // Verificar se é placeholder não substituído
        if (preg_match('/^\{\{.*\}\}$/', $token)) {
            Response::unauthorized('Token placeholder not replaced. Please use a real token.');
            return null;
        }
        
        // Primeiro, verificar se é um admin (superadmin ou staff)
        $admin = Admin::findByToken($token);
        
        if ($admin) {
            if ($admin['status'] !== 'active') {
                Logger::warning('Authentication failed - inactive admin', [
                    'admin_id' => $admin['id'],
                ]);
                
                Response::forbidden('Admin account is not active');
                return null;
            }
            
            return array_merge($admin, ['type' => 'admin']);
        }
        
        // Se não for admin, verificar se é uma empresa
        $company = Company::getByToken($token);
        
        if ($company) {
            if ($company['status'] !== 'active') {
                Logger::warning('Authentication failed - inactive company', [
                    'company_id' => $company['id'],
                ]);
                
                Response::forbidden('Company is not active');
                return null;
            }
            
            return array_merge($company, [
                'is_superadmin' => false,
                'type' => 'company',
            ]);
        }
        
        // Se não for empresa, verificar se é token de instância
        $instance = Instance::getByToken($token);
        
        if ($instance) {
            if ($instance['status'] === 'deleted') {
                Logger::warning('Authentication failed - deleted instance', [
                    'instance_id' => $instance['id'],
                ]);
                
                Response::forbidden('Instance is deleted');
                return null;
            }
            
            return array_merge($instance, [
                'is_superadmin' => false,
                'type' => 'instance',
                'authenticated_instance_id' => $instance['id'],
            ]);
        }
        
        // Token inválido
        Logger::warning('Authentication failed - invalid token', [
            'token' => substr($token, 0, 8) . '...',
        ]);
        
        Response::unauthorized('Invalid token');
        return null;
    }
    
    public static function requireSuperadmin(): bool
    {
        $user = self::authenticate();
        
        if (!$user) {
            return false;
        }
        
        if (!isset($user['is_superadmin']) || !$user['is_superadmin']) {
            Response::forbidden('Superadmin access required');
            return false;
        }
        
        return true;
    }
}

