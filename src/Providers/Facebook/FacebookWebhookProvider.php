<?php

namespace App\Providers\Facebook;

use App\Utils\Logger;

class FacebookWebhookProvider
{
    public function verifySignature(array $headers, string $payload, string $appSecret): bool
    {
        $signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? '';
        if (empty($signature)) {
            Logger::warning('Facebook webhook signature missing', [
                'headers' => $headers
            ]);
            return false;
        }

        $signature = str_replace('sha256=', '', $signature);
        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Logger::warning('Facebook webhook signature verification failed', [
                'expected' => $expectedSignature,
                'received' => $signature
            ]);
        } else {
            Logger::info('Facebook webhook signature verified');
        }

        return $isValid;
    }

    public function translateToStandardFormat(array $payload): array
    {
        try {
            $standardEvent = [
                'event' => 'facebook.webhook',
                'timestamp' => time() * 1000,
                'data' => $payload,
                'source' => 'facebook'
            ];

            if (isset($payload['object']) && $payload['object'] === 'page') {
                $standardEvent['event'] = 'facebook.messaging';

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

            Logger::info('Facebook webhook translated', [
                'original_object' => $payload['object'] ?? 'unknown',
                'events_count' => count($standardEvent['events'] ?? [])
            ]);

            return $standardEvent;

        } catch (\Exception $e) {
            Logger::error('Facebook webhook translation failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'event' => 'facebook.webhook.error',
                'timestamp' => time() * 1000,
                'data' => $payload,
                'source' => 'facebook',
                'error' => $e->getMessage()
            ];
        }
    }

    private function processMessagingEvent(array $messaging, array $entry): ?array
    {
        $event = [
            'timestamp' => $messaging['timestamp'] ?? time() * 1000,
            'sender' => $messaging['sender']['id'] ?? null, // PSID
            'recipient' => $messaging['recipient']['id'] ?? null, // Page ID
            'page_id' => $entry['id'] ?? ($messaging['recipient']['id'] ?? null)
        ];

        if (isset($messaging['message'])) {
            $message = $messaging['message'];
            $event['event'] = 'message';
            $event['message_id'] = $message['mid'] ?? null;
            $event['text'] = $message['text'] ?? '';
            $event['attachments'] = $message['attachments'] ?? [];

            if (isset($message['attachments'])) {
                foreach ($message['attachments'] as $attachment) {
                    $event['media_type'] = $attachment['type'] ?? 'unknown';
                    $event['media_url'] = $attachment['payload']['url'] ?? null;
                }
            }

            return $event;
        }

        if (isset($messaging['postback'])) {
            $postback = $messaging['postback'];
            $event['event'] = 'postback';
            $event['payload'] = $postback['payload'] ?? '';
            $event['title'] = $postback['title'] ?? '';
            $event['referral'] = $postback['referral'] ?? null;

            return $event;
        }

        if (isset($messaging['referral'])) {
            $referral = $messaging['referral'];
            $event['event'] = 'referral';
            $event['ref'] = $referral['ref'] ?? '';
            $event['source'] = $referral['source'] ?? '';
            $event['type'] = $referral['type'] ?? '';

            return $event;
        }

        if (isset($messaging['reaction'])) {
            $reaction = $messaging['reaction'];
            $event['event'] = 'reaction';
            $event['reaction'] = $reaction['reaction'] ?? '';
            $event['action'] = $reaction['action'] ?? '';
            $event['message_id'] = $reaction['mid'] ?? null;

            return $event;
        }

        if (isset($messaging['read'])) {
            $read = $messaging['read'];
            $event['event'] = 'message.read';
            $event['watermark'] = $read['watermark'] ?? null;

            return $event;
        }

        if (isset($messaging['delivery'])) {
            $delivery = $messaging['delivery'];
            $event['event'] = 'message.delivered';
            $event['watermark'] = $delivery['watermark'] ?? null;

            return $event;
        }

        if (isset($messaging['typing_on'])) {
            $event['event'] = 'typing.on';
            return $event;
        }
        if (isset($messaging['typing_off'])) {
            $event['event'] = 'typing.off';
            return $event;
        }

        Logger::warning('Unknown Facebook messaging event', [
            'messaging' => $messaging
        ]);
        return null;
    }

    public function mapEventType(string $facebookEvent): string
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
        return $mapping[$facebookEvent] ?? 'facebook.unknown';
    }

    public function extractUserInfo(array $event): array
    {
        return [
            'user_id' => $event['sender'] ?? null,
            'page_id' => $event['page_id'] ?? null,
            'message_id' => $event['message_id'] ?? null,
            'timestamp' => $event['timestamp'] ?? time() * 1000
        ];
    }

    public function extractMessageContent(array $event): array
    {
        $content = [
            'text' => $event['text'] ?? '',
            'media_type' => $event['media_type'] ?? null,
            'media_url' => $event['media_url'] ?? null,
            'attachments' => $event['attachments'] ?? []
        ];

        if (isset($event['payload'])) {
            $content['payload'] = $event['payload'];
        }
        if (isset($event['title'])) {
            $content['title'] = $event['title'];
        }
        if (isset($event['ref'])) {
            $content['ref'] = $event['ref'];
        }
        if (isset($event['reaction'])) {
            $content['reaction'] = $event['reaction'];
        }

        return $content;
    }

    public function validateWebhook(array $payload): bool
    {
        if (!isset($payload['object']) || $payload['object'] !== 'page') {
            return false;
        }
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            return false;
        }
        foreach ($payload['entry'] as $entry) {
            if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                return true;
            }
        }
        return false;
    }

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


