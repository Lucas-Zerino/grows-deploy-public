#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Provider;
use App\Utils\Logger;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Seeding default providers...\n\n";

try {
    // Criar provider WAHA padrão
    $wahaUrl = $_ENV['WAHA_API_URL'] ?? 'http://localhost:3000';
    $wahaKey = $_ENV['WAHA_API_KEY'] ?? null;
    
    // Verificar se já existe provider WAHA com essa URL
    $existingProviders = Provider::findAll();
    
    $wahaExists = false;
    foreach ($existingProviders as $provider) {
        if ($provider['type'] === 'waha' && $provider['base_url'] === $wahaUrl) {
            $wahaExists = true;
            echo "✓ Provider WAHA já existe: {$provider['name']} (ID: {$provider['id']})\n";
            break;
        }
    }
    
    if (!$wahaExists) {
        $provider = Provider::create([
            'type' => 'waha',
            'name' => 'WAHA Server - Default',
            'base_url' => $wahaUrl,
            'api_key' => $wahaKey,
            'max_instances' => 50,
            'is_active' => true,
        ]);
        
        echo "✓ Provider WAHA criado com sucesso!\n";
        echo "  ID: {$provider['id']}\n";
        echo "  Nome: {$provider['name']}\n";
        echo "  URL: {$provider['base_url']}\n";
        echo "  API Key: " . ($wahaKey ? 'Configurada' : 'Não configurada') . "\n";
        
        Logger::info('Default WAHA provider seeded', [
            'provider_id' => $provider['id'],
        ]);
    }
    
    echo "\n✅ Seed de providers concluído!\n\n";
    
} catch (\Exception $e) {
    echo "❌ Erro ao criar provider: {$e->getMessage()}\n";
    Logger::error('Failed to seed providers', [
        'error' => $e->getMessage(),
    ]);
    exit(1);
}

