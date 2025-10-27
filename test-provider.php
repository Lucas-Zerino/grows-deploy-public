<?php

require_once 'vendor/autoload.php';

try {
    $provider = App\Providers\ProviderManager::getProvider(2);
    echo "ProviderManager funcionando!\n";
    echo "Provider class: " . get_class($provider) . "\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
