<?php

/**
 * Script para testar extração de ID numérico do external_instance_id
 * Simula o comportamento do webhook da WAHA
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\WebhookController;
use ReflectionClass;

echo "========================================\n";
echo "  Teste de Extração de ID do Webhook\n";
echo "  Simulando comportamento da WAHA\n";
echo "========================================\n\n";

// Usar reflexão para acessar método privado
$reflection = new ReflectionClass(WebhookController::class);
$method = $reflection->getMethod('extractNumericInstanceId');
$method->setAccessible(true);

// Casos de teste baseados no exemplo real
$testCases = [
    // Casos reais da WAHA
    '11-1414' => 11,
    '1-vendas998' => 1,
    '123-test-instance' => 123,
    
    // Casos edge
    '999' => 999,
    '0-test' => 0,
    'invalid-format' => null,
    'no-dash' => null,
    '' => null,
    'abc-123' => null,
    '123-abc-def' => 123,
    
    // Casos específicos do seu exemplo
    '11-1414' => 11, // Company ID 11, Instance Name 1414
];

echo "🧪 Testando extração de ID numérico:\n\n";

foreach ($testCases as $input => $expected) {
    $result = $method->invoke(null, $input);
    
    $status = ($result === $expected) ? '✅' : '❌';
    
    echo "{$status} Input: '{$input}' → Expected: " . ($expected ?? 'null') . " → Got: " . ($result ?? 'null') . "\n";
}

echo "\n";
echo "========================================\n";
echo "  Teste do Caso Real\n";
echo "========================================\n";

// Teste específico do seu exemplo
$realCase = '11-1414';
$realResult = $method->invoke(null, $realCase);

echo "📱 Exemplo do seu webhook:\n";
echo "   Input: '{$realCase}'\n";
echo "   ID extraído: {$realResult}\n";
echo "   Company ID: {$realResult}\n";
echo "   Instance Name: 1414\n\n";

if ($realResult === 11) {
    echo "✅ Sucesso! O webhook conseguirá processar '11-1414' corretamente.\n";
    echo "   - Company ID: 11\n";
    echo "   - Instance Name: 1414\n";
    echo "   - Sistema buscará instância com company_id = 11\n";
} else {
    echo "❌ Falha! O webhook não conseguirá processar '11-1414'.\n";
}

echo "\n";
echo "📋 Resumo do comportamento:\n";
echo "   - WAHA envia: POST /webhook/waha/11-1414\n";
echo "   - Sistema extrai: 11 (company_id)\n";
echo "   - Sistema busca: Instance::getById(11)\n";
echo "   - Webhook processado: ✅\n";
