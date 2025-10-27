#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Company;
use App\Models\Provider;
use App\Services\QueueManagerService;
use App\Utils\Logger;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Creating test data...\n\n";

try {
    // Criar empresa de teste
    echo "Creating test company...\n";
    $companyId = Company::create(['name' => 'Empresa Teste']);
    
    if ($companyId) {
        // Buscar dados da empresa criada
        $company = Company::getById($companyId);
        echo "✓ Company created: {$company['name']}\n";
        echo "  Token: {$company['token']}\n";
        echo "  ID: {$company['id']}\n\n";
        
        // Criar filas da empresa
        echo "Creating company queues...\n";
        QueueManagerService::createCompanyQueues($company['id']);
    } else {
        echo "❌ Failed to create company\n";
        return;
    }
    echo "✓ Queues created\n\n";
    
    // Verificar se existe provider WAHA ativo
    $providers = Provider::findAll(true); // apenas ativos
    $wahaProvider = null;
    foreach ($providers as $p) {
        if ($p['type'] === 'waha') {
            $wahaProvider = $p;
            break;
        }
    }
    
    if ($wahaProvider) {
        echo "✓ Provider WAHA encontrado: {$wahaProvider['name']} (ID: {$wahaProvider['id']})\n";
        echo "  URL: {$wahaProvider['base_url']}\n\n";
    } else {
        echo "⚠️  Nenhum provider WAHA ativo encontrado!\n";
        echo "  Configure WAHA_API_URL e WAHA_API_KEY no .env\n\n";
    }
    
    echo "✅ Test data created successfully!\n\n";
    echo "Use this token to test the API:\n";
    echo "Authorization: Bearer {$company['token']}\n\n";
    echo "Example:\n";
    echo "curl -X POST http://localhost:8000/api/instances \\\n";
    echo "  -H 'Authorization: Bearer {$company['token']}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\"instance_name\": \"test\", \"phone_number\": \"5511999999999\"}'\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    Logger::error('Failed to create test data', ['error' => $e->getMessage()]);
    exit(1);
}

