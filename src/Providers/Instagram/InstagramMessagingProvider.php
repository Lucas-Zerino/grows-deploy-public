<?php

namespace App\Providers\Instagram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class InstagramMessagingProvider
{
    private Client $client;
    private string $baseUrl = 'https://graph.instagram.com';

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
     * Enviar mensagem genérica
     */
    public function sendMessage(string $igUserId, string $recipientId, array $message): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/{$igUserId}/messages", [
                'json' => [
                    'recipient' => ['id' => $recipientId],
                    'message' => $message
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to send message: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            Logger::info('Instagram message sent', [
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId,
                'message_id' => $data['message_id'] ?? null
            ]);

            return [
                'success' => true,
                'message_id' => $data['message_id'] ?? null,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram message send failed', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram message send error', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar mensagem de texto
     */
    public function sendTextMessage(string $igUserId, string $recipientId, string $text): array
    {
        $message = [
            'text' => $text
        ];

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar mensagem com mídia
     */
    public function sendMediaMessage(string $igUserId, string $recipientId, string $mediaUrl, string $mediaType, ?string $caption = null): array
    {
        $message = [
            'attachment' => [
                'type' => $mediaType,
                'payload' => [
                    'url' => $mediaUrl
                ]
            ]
        ];

        if ($caption) {
            $message['attachment']['payload']['caption'] = $caption;
        }

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar imagem
     */
    public function sendImage(string $igUserId, string $recipientId, string $imageUrl, ?string $caption = null): array
    {
        return $this->sendMediaMessage($igUserId, $recipientId, $imageUrl, 'image', $caption);
    }

    /**
     * Enviar vídeo
     */
    public function sendVideo(string $igUserId, string $recipientId, string $videoUrl, ?string $caption = null): array
    {
        return $this->sendMediaMessage($igUserId, $recipientId, $videoUrl, 'video', $caption);
    }

    /**
     * Enviar áudio
     */
    public function sendAudio(string $igUserId, string $recipientId, string $audioUrl): array
    {
        return $this->sendMediaMessage($igUserId, $recipientId, $audioUrl, 'audio');
    }

    /**
     * Enviar arquivo
     */
    public function sendFile(string $igUserId, string $recipientId, string $fileUrl, ?string $caption = null): array
    {
        return $this->sendMediaMessage($igUserId, $recipientId, $fileUrl, 'file', $caption);
    }

    /**
     * Responder mensagem específica
     */
    public function sendReply(string $igUserId, string $recipientId, string $messageId, string $text): array
    {
        $message = [
            'text' => $text,
            'reply_to' => [
                'message_id' => $messageId
            ]
        ];

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar botão de resposta rápida
     */
    public function sendQuickReply(string $igUserId, string $recipientId, string $text, array $quickReplies): array
    {
        $message = [
            'text' => $text,
            'quick_replies' => array_map(function($reply) {
                return [
                    'content_type' => 'text',
                    'title' => $reply['title'],
                    'payload' => $reply['payload'] ?? $reply['title']
                ];
            }, $quickReplies)
        ];

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar template de botões
     */
    public function sendButtonTemplate(string $igUserId, string $recipientId, string $text, array $buttons): array
    {
        $message = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $text,
                    'buttons' => array_map(function($button) {
                        $btn = [
                            'type' => $button['type'] ?? 'web_url',
                            'title' => $button['title']
                        ];

                        if ($button['type'] === 'web_url') {
                            $btn['url'] = $button['url'];
                        } elseif ($button['type'] === 'postback') {
                            $btn['payload'] = $button['payload'];
                        }

                        return $btn;
                    }, $buttons)
                ]
            ]
        ];

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar template de lista
     */
    public function sendListTemplate(string $igUserId, string $recipientId, string $text, array $elements, ?string $buttonText = null): array
    {
        $message = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'list',
                    'text' => $text,
                    'elements' => array_map(function($element) {
                        return [
                            'title' => $element['title'],
                            'subtitle' => $element['subtitle'] ?? '',
                            'image_url' => $element['image_url'] ?? null,
                            'default_action' => $element['default_action'] ?? null,
                            'buttons' => $element['buttons'] ?? []
                        ];
                    }, $elements)
                ]
            ]
        ];

        if ($buttonText) {
            $message['attachment']['payload']['buttons'] = [
                [
                    'type' => 'postback',
                    'title' => $buttonText,
                    'payload' => 'SHOW_MORE'
                ]
            ];
        }

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Enviar template de carrossel
     */
    public function sendCarouselTemplate(string $igUserId, string $recipientId, array $elements): array
    {
        $message = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' => array_map(function($element) {
                        $el = [
                            'title' => $element['title'],
                            'subtitle' => $element['subtitle'] ?? '',
                            'image_url' => $element['image_url'] ?? null,
                            'default_action' => $element['default_action'] ?? null
                        ];

                        if (isset($element['buttons'])) {
                            $el['buttons'] = array_map(function($button) {
                                $btn = [
                                    'type' => $button['type'] ?? 'web_url',
                                    'title' => $button['title']
                                ];

                                if ($button['type'] === 'web_url') {
                                    $btn['url'] = $button['url'];
                                } elseif ($button['type'] === 'postback') {
                                    $btn['payload'] = $button['payload'];
                                }

                                return $btn;
                            }, $element['buttons']);
                        }

                        return $el;
                    }, $elements)
                ]
            ]
        ];

        return $this->sendMessage($igUserId, $recipientId, $message);
    }

    /**
     * Marcar mensagem como lida
     */
    public function markAsRead(string $igUserId, string $recipientId, string $messageId): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/{$igUserId}/messages", [
                'json' => [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => 'mark_seen',
                    'message' => [
                        'text' => '',
                        'reply_to' => [
                            'message_id' => $messageId
                        ]
                    ]
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to mark as read: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            Logger::info('Instagram message marked as read', [
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId,
                'message_id' => $messageId
            ]);

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram mark as read failed', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId,
                'message_id' => $messageId
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao marcar como lida: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram mark as read error', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId,
                'message_id' => $messageId
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Indicar que está digitando
     */
    public function sendTypingIndicator(string $igUserId, string $recipientId): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/{$igUserId}/messages", [
                'json' => [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => 'typing_on'
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to send typing indicator: ' . $responseBody);
            }

            if (isset($data['error'])) {
                throw new \Exception('Instagram API error: ' . $data['error']['message']);
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (GuzzleException $e) {
            Logger::error('Instagram typing indicator failed', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar indicador de digitação: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Logger::error('Instagram typing indicator error', [
                'error' => $e->getMessage(),
                'ig_user_id' => $igUserId,
                'recipient_id' => $recipientId
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
