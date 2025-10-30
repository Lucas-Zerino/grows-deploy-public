<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class InstagramApp
{
    private static $encryptionKey;
    private static $encryptionMethod = 'AES-256-CBC';

    /**
     * Obter chave de criptografia
     */
    private static function getEncryptionKey(): string
    {
        if (!self::$encryptionKey) {
            self::$encryptionKey = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-in-production';
        }
        return self::$encryptionKey;
    }

    /**
     * Criptografar dados sensíveis
     */
    private static function encrypt(string $data): string
    {
        $key = self::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, self::$encryptionMethod, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Descriptografar dados sensíveis
     */
    private static function decrypt(string $encryptedData): string
    {
        $key = self::getEncryptionKey();
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, self::$encryptionMethod, $key, 0, $iv);
    }

    /**
     * Criar novo Instagram App
     */
    public static function create(array $data): ?int
    {
        try {
            $db = Database::getInstance();
            
            // Verificar se já existe app para esta company
            $existing = self::getByCompanyId($data['company_id']);
            if ($existing) {
                throw new \Exception('Instagram App já configurado para esta empresa');
            }

            $stmt = $db->prepare("
                INSERT INTO instagram_apps (company_id, app_id, app_secret, status) 
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['company_id'],
                $data['app_id'],
                self::encrypt($data['app_secret']), // Criptografar app_secret
                $data['status'] ?? 'pending'
            ]);

            if ($result) {
                $appId = $db->lastInsertId();
                Logger::info('Instagram App created', [
                    'app_id' => $appId,
                    'company_id' => $data['company_id'],
                    'instagram_app_id' => $data['app_id']
                ]);
                return $appId;
            }

            return null;

        } catch (\Exception $e) {
            Logger::error('Database error creating Instagram App', [
                'error' => $e->getMessage(),
                'company_id' => $data['company_id'] ?? null,
                'app_id' => $data['app_id'] ?? null
            ]);
            return null;
        }
    }

    /**
     * Buscar Instagram App por company_id
     */
    public static function getByCompanyId(int $companyId): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, company_id, app_id, app_secret, access_token, 
                       token_expires_at, status, created_at, updated_at
                FROM instagram_apps 
                WHERE company_id = ?
            ");
            $stmt->execute([$companyId]);
            
            $app = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($app) {
                // Descriptografar app_secret
                $app['app_secret'] = self::decrypt($app['app_secret']);
                return $app;
            }
            
            return null;

        } catch (\Exception $e) {
            Logger::error('Database error getting Instagram App by company', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);
            return null;
        }
    }

    /**
     * Buscar Instagram App por ID
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, company_id, app_id, app_secret, access_token, 
                       token_expires_at, status, created_at, updated_at
                FROM instagram_apps 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            $app = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($app) {
                // Descriptografar app_secret
                $app['app_secret'] = self::decrypt($app['app_secret']);
                return $app;
            }
            
            return null;

        } catch (\Exception $e) {
            Logger::error('Database error getting Instagram App by ID', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return null;
        }
    }

    /**
     * Atualizar access token
     */
    public static function updateToken(int $id, string $accessToken, int $expiresIn): bool
    {
        try {
            $db = Database::getInstance();
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
            
            $stmt = $db->prepare("
                UPDATE instagram_apps 
                SET access_token = ?, token_expires_at = ?, status = 'active', updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$accessToken, $expiresAt, $id]);
            
            if ($result) {
                Logger::info('Instagram App token updated', [
                    'app_id' => $id,
                    'expires_at' => $expiresAt
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Instagram App token', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Atualizar status do app
     */
    public static function updateStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instagram_apps 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $id]);
            
            if ($result) {
                Logger::info('Instagram App status updated', [
                    'app_id' => $id,
                    'status' => $status
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Instagram App status', [
                'error' => $e->getMessage(),
                'id' => $id,
                'status' => $status
            ]);
            return false;
        }
    }

    /**
     * Atualizar credenciais do app
     */
    public static function updateCredentials(int $id, string $appId, string $appSecret): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE instagram_apps 
                SET app_id = ?, app_secret = ?, status = 'pending', updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $appId, 
                self::encrypt($appSecret), // Criptografar app_secret
                $id
            ]);
            
            if ($result) {
                Logger::info('Instagram App credentials updated', [
                    'app_id' => $id,
                    'instagram_app_id' => $appId
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Instagram App credentials', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Deletar Instagram App
     */
    public static function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM instagram_apps WHERE id = ?");
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                Logger::info('Instagram App deleted', ['app_id' => $id]);
            }
            
            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error deleting Instagram App', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Verificar se token está expirado
     */
    public static function isTokenExpired(array $app): bool
    {
        if (!$app['token_expires_at']) {
            return true;
        }
        
        $expiresAt = strtotime($app['token_expires_at']);
        $now = time();
        
        // Considerar expirado se faltam menos de 1 hora
        return ($expiresAt - $now) < 3600;
    }

    /**
     * Listar todos os apps (admin)
     */
    public static function listAll(): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT ia.id, ia.company_id, ia.app_id, ia.access_token, 
                       ia.token_expires_at, ia.status, ia.created_at, ia.updated_at,
                       c.name as company_name
                FROM instagram_apps ia
                JOIN companies c ON ia.company_id = c.id
                ORDER BY ia.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            Logger::error('Database error listing Instagram Apps', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
