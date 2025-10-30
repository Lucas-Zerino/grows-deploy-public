<?php

namespace App\Providers\Instagram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class InstagramAuthProvider
{
    private Client $client;
    private string $baseUrl = 'https://api.instagram.com';
    private string $graphUrl = 'https://graph.instagram.com';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'GrowHub-Instagram-Integration/1.0'
            ]
        ]);
    }

    /**
     * Gerar URL de autorização OAuth2
     */
    public function generateAuthUrl(string $appId, string $redirectUri, array $scopes = []): string
    {
        $defaultScopes = [
            'instagram_business_basic',
            'instagram_business_manage_messages',
            'instagram_business_manage_comments',
            'instagram_business_content_publish'
        ];

        $scopes = array_merge($defaultScopes, $scopes);
        $scopeString = implode(',', $scopes);

        $params = [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopeString
        ];

        $url = 'https://www.instagram.com/oauth/authorize?' . http_build_query($params);

        Logger::info('Instagram OAuth URL generated', [
            'app_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes
        ]);

        return $url;
    }

    /**
     * Trocar authorization code por short-lived access token
     */
    public function exchangeCodeForToken(string $code, string $appId, string $appSecret, string $redirectUri): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/oauth/access_token", [
                'form_params' => [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to exchange code for token: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            Logger::info('Instagram code exchanged for token', [
                'app_id' => $appId,
                'user_id' => $data['user_id'] ?? null
            ]);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'user_id' => $data['user_id'],
                'permissions' => $data['permissions'] ?? []
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram token exchange failed', [
                'error' => $e->getMessage(),
                'app_id' => $appId
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao trocar código por token: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram token exchange error', [
                'error' => $e->getMessage(),
                'app_id' => $appId
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Trocar short-lived token por long-lived token (60 dias)
     */
    public function getLongLivedToken(string $shortToken, string $appSecret): array
    {
        try {
            $response = $this->client->get("{$this->graphUrl}/access_token", [
                'query' => [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => $appSecret,
                    'access_token' => $shortToken
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to get long-lived token: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            Logger::info('Instagram long-lived token obtained', [
                'expires_in' => $data['expires_in'] ?? null
            ]);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in']
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram long-lived token failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao obter token de longa duração: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram long-lived token error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Renovar long-lived token (se >= 24h de idade)
     */
    public function refreshToken(string $longToken, string $appSecret): array
    {
        try {
            $response = $this->client->get("{$this->graphUrl}/refresh_access_token", [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $longToken
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to refresh token: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            Logger::info('Instagram token refreshed', [
                'expires_in' => $data['expires_in'] ?? null
            ]);

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in']
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram token refresh failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao renovar token: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram token refresh error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar se token é válido
     */
    public function validateToken(string $accessToken): array
    {
        try {
            $response = $this->client->get("{$this->graphUrl}/me", [
                'query' => [
                    'fields' => 'id,username,account_type',
                    'access_token' => $accessToken
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'Token inválido ou expirado'
                ];
            }

            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'Token inválido: ' . $data['error']['message']
                ];
            }

            Logger::info('Instagram token validated', [
                'user_id' => $data['id'] ?? null,
                'username' => $data['username'] ?? null
            ]);

            return [
                'success' => true,
                'valid' => true,
                'user_id' => $data['id'],
                'username' => $data['username'],
                'account_type' => $data['account_type'] ?? null
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram token validation failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'valid' => false,
                'message' => 'Erro ao validar token: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram token validation error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obter informações do usuário Instagram
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->client->get("{$this->graphUrl}/me", [
                'query' => [
                    'fields' => 'id,username,account_type,media_count,followers_count,follows_count',
                    'access_token' => $accessToken
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to get user info: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            return [
                'success' => true,
                'user_id' => $data['id'],
                'username' => $data['username'],
                'account_type' => $data['account_type'] ?? null,
                'media_count' => $data['media_count'] ?? 0,
                'followers_count' => $data['followers_count'] ?? 0,
                'follows_count' => $data['follows_count'] ?? 0
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram user info failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao obter informações do usuário: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram user info error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
