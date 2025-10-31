<?php

namespace App\Utils;

use App\Models\Instance;
use App\Models\ValidatedPhoneNumber;
use App\Providers\ProviderManager;
use App\Utils\Logger;

class PhoneValidator
{
    /**
     * Validar e normalizar número de telefone brasileiro
     * Tenta com e sem o dígito 9 se necessário
     * 
     * @param int $instanceId ID da instância
     * @param string $phone Número fornecido pelo usuário
     * @return array ['validated_number' => string, 'is_valid' => bool, 'from_cache' => bool]
     */
    public static function validateBrazilianPhone(int $instanceId, string $phone): array
    {
        try {
            // Limpar número (remover caracteres não numéricos)
            $cleanPhone = preg_replace('/\D/', '', $phone);
            
            // Verificar se é número brasileiro (começa com 55)
            if (!self::isBrazilianNumber($cleanPhone)) {
                // Se não for brasileiro, retornar como está
                return [
                    'validated_number' => $cleanPhone,
                    'is_valid' => true, // Assume válido para números internacionais
                    'from_cache' => false
                ];
            }
            
            // 1. Verificar cache primeiro (evita validação desnecessária)
            $cached = ValidatedPhoneNumber::get($instanceId, $cleanPhone);
            if ($cached) {
                Logger::info('Using cached validated phone number (fast path)', [
                    'instance_id' => $instanceId,
                    'original' => $cleanPhone,
                    'validated' => $cached['validated_number'],
                    'is_valid' => $cached['is_valid'],
                    'cached_at' => $cached['last_validated_at'] ?? null
                ]);
                
                return [
                    'validated_number' => $cached['validated_number'],
                    'is_valid' => $cached['is_valid'],
                    'from_cache' => true
                ];
            }
            
            Logger::info('Phone number not in cache, validating...', [
                'instance_id' => $instanceId,
                'original' => $cleanPhone
            ]);
            
            // 2. Buscar instância e provider
            $instance = Instance::getById($instanceId);
            if (!$instance) {
                Logger::warning('Instance not found for phone validation', [
                    'instance_id' => $instanceId
                ]);
                return [
                    'validated_number' => $cleanPhone,
                    'is_valid' => true, // Assume válido se não conseguir validar
                    'from_cache' => false
                ];
            }
            
            $provider = ProviderManager::getProvider($instance['provider_id']);
            if (!$provider || !method_exists($provider, 'checkNumberStatus')) {
                Logger::warning('Provider not found or does not support validation', [
                    'instance_id' => $instanceId,
                    'provider_id' => $instance['provider_id']
                ]);
                return [
                    'validated_number' => $cleanPhone,
                    'is_valid' => true,
                    'from_cache' => false
                ];
            }
            
            // 3. Tentar validar número original primeiro
            /** @var \App\Providers\WahaProvider $provider */
            $result = $provider->checkNumberStatus($instance['external_instance_id'], $cleanPhone);
            
            // Se houve erro de conexão/timeout, não tentar alternativo e retornar como inválido
            if (!$result['success']) {
                Logger::warning('Phone validation failed (connection/timeout error)', [
                    'instance_id' => $instanceId,
                    'phone' => $cleanPhone,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                // Não salvar no cache se foi erro de conexão (pode ser temporário)
                return [
                    'validated_number' => $cleanPhone,
                    'is_valid' => false,
                    'from_cache' => false,
                    'error' => 'Erro ao validar número. Tente novamente mais tarde.',
                    'error_code' => 'VALIDATION_ERROR'
                ];
            }
            
            if ($result['exists']) {
                // Se a WAHA retornou um chatId, extrair o número do chatId (é o número correto)
                $validatedNumber = $cleanPhone;
                if (!empty($result['chatId'])) {
                    // Extrair número do chatId (formato: 558498537596@c.us)
                    $chatIdNumber = preg_replace('/@.*/', '', $result['chatId']);
                    if ($chatIdNumber && $chatIdNumber !== $cleanPhone) {
                        $validatedNumber = $chatIdNumber;
                        Logger::info('Using chatId number instead of original (WAHA normalized)', [
                            'instance_id' => $instanceId,
                            'original' => $cleanPhone,
                            'chatId_number' => $chatIdNumber
                        ]);
                    }
                }
                
                // Salvar no cache
                ValidatedPhoneNumber::save($instanceId, $cleanPhone, $validatedNumber, true);
                return [
                    'validated_number' => $validatedNumber,
                    'is_valid' => true,
                    'from_cache' => false
                ];
            }
            
            // 4. Se número original não existe, tentar com/sem o dígito 9 (apenas se for número brasileiro)
            // Só tentar alternativo se:
            // - A validação anterior foi bem-sucedida (não foi erro de conexão)
            // - O número não existe (mas a chamada funcionou)
            // - É número brasileiro
            $shouldTryAlternate = false;
            $alternateNumber = null;
            
            if (!$result['exists'] && self::isBrazilianNumber($cleanPhone)) {
                // Se a validação foi bem-sucedida mas número não existe, tentar alternativo
                $alternateNumber = self::toggleDigit9($cleanPhone);
                $shouldTryAlternate = ($alternateNumber && $alternateNumber !== $cleanPhone);
            }
            
            if ($shouldTryAlternate) {
                Logger::info('Trying alternate phone number (with/without digit 9)', [
                    'instance_id' => $instanceId,
                    'original' => $cleanPhone,
                    'alternate' => $alternateNumber
                ]);
                
                /** @var \App\Providers\WahaProvider $provider */
                $alternateResult = $provider->checkNumberStatus($instance['external_instance_id'], $alternateNumber);
                
                if ($alternateResult['success'] && $alternateResult['exists']) {
                    // Se a WAHA retornou um chatId, extrair o número do chatId (é o número correto)
                    $validatedNumber = $alternateNumber;
                    if (!empty($alternateResult['chatId'])) {
                        // Extrair número do chatId (formato: 558498537596@c.us)
                        $chatIdNumber = preg_replace('/@.*/', '', $alternateResult['chatId']);
                        if ($chatIdNumber && $chatIdNumber !== $alternateNumber) {
                            $validatedNumber = $chatIdNumber;
                            Logger::info('Using chatId number from alternate validation (WAHA normalized)', [
                                'instance_id' => $instanceId,
                                'alternate' => $alternateNumber,
                                'chatId_number' => $chatIdNumber
                            ]);
                        }
                    }
                    
                    // Número alternativo é válido - salvar ambos no cache
                    ValidatedPhoneNumber::save($instanceId, $cleanPhone, $validatedNumber, true);
                    ValidatedPhoneNumber::save($instanceId, $alternateNumber, $validatedNumber, true);
                    
                    Logger::info('Alternate phone number is valid', [
                        'instance_id' => $instanceId,
                        'original' => $cleanPhone,
                        'alternate_tried' => $alternateNumber,
                        'validated' => $validatedNumber
                    ]);
                    
                    return [
                        'validated_number' => $validatedNumber,
                        'is_valid' => true,
                        'from_cache' => false
                    ];
                }
            }
            
            // 5. Se nenhum número funcionou, marcar como inválido
            ValidatedPhoneNumber::save($instanceId, $cleanPhone, $cleanPhone, false);
            
            Logger::warning('Phone number validation failed - number does not exist on WhatsApp', [
                'instance_id' => $instanceId,
                'phone' => $cleanPhone,
                'alternate_tried' => $alternateNumber ?? null,
                'original_exists' => false,
                'alternate_exists' => isset($alternateResult) ? ($alternateResult['exists'] ?? false) : false
            ]);
            
            return [
                'validated_number' => $cleanPhone,
                'is_valid' => false,
                'from_cache' => false,
                'error' => 'Número não existe no WhatsApp',
                'error_code' => 'PHONE_NOT_FOUND'
            ];
            
        } catch (\Exception $e) {
            Logger::error('Error validating phone number', [
                'instance_id' => $instanceId,
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Em caso de erro, retornar como inválido para não enviar mensagem
            return [
                'validated_number' => preg_replace('/\D/', '', $phone),
                'is_valid' => false,
                'from_cache' => false,
                'error' => 'Erro ao validar número: ' . $e->getMessage(),
                'error_code' => 'VALIDATION_EXCEPTION'
            ];
        }
    }
    
    /**
     * Verificar se é número brasileiro (começa com 55)
     */
    public static function isBrazilianNumber(string $phone): bool
    {
        return strpos($phone, '55') === 0 && strlen($phone) >= 12; // Mínimo: 55 + DDD (2) + número (8+)
    }
    
    /**
     * Alternar dígito 9 em número brasileiro
     * Se tem 9 após DDD, remove. Se não tem, adiciona.
     * Formato esperado: 55DDD9XXXXXXXX ou 55DDDXXXXXXXX
     * 
     * @param string $phone Número limpo (apenas dígitos)
     * @return string|null Número alternativo ou null se não aplicável
     */
    private static function toggleDigit9(string $phone): ?string
    {
        // Número brasileiro: 55 + DDD (2 dígitos) + 9? + número (8 dígitos)
        // Exemplo: 558498537596 (sem 9) ou 5584998537596 (com 9)
        
        // Remover caracteres não numéricos
        $clean = preg_replace('/\D/', '', $phone);
        
        // Deve começar com 55 e ter pelo menos 12 dígitos (55 + 2 DDD + 8 número)
        if (strpos($clean, '55') !== 0 || strlen($clean) < 12) {
            return null;
        }
        
        // Extrair: 55 + DDD (posições 2-3) + resto
        $countryCode = substr($clean, 0, 2); // 55
        $ddd = substr($clean, 2, 2); // DDD (2 dígitos)
        $rest = substr($clean, 4); // Resto do número
        
        // Se o resto começa com 9 e tem 9 dígitos, remover o 9
        if (strlen($rest) === 9 && $rest[0] === '9') {
            // Formato: 55DDD9XXXXXXXX (13 dígitos) -> 55DDDXXXXXXXX (12 dígitos)
            return $countryCode . $ddd . substr($rest, 1);
        }
        // Se o resto tem 8 dígitos e não começa com 9, adicionar o 9
        elseif (strlen($rest) === 8 && $rest[0] !== '9') {
            // Formato: 55DDDXXXXXXXX (12 dígitos) -> 55DDD9XXXXXXXX (13 dígitos)
            return $countryCode . $ddd . '9' . $rest;
        }
        
        return null;
    }
}

