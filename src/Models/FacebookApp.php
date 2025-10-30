<?php

namespace App\Models;

use App\Utils\Database;
use App\Utils\Logger;

class FacebookApp
{
    private static $encryptionKey;
    private static $encryptionMethod = 'AES-256-CBC';

    private static function getEncryptionKey(): string
    {
        if (!self::$encryptionKey) {
            self::$encryptionKey = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-in-production';
        }
        return self::$encryptionKey;
    }

    private static function encrypt(string $data): string
    {
        $key = self::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, self::$encryptionMethod, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private static function decrypt(?string $encryptedData): ?string
    {
        if ($encryptedData === null) {
            return null;
        }
        $key = self::getEncryptionKey();
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, self::$encryptionMethod, $key, 0, $iv);
    }

    public static function create(array $data): ?int
    {
        try {
            $db = Database::getInstance();

            $existing = self::getByCompanyId($data['company_id']);
            if ($existing) {
                throw new \Exception('Facebook App já configurado para esta empresa');
            }

            $stmt = $db->prepare("\n                INSERT INTO facebook_apps (company_id, app_id, app_secret, page_id, page_access_token, webhook_verify_token, status) \n                VALUES (?, ?, ?, ?, ?, ?, ?)\n            ");

            $result = $stmt->execute([
                $data['company_id'],
                $data['app_id'],
                self::encrypt($data['app_secret']),
                $data['page_id'] ?? null,
                isset($data['page_access_token']) ? self::encrypt($data['page_access_token']) : null,
                $data['webhook_verify_token'] ?? null,
                $data['status'] ?? 'pending'
            ]);

            if ($result) {
                $newId = $db->lastInsertId();
                Logger::info('Facebook App created', [
                    'facebook_app_id' => $newId,
                    'company_id' => $data['company_id']
                ]);
                return (int)$newId;
            }

            return null;

        } catch (\Exception $e) {
            Logger::error('Database error creating Facebook App', [
                'error' => $e->getMessage(),
                'company_id' => $data['company_id'] ?? null,
                'app_id' => $data['app_id'] ?? null
            ]);
            return null;
        }
    }

    public static function getByCompanyId(int $companyId): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                SELECT id, company_id, app_id, app_secret, page_id, page_access_token, webhook_verify_token, status, created_at, updated_at\n                FROM facebook_apps\n                WHERE company_id = ?\n            ");
            $stmt->execute([$companyId]);

            $app = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($app) {
                $app['app_secret'] = self::decrypt($app['app_secret']);
                $app['page_access_token'] = self::decrypt($app['page_access_token']);
                return $app;
            }

            return null;

        } catch (\Exception $e) {
            Logger::error('Database error getting Facebook App by company', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);
            return null;
        }
    }

    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                SELECT id, company_id, app_id, app_secret, page_id, page_access_token, webhook_verify_token, status, created_at, updated_at\n                FROM facebook_apps\n                WHERE id = ?\n            ");
            $stmt->execute([$id]);

            $app = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($app) {
                $app['app_secret'] = self::decrypt($app['app_secret']);
                $app['page_access_token'] = self::decrypt($app['page_access_token']);
                return $app;
            }

            return null;

        } catch (\Exception $e) {
            Logger::error('Database error getting Facebook App by ID', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return null;
        }
    }

    public static function updateCredentials(int $id, string $appId, string $appSecret): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                UPDATE facebook_apps\n                SET app_id = ?, app_secret = ?, status = 'pending', updated_at = NOW()\n                WHERE id = ?\n            ");

            $result = $stmt->execute([
                $appId,
                self::encrypt($appSecret),
                $id
            ]);

            if ($result) {
                Logger::info('Facebook App credentials updated', [
                    'app_id' => $id,
                    'facebook_app_id' => $appId
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Facebook App credentials', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    public static function updatePageAccess(int $id, ?string $pageId, ?string $pageAccessToken): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                UPDATE facebook_apps\n                SET page_id = ?, page_access_token = ?, updated_at = NOW(), status = CASE WHEN status = 'pending' THEN 'active' ELSE status END\n                WHERE id = ?\n            ");

            $result = $stmt->execute([
                $pageId,
                $pageAccessToken !== null ? self::encrypt($pageAccessToken) : null,
                $id
            ]);

            if ($result) {
                Logger::info('Facebook App page access updated', [
                    'app_id' => $id,
                    'page_id' => $pageId
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Facebook App page access', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    public static function updateVerifyToken(int $id, string $verifyToken): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                UPDATE facebook_apps\n                SET webhook_verify_token = ?, updated_at = NOW()\n                WHERE id = ?\n            ");
            $result = $stmt->execute([$verifyToken, $id]);

            if ($result) {
                Logger::info('Facebook App verify token updated', [
                    'app_id' => $id
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Facebook App verify token', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    public static function updateStatus(int $id, string $status): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                UPDATE facebook_apps\n                SET status = ?, updated_at = NOW()\n                WHERE id = ?\n            ");
            $result = $stmt->execute([$status, $id]);

            if ($result) {
                Logger::info('Facebook App status updated', [
                    'app_id' => $id,
                    'status' => $status
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error updating Facebook App status', [
                'error' => $e->getMessage(),
                'id' => $id,
                'status' => $status
            ]);
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM facebook_apps WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                Logger::info('Facebook App deleted', ['app_id' => $id]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Database error deleting Facebook App', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    public static function listAll(): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("\n                SELECT fa.id, fa.company_id, fa.app_id, fa.page_id, fa.status, fa.created_at, fa.updated_at, c.name as company_name\n                FROM facebook_apps fa\n                JOIN companies c ON fa.company_id = c.id\n                ORDER BY fa.created_at DESC\n            ");
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            Logger::error('Database error listing Facebook Apps', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}


