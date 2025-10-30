<?php

namespace App\Providers\Facebook;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class FacebookConversationsProvider
{
    private Client $client;
    private string $baseUrl = 'https://graph.facebook.com/v19.0';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'GrowHub-Facebook-Integration/1.0'
            ]
        ]);
    }

    public function listConversations(string $pageId, string $accessToken, array $params = []): array
    {
        try {
            $query = array_merge([
                'platform' => 'messenger',
                'access_token' => $accessToken
            ], $params);

            $response = $this->client->get("{$this->baseUrl}/{$pageId}/conversations", [
                'query' => $query
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200 || isset($data['error'])) {
                throw new \Exception('Facebook API error on listConversations: ' . json_encode($data));
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('HTTP error on listConversations', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Logger::error('listConversations failed', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listMessages(string $conversationId, string $accessToken, array $params = []): array
    {
        try {
            $query = array_merge([
                'fields' => 'messages',
                'access_token' => $accessToken
            ], $params);

            $response = $this->client->get("{$this->baseUrl}/{$conversationId}", [
                'query' => $query
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200 || isset($data['error'])) {
                throw new \Exception('Facebook API error on listMessages: ' . json_encode($data));
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('HTTP error on listMessages', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Logger::error('listMessages failed', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getMessage(string $messageId, string $accessToken, array $fields = ['id','created_time','from','to','message','reply_to']): array
    {
        try {
            $query = [
                'fields' => implode(',', $fields),
                'access_token' => $accessToken
            ];

            $response = $this->client->get("{$this->baseUrl}/{$messageId}", [
                'query' => $query
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200 || isset($data['error'])) {
                throw new \Exception('Facebook API error on getMessage: ' . json_encode($data));
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('HTTP error on getMessage', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Logger::error('getMessage failed', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}


