<?php

namespace App\Providers\Instagram;

use App\Utils\Logger;

class InstagramWebhookProvider
{
    /**
     * Verificar signature do webhook Instagram
     */
    public function verifySignature(array $headers, string $payload, string $appSecret): bool
    {
        $signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? '';
        
        if (empty($signature)) {
            Logger::warning('Instagram webhook signature missing', [
                'headers' => $headers
            ]);
            return false;
        }

        // Remover 'sha256=' do início se presente
        $signature = str_replace('sha256=', '', $signature);
        
        // Calcular HMAC SHA-256
        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);
        
        // Comparação segura
        $isValid = hash_equals($expectedSignature, $signature);
        
        if (!$isValid) {
            Logger::warning('Instagram webhook signature verification failed', [
                'expected' => $expectedSignature,
                'received' => $signature
            ]);
        } else {
            Logger::info('Instagram webhook signature verified');
        }
        
        return $isValid;
    }

    /**
     * Traduzir payload do Instagram para formato interno
     */
    public function translateToStandardFormat(array $payload): array
    {
        try {
            $standardEvent = [
                'event' => 'instagram.webhook',
                'timestamp' => time() * 1000,
                'data' => $payload,
                'source' => 'instagram'
            ];

            // Processar diferentes tipos de eventos
            if (isset($payload['object']) && $payload['object'] === 'instagram') {
                $standardEvent['event'] = 'instagram.messaging';
                
                if (isset($payload['entry'])) {
                    foreach ($payload['entry'] as $entry) {
                        if (isset($entry['messaging'])) {
                            foreach ($entry['messaging'] as $messaging) {
                                $event = $this->processMessagingEvent($messaging, $entry);
                                if ($event) {
                                    $standardEvent['events'][] = $event;
                                }
                            }
                        }
                    }
                }
            }

            Logger::info('Instagram webhook translated', [
                'original_event' => $payload['object'] ?? 'unknown',
                'standard_event' => $standardEvent['event'],
                'events_count' => count($standardEvent['events'] ?? [])
            ]);

            return $standardEvent;

        } catch (\Exception $e) {
            Logger::error('Instagram webhook translation failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'event' => 'instagram.webhook.error',
                'timestamp' => time() * 1000,
                'data' => $payload,
                'source' => 'instagram',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar evento de messaging
     */
    private function processMessagingEvent(array $messaging, array $entry): ?array
    {
        $event = [
            'timestamp' => $messaging['timestamp'] ?? time() * 1000,
            'sender' => $messaging['sender']['id'] ?? null,
            'recipient' => $messaging['recipient']['id'] ?? null,
            'page_id' => $entry['id'] ?? null
        ];

        // Mensagem recebida
        if (isset($messaging['message'])) {
            $message = $messaging['message'];
            $event['event'] = 'message';
            $event['message_id'] = $message['mid'] ?? null;
            $event['text'] = $message['text'] ?? '';
            $event['attachments'] = $message['attachments'] ?? [];
            
            // Mapear tipos de mídia
            if (isset($message['attachments'])) {
                foreach ($message['attachments'] as $attachment) {
                    $event['media_type'] = $attachment['type'] ?? 'unknown';
                    $event['media_url'] = $attachment['payload']['url'] ?? null;
                }
            }

            return $event;
        }

        // Postback (botões, quick replies)
        if (isset($messaging['postback'])) {
            $postback = $messaging['postback'];
            $event['event'] = 'postback';
            $event['payload'] = $postback['payload'] ?? '';
            $event['title'] = $postback['title'] ?? '';
            $event['referral'] = $postback['referral'] ?? null;

            return $event;
        }

        // Referral (links, ads)
        if (isset($messaging['referral'])) {
            $referral = $messaging['referral'];
            $event['event'] = 'referral';
            $event['ref'] = $referral['ref'] ?? '';
            $event['source'] = $referral['source'] ?? '';
            $event['type'] = $referral['type'] ?? '';

            return $event;
        }

        // Reação (like, heart, etc)
        if (isset($messaging['reaction'])) {
            $reaction = $messaging['reaction'];
            $event['event'] = 'reaction';
            $event['reaction'] = $reaction['reaction'] ?? '';
            $event['action'] = $reaction['action'] ?? '';
            $event['message_id'] = $reaction['mid'] ?? null;

            return $event;
        }

        // Leitura de mensagem
        if (isset($messaging['read'])) {
            $read = $messaging['read'];
            $event['event'] = 'message.read';
            $event['watermark'] = $read['watermark'] ?? null;

            return $event;
        }

        // Entrega de mensagem
        if (isset($messaging['delivery'])) {
            $delivery = $messaging['delivery'];
            $event['event'] = 'message.delivered';
            $event['watermark'] = $delivery['watermark'] ?? null;

            return $event;
        }

        // Typing indicator
        if (isset($messaging['typing_on'])) {
            $event['event'] = 'typing.on';
            return $event;
        }

        if (isset($messaging['typing_off'])) {
            $event['event'] = 'typing.off';
            return $event;
        }

        // Evento desconhecido
        Logger::warning('Unknown Instagram messaging event', [
            'messaging' => $messaging
        ]);

        return null;
    }

    /**
     * Mapear eventos Instagram para eventos padrão
     */
    public function mapEventType(string $instagramEvent): string
    {
        $mapping = [
            'message' => 'message',
            'postback' => 'message.postback',
            'referral' => 'message.referral',
            'reaction' => 'message.reaction',
            'message.read' => 'message.ack',
            'message.delivered' => 'message.ack',
            'typing.on' => 'typing.start',
            'typing.off' => 'typing.stop'
        ];

        return $mapping[$instagramEvent] ?? 'instagram.unknown';
    }

    /**
     * Extrair informações do usuário do evento
     */
    public function extractUserInfo(array $event): array
    {
        return [
            'user_id' => $event['sender'] ?? null,
            'page_id' => $event['page_id'] ?? null,
            'message_id' => $event['message_id'] ?? null,
            'timestamp' => $event['timestamp'] ?? time() * 1000
        ];
    }

    /**
     * Extrair conteúdo da mensagem
     */
    public function extractMessageContent(array $event): array
    {
        $content = [
            'text' => $event['text'] ?? '',
            'media_type' => $event['media_type'] ?? null,
            'media_url' => $event['media_url'] ?? null,
            'attachments' => $event['attachments'] ?? []
        ];

        // Adicionar payload para postbacks
        if (isset($event['payload'])) {
            $content['payload'] = $event['payload'];
        }

        // Adicionar título para postbacks
        if (isset($event['title'])) {
            $content['title'] = $event['title'];
        }

        // Adicionar referência para referrals
        if (isset($event['ref'])) {
            $content['ref'] = $event['ref'];
        }

        // Adicionar reação
        if (isset($event['reaction'])) {
            $content['reaction'] = $event['reaction'];
        }

        return $content;
    }

    /**
     * Validar se o webhook é válido
     */
    public function validateWebhook(array $payload): bool
    {
        // Verificar se tem a estrutura básica do Instagram
        if (!isset($payload['object']) || $payload['object'] !== 'instagram') {
            return false;
        }

        // Verificar se tem entries
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            return false;
        }

        // Verificar se pelo menos uma entry tem messaging
        foreach ($payload['entry'] as $entry) {
            if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrair ID da página/instância do webhook
     */
    public function extractPageId(array $payload): ?string
    {
        if (isset($payload['entry']) && is_array($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['id'])) {
                    return $entry['id'];
                }
            }
        }

        return null;
    }

    /**
     * Contar eventos no webhook
     */
    public function countEvents(array $payload): int
    {
        $count = 0;
        
        if (isset($payload['entry']) && is_array($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                    $count += count($entry['messaging']);
                }
            }
        }

        return $count;
    }
}
