<?php

/**
 * Configuração de Providers (WAHA/UAZAPI)
 * 
 * Este arquivo serve como exemplo. Providers devem ser adicionados
 * via API Admin ou diretamente no banco de dados.
 */

return [
    // Exemplo de configuração WAHA
    'waha_example' => [
        'type' => 'waha',
        'name' => 'WAHA Server 1',
        'base_url' => 'http://localhost:3000',
        'api_key' => null, // Opcional
        'max_instances' => 100,
        'is_active' => true,
    ],
    
    // Exemplo de configuração UAZAPI
    'uazapi_example' => [
        'type' => 'uazapi',
        'name' => 'UAZAPI Server 1',
        'base_url' => 'https://api.uazapi.com',
        'api_key' => 'your-secret-key-here',
        'max_instances' => 100,
        'is_active' => true,
    ],
];

