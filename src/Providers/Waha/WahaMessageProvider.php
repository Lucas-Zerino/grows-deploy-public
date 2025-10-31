<?php

namespace App\Providers\Waha;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Utils\Logger;

class WahaMessageProvider
{
    private Client $client;
    private string $baseUrl;
    private ?string $apiKey;
    
    public function __construct(Client $client, string $baseUrl, ?string $apiKey)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Formatar número para chatId (adiciona @c.us se necessário)
     */
    private function formatChatId(string $phone): string
    {
        // Remove caracteres não numéricos
        $phone = preg_replace('/\D/', '', $phone);
        
        // Se já tiver @c.us, retorna como está
        if (strpos($phone, '@') !== false) {
            return $phone;
        }
        
        // Adiciona @c.us se não tiver
        return $phone . '@c.us';
    }
    
    /**
     * Enviar mensagem de texto
     * WAHA: POST /api/sendText
     * Payload: {chatId, text, session, reply_to?, linkPreview?, linkPreviewHighQuality?}
     */
    public function sendText(string $externalInstanceId, string $to, string $message, ?string $replyTo = null, ?bool $linkPreview = null): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'text' => $message,
                'session' => $externalInstanceId
            ];
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            if ($linkPreview !== null) {
                $payload['linkPreview'] = $linkPreview;
            }
            
            $response = $this->client->post('/api/sendText', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA text message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send text message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar imagem
     * WAHA: POST /api/sendImage
     * Payload: {chatId, file: {mimetype, url, filename}, session, reply_to?, caption?}
     */
    public function sendImage(string $externalInstanceId, string $to, string $imageUrl, ?string $caption = null, ?string $replyTo = null): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'file' => [
                    'mimetype' => 'image/jpeg', // Pode ser detectado pela URL
                    'url' => $imageUrl
                ],
                'session' => $externalInstanceId
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendImage', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA image message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'image_url' => $imageUrl,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send image message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar imagem: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar arquivo/documento
     * WAHA: POST /api/sendFile
     * Payload: {chatId, file: {mimetype, url, filename}, session, reply_to?, caption?}
     */
    public function sendFile(string $externalInstanceId, string $to, string $fileUrl, string $mimetype, ?string $filename = null, ?string $caption = null, ?string $replyTo = null): array
    {
        try {
            $file = [
                'mimetype' => $mimetype,
                'url' => $fileUrl
            ];
            
            if ($filename) {
                $file['filename'] = $filename;
            }
            
            $payload = [
                'chatId' => $this->formatChatId($to),
                'file' => $file,
                'session' => $externalInstanceId
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendFile', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA file message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'file_url' => $fileUrl,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Arquivo enviado com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send file message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'file_url' => $fileUrl,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar arquivo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar mensagem de áudio/voice
     * WAHA: POST /api/sendVoice
     * Payload: {chatId, file: {mimetype, url}, session, reply_to?, convert?}
     */
    public function sendVoice(string $externalInstanceId, string $to, string $audioUrl, ?string $replyTo = null, bool $convert = true): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'file' => [
                    'mimetype' => 'audio/ogg; codecs=opus', // Padrão WAHA
                    'url' => $audioUrl
                ],
                'session' => $externalInstanceId,
                'convert' => $convert
            ];
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendVoice', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA voice message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'audio_url' => $audioUrl,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Áudio enviado com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send voice message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'audio_url' => $audioUrl,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar áudio: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar vídeo
     * WAHA: POST /api/sendVideo
     * Payload: {chatId, file: {mimetype, url, filename}, session, reply_to?, caption?, asNote?, convert?}
     */
    public function sendVideo(string $externalInstanceId, string $to, string $videoUrl, ?string $caption = null, ?string $replyTo = null, bool $asNote = false, bool $convert = true): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'file' => [
                    'mimetype' => 'video/mp4',
                    'url' => $videoUrl
                ],
                'session' => $externalInstanceId,
                'asNote' => $asNote,
                'convert' => $convert
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendVideo', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA video message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'video_url' => $videoUrl,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Vídeo enviado com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send video message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'video_url' => $videoUrl,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar vídeo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar mídia (método genérico que detecta o tipo)
     * Padrão UAZAPI: POST /send/media
     */
    public function sendMedia(string $externalInstanceId, string $to, string $mediaUrl, ?string $caption = null, ?string $type = null): array
    {
        $type = strtolower($type ?? 'image');
        
        switch ($type) {
            case 'image':
                return $this->sendImage($externalInstanceId, $to, $mediaUrl, $caption);
            case 'video':
                return $this->sendVideo($externalInstanceId, $to, $mediaUrl, $caption);
            case 'audio':
            case 'voice':
                return $this->sendVoice($externalInstanceId, $to, $mediaUrl);
            case 'document':
            case 'file':
            default:
                return $this->sendFile($externalInstanceId, $to, $mediaUrl, 'application/pdf', null, $caption);
        }
    }
    
    /**
     * Enviar contato (vCard)
     * WAHA: POST /api/sendContactVcard
     * Payload: {chatId, contacts: [{vcard} | {fullName, organization, phoneNumber, whatsappId}], session, reply_to?}
     */
    public function sendContact(string $externalInstanceId, string $to, array $contact, ?string $replyTo = null): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'contacts' => [],
                'session' => $externalInstanceId
            ];
            
            // WAHA aceita dois formatos:
            // 1. vCard completo (string) - RECOMENDADO para garantir campo N correto
            // 2. Campos individuais (fullName, organization, phoneNumber, whatsappId)
            
            // PROBLEMA: WAHA converte campos individuais para vCard internamente,
            // mas não inclui o campo N (Name) que é obrigatório para WhatsApp exibir o nome
            // SOLUÇÃO: Sempre gerar vCard com campo N quando tivermos os dados
            
            // Se já tiver vcard como string, usar diretamente
            if (isset($contact['vcard']) && is_string($contact['vcard'])) {
                Logger::info('Using provided vCard string', [
                    'external_id' => $externalInstanceId,
                    'vcard_length' => strlen($contact['vcard'])
                ]);
                $payload['contacts'][] = ['vcard' => $contact['vcard']];
            }
            // SEMPRE gerar vCard quando tivermos name/fullName para garantir campo N
            else {
                Logger::info('Generating vCard with N field', [
                    'external_id' => $externalInstanceId,
                    'has_fullName' => isset($contact['fullName']),
                    'has_name' => isset($contact['name']),
                    'contact_keys' => array_keys($contact)
                ]);
                
                $vcard = $this->formatContactAsVcard($contact);
                Logger::info('Generated vCard', [
                    'external_id' => $externalInstanceId,
                    'vcard_preview' => substr($vcard, 0, 150) . '...',
                    'has_N_field' => strpos($vcard, 'N:') !== false
                ]);
                
                $payload['contacts'][] = ['vcard' => $vcard];
            }
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendContactVcard', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA contact message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'contact_name' => $contact['name'] ?? 'Unknown',
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Contato enviado com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send contact message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar contato: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatar contato como vCard
     * O campo N (Name) é obrigatório para o WhatsApp exibir o nome corretamente
     * Formato: N:sobrenome;nome;segundo_nome;prefixo;sufixo
     */
    private function formatContactAsVcard(array $contact): string
    {
        $name = $contact['name'] ?? $contact['fullName'] ?? 'Unknown';
        $phone = $contact['phone'] ?? $contact['phoneNumber'] ?? '';
        $org = $contact['organization'] ?? $contact['org'] ?? '';
        $email = $contact['email'] ?? '';
        $url = $contact['url'] ?? '';
        
        // Extrair sobrenome e nome do nome completo
        // Se tiver espaço, assume que último é sobrenome
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Formato N: sobrenome;nome;segundo_nome;prefixo;sufixo
        // Se não tiver sobrenome, usar nome completo como nome
        $nField = $lastName ? "{$lastName};{$firstName};;;" : ";{$name};;;";
        
        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "N:{$nField}\n"; // Campo N é obrigatório para WhatsApp
        $vcard .= "FN:{$name}\n"; // Full Name
        
        if ($org) {
            $vcard .= "ORG:{$org};\n";
        }
        
        if ($phone) {
            // Remove caracteres não numéricos
            $phone = preg_replace('/\D/', '', $phone);
            $vcard .= "TEL;type=CELL;type=VOICE;waid={$phone}:+{$phone}\n";
        }
        
        if ($email) {
            $vcard .= "EMAIL:{$email}\n";
        }
        
        if ($url) {
            $vcard .= "URL:{$url}\n";
        }
        
        $vcard .= "END:VCARD";
        
        return $vcard;
    }
    
    /**
     * Enviar localização
     * WAHA: POST /api/sendLocation
     * Payload: {chatId, latitude, longitude, title?, session, reply_to?}
     */
    public function sendLocation(string $externalInstanceId, string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null, ?string $replyTo = null): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'session' => $externalInstanceId
            ];
            
            if ($name) {
                $payload['title'] = $name;
            }
            
            // O campo 'address' não é suportado pela API WAHA sendLocation
            // O WAHA só aceita: chatId, latitude, longitude, title (opcional), session, reply_to (opcional)
            
            // Só adicionar reply_to se não for null/vazio (conforme documentação WAHA)
            if ($replyTo && !empty(trim($replyTo))) {
                $payload['reply_to'] = $replyTo;
            }
            
            Logger::info('Sending location to WAHA', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'payload' => $payload,
                'endpoint' => '/api/sendLocation'
            ]);
            
            $response = $this->client->post('/api/sendLocation', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA location message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Localização enviada com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            $errorMessage = $e->getMessage();
            
            // Verificar se o erro indica que o método não está implementado
            if (strpos($errorMessage, 'Method not implemented') !== false || strpos($errorMessage, '500 Internal Server Error') !== false) {
                Logger::error('WAHA sendLocation method not implemented or unavailable', [
                    'external_id' => $externalInstanceId,
                    'to' => $to,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'error' => $errorMessage,
                    'note' => 'This may indicate that the WAHA version does not support sendLocation endpoint'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'O método de envio de localização não está disponível nesta versão do WAHA. Verifique se o WAHA está atualizado e suporta o endpoint /api/sendLocation',
                    'error_code' => 'METHOD_NOT_IMPLEMENTED'
                ];
            }
            
            Logger::error('WAHA send location message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $errorMessage,
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar localização: ' . $errorMessage
            ];
        }
    }
    
    /**
     * Enviar botões interativos
     * WAHA: POST /api/sendButtons
     * Payload: {chatId, header?, body, footer?, buttons: [{type, text, ...}], session, headerImage?}
     */
    public function sendButtons(string $externalInstanceId, string $to, string $body, array $buttons, ?string $header = null, ?string $footer = null, ?array $headerImage = null, ?string $replyTo = null): array
    {
        try {
            $payload = [
                'chatId' => $this->formatChatId($to),
                'body' => $body,
                'buttons' => $this->formatButtons($buttons),
                'session' => $externalInstanceId
            ];
            
            if ($header) {
                $payload['header'] = $header;
            }
            
            if ($footer) {
                $payload['footer'] = $footer;
            }
            
            if ($headerImage) {
                $payload['headerImage'] = $headerImage;
            }
            
            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }
            
            $response = $this->client->post('/api/sendButtons', [
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Logger::info('WAHA buttons message sent', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'buttons_count' => count($buttons),
                'message_id' => $data['id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Botões enviados com sucesso',
                'message_id' => $data['id'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA send buttons message failed', [
                'external_id' => $externalInstanceId,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar botões: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatar botões para o formato WAHA
     */
    private function formatButtons(array $buttons): array
    {
        $formatted = [];
        
        foreach ($buttons as $button) {
            $type = $button['type'] ?? 'reply';
            $text = $button['text'] ?? $button['label'] ?? '';
            
            $formattedButton = [
                'type' => $type,
                'text' => $text
            ];
            
            switch ($type) {
                case 'url':
                    if (isset($button['url'])) {
                        $formattedButton['url'] = $button['url'];
                    }
                    break;
                case 'call':
                    if (isset($button['phoneNumber'])) {
                        $formattedButton['phoneNumber'] = $button['phoneNumber'];
                    }
                    break;
                case 'copy':
                    if (isset($button['copyCode'])) {
                        $formattedButton['copyCode'] = $button['copyCode'];
                    }
                    break;
                case 'reply':
                default:
                    // Botão de resposta (padrão)
                    break;
            }
            
            $formatted[] = $formattedButton;
        }
        
        return $formatted;
    }
    
    /**
     * Verificar se um número existe no WhatsApp
     * WAHA: GET /api/checkNumberStatus?phone={phone}&session={session}
     * @return array ['success' => bool, 'exists' => bool, 'chatId' => string|null]
     */
    public function checkNumberStatus(string $externalInstanceId, string $phone): array
    {
        try {
            // Remove caracteres não numéricos do número
            $cleanPhone = preg_replace('/\D/', '', $phone);
            
            // Timeout curto: connect_timeout 3s, timeout total 8s
            // Validação de número pode ser lenta quando número não existe, então limitamos o tempo
            $response = $this->client->get('/api/checkNumberStatus', [
                'query' => [
                    'phone' => $cleanPhone,
                    'session' => $externalInstanceId
                ],
                'connect_timeout' => 3,
                'timeout' => 8
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $exists = $data['numberExists'] ?? false;
            $chatId = $data['chatId'] ?? null;
            
            Logger::info('WAHA number status checked', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'exists' => $exists,
                'chatId' => $chatId
            ]);
            
            return [
                'success' => true,
                'exists' => $exists,
                'chatId' => $chatId,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            Logger::error('WAHA check number status failed', [
                'external_id' => $externalInstanceId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
