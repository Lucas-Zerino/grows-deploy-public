<?php

namespace App\Providers;

use App\Providers\Facebook\FacebookWebhookProvider;
use App\Utils\Logger;

class FacebookProvider implements ProviderInterface
{
    private string $appId;
    private string $appSecret;
    private ?string $pageId;
    private ?string $pageAccessToken;

    private FacebookWebhookProvider $webhookProvider;

    public function __construct(string $appId, string $appSecret, ?string $pageId = null, ?string $pageAccessToken = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->pageId = $pageId;
        $this->pageAccessToken = $pageAccessToken;
        $this->webhookProvider = new FacebookWebhookProvider();
    }

    public function createInstance(string $instanceName, ?string $instanceId = null): array
    {
        if (empty($this->appId) || empty($this->appSecret)) {
            return [
                'success' => false,
                'message' => 'Facebook App não configurado. Configure App ID e App Secret.'
            ];
        }

        Logger::info('Facebook instance creation validated', [
            'instance_name' => $instanceName,
            'page_id' => $this->pageId
        ]);

        return [
            'success' => true,
            'message' => 'Facebook provider pronto para receber webhooks'
        ];
    }

    public function connect(string $externalInstanceId, ?string $phone = null): array
    {
        return [
            'success' => true,
            'message' => 'Facebook (somente leitura). Conexão não é necessária.'
        ];
    }

    public function deleteInstance(string $externalInstanceId): array
    {
        return [
            'success' => true,
            'message' => 'Instância removida (no-op no Facebook provider)'
        ];
    }

    public function updateInstanceName(string $externalInstanceId, string $newName): array
    {
        return [
            'success' => false,
            'message' => 'Facebook não permite alterar nome da instância via API'
        ];
    }

    public function getInstanceStatus(string $externalInstanceId): array
    {
        return $this->getStatus($externalInstanceId);
    }

    public function getStatus(string $externalInstanceId): array
    {
        $healthy = $this->healthCheck();
        return [
            'success' => true,
            'status' => $healthy ? 'connected' : 'disconnected',
            'details' => [
                'page_id' => $this->pageId,
                'has_access_token' => !empty($this->pageAccessToken)
            ]
        ];
    }

    public function healthCheck(): bool
    {
        if (empty($this->appId) || empty($this->appSecret)) {
            return false;
        }
        // Como é somente leitura e via webhooks, considerar saudável se token de página (quando exigido) existir
        return true;
    }

    public function processWebhook(array $headers, string $payload): array
    {
        if (!$this->webhookProvider->verifySignature($headers, $payload, $this->appSecret)) {
            return [
                'success' => false,
                'message' => 'Signature inválida'
            ];
        }

        $decodedPayload = json_decode($payload, true);
        if (!$decodedPayload) {
            return [
                'success' => false,
                'message' => 'Payload inválido'
            ];
        }

        return [
            'success' => true,
            'event' => $this->webhookProvider->translateToStandardFormat($decodedPayload)
        ];
    }
}


