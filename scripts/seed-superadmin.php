#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Admin;
use App\Utils\Logger;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "===========================================\n";
echo "  GrowHub Gateway - Criar Superadmin\n";
echo "===========================================\n\n";

try {
    // Verificar se já existe um superadmin
    $existingSuperadmin = Admin::findByEmail($_ENV['SUPERADMIN_EMAIL']);
    
    if ($existingSuperadmin) {
        echo "⚠️  Superadmin já existe!\n";
        echo "   Email: {$existingSuperadmin['email']}\n";
        echo "   Nome: {$existingSuperadmin['name']}\n";
        echo "   Token: {$existingSuperadmin['token']}\n\n";
        echo "Para criar um novo superadmin, delete o existente primeiro ou use outro email.\n";
        exit(0);
    }
    
    // Validar credenciais do .env
    $name = $_ENV['SUPERADMIN_NAME'] ?? null;
    $email = $_ENV['SUPERADMIN_EMAIL'] ?? null;
    $password = $_ENV['SUPERADMIN_PASSWORD'] ?? null;
    
    if (!$name || !$email || !$password) {
        echo "❌ Erro: Credenciais do superadmin não configuradas no .env\n\n";
        echo "Adicione as seguintes variáveis ao seu arquivo .env:\n";
        echo "  SUPERADMIN_NAME=Admin\n";
        echo "  SUPERADMIN_EMAIL=admin@growhub.com\n";
        echo "  SUPERADMIN_PASSWORD=Admin@123456\n";
        exit(1);
    }
    
    // Validar senha
    $minLength = (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8);
    if (strlen($password) < $minLength) {
        echo "❌ Erro: A senha deve ter no mínimo {$minLength} caracteres\n";
        exit(1);
    }
    
    // Criar superadmin
    echo "Criando superadmin...\n";
    
    $superadmin = Admin::create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'is_superadmin' => true,
        'status' => 'active',
    ]);
    
    echo "\n✅ Superadmin criado com sucesso!\n\n";
    echo "═══════════════════════════════════════════\n";
    echo "  CREDENCIAIS DO SUPERADMIN\n";
    echo "═══════════════════════════════════════════\n";
    echo "  ID:     {$superadmin['id']}\n";
    echo "  Nome:   {$superadmin['name']}\n";
    echo "  Email:  {$superadmin['email']}\n";
    echo "  Token:  {$superadmin['token']}\n";
    echo "═══════════════════════════════════════════\n\n";
    
    echo "🔐 Para fazer login via API:\n\n";
    echo "  Opção 1 - Via Token (direto):\n";
    echo "    Authorization: Bearer {$superadmin['token']}\n\n";
    
    echo "  Opção 2 - Via Email/Senha (endpoint de login):\n";
    echo "    POST /api/admin/login\n";
    echo "    {\n";
    echo "      \"email\": \"{$email}\",\n";
    echo "      \"password\": \"sua-senha\"\n";
    echo "    }\n\n";
    
    Logger::info('Superadmin created', [
        'admin_id' => $superadmin['id'],
        'email' => $superadmin['email'],
    ]);
    
} catch (\Exception $e) {
    echo "❌ Erro ao criar superadmin: {$e->getMessage()}\n";
    Logger::error('Failed to create superadmin', ['error' => $e->getMessage()]);
    exit(1);
}

