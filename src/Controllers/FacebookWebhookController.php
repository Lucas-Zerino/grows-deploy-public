<?php

namespace App\Controllers;

use App\Models\FacebookApp;
use App\Models\Instance;
use App\Providers\Facebook\FacebookWebhookProvider;
use App\Services\QueueService;
use App\Utils\Response;
use App\Utils\Logger;

class FacebookWebhookController
{
    /**
     * Receber webhook do Facebook
     * POST /webhook/facebook/{companyId}
     */
    public static function handleWebhook(string $companyId): void
    {
        try {
            $payload = file_get_contents('php://input');
            $headers = getallheaders();

            Logger::info('Facebook webhook received', [
                'company_id' => $companyId,
                'headers' => $headers,
                'payload_length' => strlen($payload),
                'context' => 'webhook_inbound'
            ]);

            $app = FacebookApp::getByCompanyId((int)$companyId);
            if (!$app) {
                Logger::warning('Facebook webhook received for company without app', [
                    'company_id' => $companyId
                ]);
                Response::notFound('Facebook App não encontrado');
                return;
            }

            $webhookProvider = new FacebookWebhookProvider();

            if (!$webhookProvider->verifySignature($headers, $payload, $app['app_secret'])) {
                Logger::warning('Facebook webhook signature verification failed', [
                    'company_id' => $companyId
                ]);
                Response::unauthorized('Signature inválida');
                return;
            }

            $decodedPayload = json_decode($payload, true);
            if (!$decodedPayload) {
                Logger::warning('Facebook webhook invalid JSON payload', [
                    'company_id' => $companyId,
                    'payload' => $payload
                ]);
                Response::badRequest('Payload inválido');
                return;
            }

            if (!$webhookProvider->validateWebhook($decodedPayload)) {
                Logger::warning('Facebook webhook validation failed', [
                    'company_id' => $companyId,
                    'payload' => $decodedPayload
                ]);
                Response::badRequest('Webhook inválido');
                return;
            }

            $standardEvent = $webhookProvider->translateToStandardFormat($decodedPayload);

            if (isset($standardEvent['events']) && is_array($standardEvent['events'])) {
                foreach ($standardEvent['events'] as $event) {
                    self::processFacebookEvent($event, $companyId, $app);
                }
            } else {
                self::processFacebookEvent($standardEvent, $companyId, $app);
            }

            Logger::info('Facebook webhook processed successfully', [
                'company_id' => $companyId,
                'events_count' => $webhookProvider->countEvents($decodedPayload)
            ]);

            Response::success(['received' => true]);

        } catch (\Exception $e) {
            Logger::error('Facebook webhook processing failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Response::serverError('Erro ao processar webhook');
        }
    }

    /**
     * Verificar webhook do Facebook (challenge)
     * GET /webhook/facebook/{companyId}
     */
    public static function verifyWebhook(string $companyId): void
    {
        try {
            $hubMode = $_GET['hub_mode'] ?? '';
            $hubChallenge = $_GET['hub_challenge'] ?? '';
            $hubVerifyToken = $_GET['hub_verify_token'] ?? '';

            Logger::info('Facebook webhook verification request', [
                'company_id' => $companyId,
                'hub_mode' => $hubMode,
                'hub_verify_token' => $hubVerifyToken
            ]);

            if ($hubMode !== 'subscribe') {
                Logger::warning('Facebook webhook verification invalid mode', [
                    'company_id' => $companyId,
                    'hub_mode' => $hubMode
                ]);
                Response::badRequest('Modo inválido');
                return;
            }

            $app = FacebookApp::getByCompanyId((int)$companyId);
            if (!$app) {
                Response::notFound('Facebook App não encontrado');
                return;
            }

            $expectedToken = $app['webhook_verify_token'] ?? '';
            if ($hubVerifyToken !== $expectedToken) {
                Logger::warning('Facebook webhook verification token mismatch', [
                    'company_id' => $companyId
                ]);
                Response::unauthorized('Token inválido');
                return;
            }

            Logger::info('Facebook webhook verification successful', [
                'company_id' => $companyId
            ]);

            echo $hubChallenge;

        } catch (\Exception $e) {
            Logger::error('Facebook webhook verification failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Response::serverError('Erro na verificação do webhook');
        }
    }

    private static function processFacebookEvent(array $event, string $companyId, array $app): void
    {
        try {
            $webhookProvider = new FacebookWebhookProvider();
            $userInfo = $webhookProvider->extractUserInfo($event);
            $messageContent = $webhookProvider->extractMessageContent($event);
            $eventType = $webhookProvider->mapEventType($event['event'] ?? 'unknown');

            $instance = null;
            if ($userInfo['page_id'] ?? null) {
                $instance = Instance::getByFacebookPageId($userInfo['page_id']);
            }

            if (!$instance) {
                Logger::warning('Facebook event for unknown instance', [
                    'company_id' => $companyId,
                    'page_id' => $userInfo['page_id'] ?? null,
                    'event_type' => $eventType
                ]);
                return;
            }

            $translatedEvent = [
                'event' => $eventType,
                'timestamp' => $event['timestamp'] ?? time() * 1000,
                'instance_id' => $instance['external_instance_id'],
                'company_id' => $companyId,
                'user_psid' => $userInfo['user_id'] ?? null,
                'page_id' => $userInfo['page_id'] ?? null,
                'message_id' => $userInfo['message_id'] ?? null,
                'content' => $messageContent,
                'source' => 'facebook',
                'payload' => $event
            ];

            $routingKey = "company.{$companyId}";
            QueueService::publishToExchange(
                'messaging.inbound.exchange',
                $translatedEvent,
                $routingKey
            );

        } catch (\Exception $e) {
            Logger::error('Facebook event processing failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}


