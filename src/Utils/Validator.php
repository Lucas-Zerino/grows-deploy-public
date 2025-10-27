<?php

namespace App\Utils;

class Validator
{
    /**
     * Validar número de telefone
     */
    public static function isValidPhone(string $phone): bool
    {
        // Remove caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Verifica se tem pelo menos 10 dígitos e no máximo 15
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return false;
        }
        
        // Verifica se começa com código do país
        if (!preg_match('/^[1-9][0-9]{9,14}$/', $phone)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validar email
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar token
     */
    public static function isValidToken(string $token): bool
    {
        // Token deve ter pelo menos 32 caracteres e conter apenas caracteres alfanuméricos
        return strlen($token) >= 32 && preg_match('/^[a-zA-Z0-9]+$/', $token);
    }
    
    /**
     * Validar nome
     */
    public static function isValidName(string $name): bool
    {
        // Nome deve ter pelo menos 2 caracteres e no máximo 100
        $trimmed = trim($name);
        return strlen($trimmed) >= 2 && strlen($trimmed) <= 100;
    }
    
    /**
     * Validar status de presença
     */
    public static function isValidPresenceStatus(string $status): bool
    {
        $validStatuses = ['available', 'composing', 'recording', 'paused'];
        return in_array($status, $validStatuses);
    }
    
    /**
     * Validar configurações de privacidade
     */
    public static function isValidPrivacySettings(array $settings): bool
    {
        $validKeys = [
            'readreceipts',
            'groups',
            'calladd',
            'last',
            'status',
            'profile'
        ];
        
        foreach ($settings as $key => $value) {
            if (!in_array($key, $validKeys)) {
                return false;
            }
            
            if (!in_array($value, ['all', 'contacts', 'contact_blacklist', 'none'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitizar string
     */
    public static function sanitizeString(string $string): string
    {
        return trim(strip_tags($string));
    }
    
    /**
     * Sanitizar número de telefone
     */
    public static function sanitizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
