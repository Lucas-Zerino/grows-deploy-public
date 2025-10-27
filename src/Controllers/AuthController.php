<?php

namespace App\Controllers;

use App\Models\Admin;
use App\Utils\Response;
use App\Utils\Router;
use App\Utils\Logger;

class AuthController
{
    public static function login(): void
    {
        $input = Router::getJsonInput();
        
        // Validação
        if (empty($input['email']) || empty($input['password'])) {
            Response::validationError([
                'email' => 'Email é obrigatório',
                'password' => 'Senha é obrigatória',
            ]);
            return;
        }
        
        // Verificar credenciais
        $admin = Admin::verifyPassword($input['email'], $input['password']);
        
        if (!$admin) {
            Logger::warning('Login failed - invalid credentials', [
                'email' => $input['email'],
            ]);
            
            Response::unauthorized('Email ou senha inválidos');
            return;
        }
        
        if ($admin['status'] !== 'active') {
            Logger::warning('Login failed - inactive admin', [
                'admin_id' => $admin['id'],
            ]);
            
            Response::forbidden('Sua conta está inativa');
            return;
        }
        
        Logger::info('Admin logged in', [
            'admin_id' => $admin['id'],
            'email' => $admin['email'],
        ]);
        
        Response::success([
            'admin' => $admin,
            'token' => $admin['token'],
        ], 'Login realizado com sucesso');
    }
    
    public static function me(): void
    {
        $admin = \App\Middleware\AuthMiddleware::authenticate();
        
        if (!$admin || !$admin['is_superadmin']) {
            return; // AuthMiddleware já retornou erro
        }
        
        Response::success($admin);
    }
    
    public static function changePassword(): void
    {
        $admin = \App\Middleware\AuthMiddleware::authenticate();
        
        if (!$admin || !$admin['is_superadmin']) {
            return;
        }
        
        $input = Router::getJsonInput();
        
        // Validação
        $errors = [];
        if (empty($input['current_password'])) {
            $errors['current_password'] = 'Senha atual é obrigatória';
        }
        if (empty($input['new_password'])) {
            $errors['new_password'] = 'Nova senha é obrigatória';
        }
        
        $minLength = (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8);
        if (isset($input['new_password']) && strlen($input['new_password']) < $minLength) {
            $errors['new_password'] = "A senha deve ter no mínimo {$minLength} caracteres";
        }
        
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }
        
        // Verificar senha atual
        $adminFull = Admin::findByEmail($admin['email']);
        $verified = Admin::verifyPassword($admin['email'], $input['current_password']);
        
        if (!$verified) {
            Response::error('Senha atual incorreta', 400);
            return;
        }
        
        // Atualizar senha
        $updated = Admin::updatePassword($admin['id'], $input['new_password']);
        
        if (!$updated) {
            Response::serverError('Erro ao atualizar senha');
            return;
        }
        
        Logger::info('Admin changed password', ['admin_id' => $admin['id']]);
        
        Response::success(null, 'Senha alterada com sucesso');
    }
}

