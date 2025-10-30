<?php

namespace App\Controllers;

use App\Models\InstagramApp;
use App\Models\Instance;
use App\Providers\Instagram\InstagramWebhookProvider;
use App\Services\QueueService;
use App\Utils\Response;
use App\Utils\Logger;

class InstagramWebhookController
{
    /**
     * Receber webhook do Instagram
     * POST /webhook/instagram/{companyId}
     */
    public static function handleWebhook(string $companyId): void
    {
        try {
            $payload = file_get_contents('php://input');
            $headers = getallheaders();
            
            Logger::info('Instagram webhook received', [
                'company_id' => $companyId,
                'headers' => $headers,
                'payload_length' => strlen($payload),
                'context' => 'webhook_inbound'
            ]);
            
            // Buscar Instagram App da company
            $app = InstagramApp::getByCompanyId($companyId);
            if (!$app) {
                Logger::warning('Instagram webhook received for company without app', [
                    'company_id' => $companyId
                ]);
                Response::notFound('Instagram App não encontrado');
                return;
            }
            
            // Processar webhook
            $webhookProvider = new InstagramWebhookProvider();
            
            // Verificar signature
            if (!$webhookProvider->verifySignature($headers, $payload, $app['app_secret'])) {
                Logger::warning('Instagram webhook signature verification failed', [
                    'company_id' => $companyId
                ]);
                Response::unauthorized('Signature inválida');
                return;
            }
            
            // Decodificar payload
            $decodedPayload = json_decode($payload, true);
            if (!$decodedPayload) {
                Logger::warning('Instagram webhook invalid JSON payload', [
                    'company_id' => $companyId,
                    'payload' => $payload
                ]);
                Response::badRequest('Payload inválido');
                return;
            }
            
            // Validar webhook
            if (!$webhookProvider->validateWebhook($decodedPayload)) {
                Logger::warning('Instagram webhook validation failed', [
                    'company_id' => $companyId,
                    'payload' => $decodedPayload
                ]);
                Response::badRequest('Webhook inválido');
                return;
            }
            
            // Traduzir para formato interno
            $standardEvent = $webhookProvider->translateToStandardFormat($decodedPayload);
            
            // Processar eventos individuais
            if (isset($standardEvent['events']) && is_array($standardEvent['events'])) {
                foreach ($standardEvent['events'] as $event) {
                    self::processInstagramEvent($event, $companyId, $app);
                }
            } else {
                // Processar evento único
                self::processInstagramEvent($standardEvent, $companyId, $app);
            }
            
            Logger::info('Instagram webhook processed successfully', [
                'company_id' => $companyId,
                'events_count' => $webhookProvider->countEvents($decodedPayload)
            ]);
            
            Response::success(['received' => true]);

        } catch (\Exception $e) {
            Logger::error('Instagram webhook processing failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Response::serverError('Erro ao processar webhook');
        }
    }

    /**
     * Verificar webhook do Instagram (challenge)
     * GET /webhook/instagram/{companyId}
     */
    public static function verifyWebhook(string $companyId): void
    {
        try {
            $hubMode = $_GET['hub_mode'] ?? '';
            $hubChallenge = $_GET['hub_challenge'] ?? '';
            $hubVerifyToken = $_GET['hub_verify_token'] ?? '';
            
            Logger::info('Instagram webhook verification request', [
                'company_id' => $companyId,
                'hub_mode' => $hubMode,
                'hub_verify_token' => $hubVerifyToken
            ]);
            
            // Verificar se é um request de verificação
            if ($hubMode !== 'subscribe') {
                Logger::warning('Instagram webhook verification invalid mode', [
                    'company_id' => $companyId,
                    'hub_mode' => $hubMode
                ]);
                Response::badRequest('Modo inválido');
                return;
            }
            
            // Verificar token
            $expectedToken = $_ENV['INSTAGRAM_WEBHOOK_VERIFY_TOKEN'] ?? 'default-verify-token';
            if ($hubVerifyToken !== $expectedToken) {
                Logger::warning('Instagram webhook verification token mismatch', [
                    'company_id' => $companyId,
                    'expected' => $expectedToken,
                    'received' => $hubVerifyToken
                ]);
                Response::unauthorized('Token inválido');
                return;
            }
            
            Logger::info('Instagram webhook verification successful', [
                'company_id' => $companyId
            ]);
            
            // Retornar challenge
            echo $hubChallenge;

        } catch (\Exception $e) {
            Logger::error('Instagram webhook verification failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Response::serverError('Erro na verificação do webhook');
        }
    }

    /**
     * Processar evento individual do Instagram
     */
    private static function processInstagramEvent(array $event, string $companyId, array $app): void
    {
        try {
            // Extrair informações do usuário
            $webhookProvider = new InstagramWebhookProvider();
            $userInfo = $webhookProvider->extractUserInfo($event);
            $messageContent = $webhookProvider->extractMessageContent($event);
            
            // Mapear tipo de evento
            $eventType = $webhookProvider->mapEventType($event['event'] ?? 'unknown');
            
            // Buscar instância pelo instagram_user_id
            $instance = null;
            if ($userInfo['user_id']) {
                $instance = Instance::getByInstagramUserId($userInfo['user_id']);
            }
            
            if (!$instance) {
                Logger::warning('Instagram event for unknown instance', [
                    'company_id' => $companyId,
                    'user_id' => $userInfo['user_id'],
                    'event_type' => $eventType
                ]);
                return;
            }
            
            // Criar evento traduzido
            $translatedEvent = [
                'event' => $eventType,
                'timestamp' => $event['timestamp'] ?? time() * 1000,
                'instance_id' => $instance['external_instance_id'],
                'company_id' => $companyId,
                'user_id' => $userInfo['user_id'],
                'page_id' => $userInfo['page_id'],
                'message_id' => $userInfo['message_id'],
                'content' => $messageContent,
                'source' => 'instagram',
                'payload' => $event
            ];
            
            // Enviar para fila de entrada da empresa
            $routingKey = "company.{$companyId}";
            
            QueueService::publishToExchange(
                'messaging.inbound.exchange',
                $translatedEvent,
                $routingKey
            );
            
            Logger::info('Instagram event processed and sent to queue', [
                'company_id' => $companyId,
                'instance_id' => $instance['id'],
                'event_type' => $eventType,
                'routing_key' => $routingKey
            ]);

        } catch (\Exception $e) {
            Logger::error('Instagram event processing failed', [
                'company_id' => $companyId,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Processar evento internamente (salvar no banco, etc)
     */
    private static function processEventInternally(array $event, array $instance): void
    {
        try {
            // Aqui você pode adicionar lógica para processar eventos internamente
            // Por exemplo, salvar no banco de dados, atualizar status, etc.
            
            Logger::info('Instagram event processed internally', [
                'instance_id' => $instance['id'],
                'event_type' => $event['event'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Logger::error('Instagram internal event processing failed', [
                'instance_id' => $instance['id'] ?? null,
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}
