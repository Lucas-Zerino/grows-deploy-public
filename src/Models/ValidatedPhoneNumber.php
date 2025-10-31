<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class ValidatedPhoneNumber
{
    /**
     * Buscar número validado no cache
     * @param int $instanceId ID da instância
     * @param string $originalNumber Número original fornecido
     * @return array|null ['validated_number' => string, 'is_valid' => bool] ou null se não encontrado
     */
    public static function get(int $instanceId, string $originalNumber): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT validated_number, is_valid, last_validated_at
                FROM validated_phone_numbers
                WHERE instance_id = ? AND original_number = ?
                LIMIT 1
            ");
            
            $stmt->execute([$instanceId, $originalNumber]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'validated_number' => $result['validated_number'],
                    'is_valid' => (bool) $result['is_valid'],
                    'last_validated_at' => $result['last_validated_at']
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Logger::error('Error getting validated phone number', [
                'instance_id' => $instanceId,
                'original_number' => $originalNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Salvar número validado no cache
     * @param int $instanceId ID da instância
     * @param string $originalNumber Número original fornecido
     * @param string $validatedNumber Número correto validado
     * @param bool $isValid Se o número é válido no WhatsApp
     * @return bool Sucesso da operação
     */
    public static function save(int $instanceId, string $originalNumber, string $validatedNumber, bool $isValid = true): bool
    {
        try {
            $db = Database::getInstance();
            
            // Usar ON CONFLICT para atualizar se já existir
            $stmt = $db->prepare("
                INSERT INTO validated_phone_numbers (instance_id, original_number, validated_number, is_valid, last_validated_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())
                ON CONFLICT (instance_id, original_number)
                DO UPDATE SET
                    validated_number = EXCLUDED.validated_number,
                    is_valid = EXCLUDED.is_valid,
                    last_validated_at = NOW(),
                    updated_at = NOW()
            ");
            
            $result = $stmt->execute([
                $instanceId,
                $originalNumber,
                $validatedNumber,
                $isValid ? 'true' : 'false'
            ]);
            
            if ($result) {
                Logger::info('Validated phone number saved', [
                    'instance_id' => $instanceId,
                    'original_number' => $originalNumber,
                    'validated_number' => $validatedNumber,
                    'is_valid' => $isValid
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Logger::error('Error saving validated phone number', [
                'instance_id' => $instanceId,
                'original_number' => $originalNumber,
                'validated_number' => $validatedNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Limpar cache de números validados para uma instância
     * @param int $instanceId ID da instância
     * @return bool Sucesso da operação
     */
    public static function clearCache(int $instanceId): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM validated_phone_numbers WHERE instance_id = ?");
            $result = $stmt->execute([$instanceId]);
            
            Logger::info('Validated phone numbers cache cleared', [
                'instance_id' => $instanceId,
                'deleted_count' => $stmt->rowCount()
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Logger::error('Error clearing validated phone numbers cache', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

