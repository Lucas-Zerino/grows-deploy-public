# Exemplos de API - Instagram Integration

Este documento contém exemplos práticos de como usar a API do Instagram no GrowHub Gateway.

## Configuração Inicial

### 1. Configurar Instagram App

```bash
curl -X POST https://gapi.sockets.com.br/api/instagram/app \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "app_id": "1234567890123456",
    "app_secret": "abc123def456ghi789jkl012mno345pqr678"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Instagram App criado com sucesso",
  "data": {
    "id": 1,
    "app_id": "1234567890123456",
    "status": "pending",
    "has_access_token": false,
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### 2. Obter URL de Autenticação

```bash
curl -X GET https://gapi.sockets.com.br/api/instagram/auth-url \
  -H "Authorization: Bearer {company_token}"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "auth_url": "https://www.instagram.com/oauth/authorize?client_id=1234567890123456&redirect_uri=https://gapi.sockets.com.br/api/instagram/callback&response_type=code&scope=instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish&state=eyJjb21wYW55X2lkIjoxLCJhcHBfaWQiOjF9",
    "redirect_uri": "https://gapi.sockets.com.br/api/instagram/callback",
    "scopes": [
      "instagram_business_basic",
      "instagram_business_manage_messages",
      "instagram_business_manage_comments",
      "instagram_business_content_publish"
    ]
  }
}
```

### 3. Verificar Status de Autenticação

```bash
curl -X GET https://gapi.sockets.com.br/api/instagram/auth-status \
  -H "Authorization: Bearer {company_token}"
```

**Resposta (Conectado):**
```json
{
  "success": true,
  "data": {
    "authenticated": true,
    "status": "connected",
    "user_id": "123456789",
    "username": "minha_empresa",
    "account_type": "BUSINESS",
    "token_expires_at": "2024-03-15 10:30:00"
  }
}
```

**Resposta (Não Conectado):**
```json
{
  "success": true,
  "data": {
    "authenticated": false,
    "status": "not_connected",
    "message": "Instagram não conectado"
  }
}
```

## Gerenciamento de Instâncias

### 1. Criar Instância Instagram

```bash
curl -X POST https://gapi.sockets.com.br/api/instances \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_name": "instagram-principal",
    "provider_id": 3
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Instância criada com sucesso",
  "data": {
    "id": 15,
    "company_id": 18,
    "provider_id": 3,
    "instance_name": "instagram-principal",
    "status": "creating",
    "external_instance_id": "18-instagram-principal",
    "auth_url": "https://www.instagram.com/oauth/authorize?...",
    "requires_oauth": true,
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### 2. Listar Instâncias

```bash
curl -X GET https://gapi.sockets.com.br/api/instances \
  -H "Authorization: Bearer {company_token}"
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "company_id": 18,
      "provider_id": 3,
      "instance_name": "instagram-principal",
      "status": "connected",
      "instagram_user_id": "123456789",
      "instagram_username": "minha_empresa",
      "external_instance_id": "18-instagram-principal",
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "total": 1
}
```

### 3. Obter Detalhes da Instância

```bash
curl -X GET https://gapi.sockets.com.br/api/instances/15 \
  -H "Authorization: Bearer {company_token}"
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "company_id": 18,
    "provider_id": 3,
    "instance_name": "instagram-principal",
    "status": "connected",
    "instagram_user_id": "123456789",
    "instagram_username": "minha_empresa",
    "external_instance_id": "18-instagram-principal",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:35:00"
  }
}
```

## Envio de Mensagens

### 1. Enviar Mensagem de Texto

```bash
curl -X POST https://gapi.sockets.com.br/send/text \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "message": "Olá! Como posso ajudar você hoje?"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Mensagem enviada com sucesso",
  "data": {
    "message_id": "msg_abc123def456",
    "status": "sent",
    "timestamp": 1703123456789
  }
}
```

### 2. Enviar Imagem

```bash
curl -X POST https://gapi.sockets.com.br/send/media \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "media_type": "image",
    "media_url": "https://example.com/produto.jpg",
    "caption": "Confira nosso novo produto! 🛍️"
  }'
```

### 3. Enviar Vídeo

```bash
curl -X POST https://gapi.sockets.com.br/send/media \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "media_type": "video",
    "media_url": "https://example.com/demo.mp4",
    "caption": "Veja como funciona nosso produto em ação!"
  }'
```

### 4. Enviar Áudio

```bash
curl -X POST https://gapi.sockets.com.br/send/media \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "media_type": "audio",
    "media_url": "https://example.com/audio.mp3"
  }'
```

### 5. Enviar Arquivo

```bash
curl -X POST https://gapi.sockets.com.br/send/media \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "media_type": "file",
    "media_url": "https://example.com/catalogo.pdf",
    "caption": "Confira nosso catálogo completo!"
  }'
```

## Templates de Mensagem

### 1. Botões de Resposta Rápida

```bash
curl -X POST https://gapi.sockets.com.br/send/quick-reply \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "message": "Como posso ajudar você?",
    "quick_replies": [
      {
        "title": "Ver Produtos",
        "payload": "VER_PRODUTOS"
      },
      {
        "title": "Falar com Vendedor",
        "payload": "FALAR_VENDEDOR"
      },
      {
        "title": "Suporte Técnico",
        "payload": "SUPORTE_TECNICO"
      }
    ]
  }'
```

### 2. Template de Botões

```bash
curl -X POST https://gapi.sockets.com.br/send/button-template \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "text": "Escolha uma opção:",
    "buttons": [
      {
        "type": "web_url",
        "title": "Ver Site",
        "url": "https://minhaempresa.com"
      },
      {
        "type": "postback",
        "title": "Falar com Vendedor",
        "payload": "FALAR_VENDEDOR"
      }
    ]
  }'
```

### 3. Template de Lista

```bash
curl -X POST https://gapi.sockets.com.br/send/list-template \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "text": "Nossos produtos em destaque:",
    "elements": [
      {
        "title": "Produto A",
        "subtitle": "R$ 99,90",
        "image_url": "https://example.com/produto-a.jpg",
        "default_action": {
          "type": "web_url",
          "url": "https://minhaempresa.com/produto-a"
        }
      },
      {
        "title": "Produto B",
        "subtitle": "R$ 149,90",
        "image_url": "https://example.com/produto-b.jpg",
        "default_action": {
          "type": "web_url",
          "url": "https://minhaempresa.com/produto-b"
        }
      }
    ],
    "button_text": "Ver Mais"
  }'
```

### 4. Template de Carrossel

```bash
curl -X POST https://gapi.sockets.com.br/send/carousel-template \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "elements": [
      {
        "title": "Oferta Especial",
        "subtitle": "Desconto de 50%",
        "image_url": "https://example.com/oferta1.jpg",
        "buttons": [
          {
            "type": "web_url",
            "title": "Comprar Agora",
            "url": "https://minhaempresa.com/oferta1"
          }
        ]
      },
      {
        "title": "Novo Lançamento",
        "subtitle": "Chegou hoje!",
        "image_url": "https://example.com/lancamento.jpg",
        "buttons": [
          {
            "type": "web_url",
            "title": "Saiba Mais",
            "url": "https://minhaempresa.com/lancamento"
          }
        ]
      }
    ]
  }'
```

## Ações em Mensagens

### 1. Marcar como Lida

```bash
curl -X POST https://gapi.sockets.com.br/message/markread \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "message_id": "msg_abc123def456"
  }'
```

### 2. Indicador de Digitação

```bash
curl -X POST https://gapi.sockets.com.br/message/typing \
  -H "Authorization: Bearer {company_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": 15,
    "to": "123456789",
    "action": "typing_on"
  }'
```

## Webhooks

### Configuração do Webhook

Configure no Instagram App:
- **URL**: `https://gapi.sockets.com.br/webhook/instagram/{company_id}`
- **Verify Token**: Valor de `INSTAGRAM_WEBHOOK_VERIFY_TOKEN`
- **Eventos**: `messages`, `messaging_postbacks`, `messaging_referrals`

### Exemplo de Webhook Recebido

```json
{
  "event": "message",
  "timestamp": 1703123456789,
  "instance_id": "18-instagram-principal",
  "company_id": 18,
  "user_id": "123456789",
  "page_id": "987654321",
  "message_id": "msg_abc123def456",
  "content": {
    "text": "Olá, preciso de ajuda com meu pedido!",
    "attachments": []
  },
  "source": "instagram"
}
```

### Exemplo de Postback

```json
{
  "event": "message.postback",
  "timestamp": 1703123456789,
  "instance_id": "18-instagram-principal",
  "company_id": 18,
  "user_id": "123456789",
  "page_id": "987654321",
  "content": {
    "payload": "VER_PRODUTOS",
    "title": "Ver Produtos"
  },
  "source": "instagram"
}
```

### Exemplo de Referral

```json
{
  "event": "message.referral",
  "timestamp": 1703123456789,
  "instance_id": "18-instagram-principal",
  "company_id": 18,
  "user_id": "123456789",
  "page_id": "987654321",
  "content": {
    "ref": "promo_black_friday",
    "source": "SHORTLINK",
    "type": "OPEN_THREAD"
  },
  "source": "instagram"
}
```

## Tratamento de Erros

### Erro de Autenticação

```json
{
  "success": false,
  "message": "Instagram não conectado",
  "error_code": "NOT_AUTHENTICATED"
}
```

### Erro de Rate Limit

```json
{
  "success": false,
  "message": "Rate limit exceeded. Try again in 3600 seconds",
  "error_code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 3600
}
```

### Erro de Janela de 24h

```json
{
  "success": false,
  "message": "Cannot send message. User has not initiated conversation or 24h window expired",
  "error_code": "MESSAGE_WINDOW_EXPIRED"
}
```

### Erro de Mídia

```json
{
  "success": false,
  "message": "Invalid media type. Supported types: image, video, audio, file",
  "error_code": "INVALID_MEDIA_TYPE"
}
```

## Exemplos de Integração

### JavaScript (Frontend)

```javascript
// Conectar Instagram
async function connectInstagram() {
  try {
    const response = await fetch('/api/instagram/auth-url', {
      headers: {
        'Authorization': `Bearer ${companyToken}`
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Abrir popup de autenticação
      const popup = window.open(
        data.data.auth_url,
        'instagram-auth',
        'width=600,height=700'
      );
      
      // Monitorar fechamento do popup
      const checkClosed = setInterval(() => {
        if (popup.closed) {
          clearInterval(checkClosed);
          checkInstagramStatus();
        }
      }, 1000);
    }
  } catch (error) {
    console.error('Erro ao conectar Instagram:', error);
  }
}

// Enviar mensagem
async function sendMessage(instanceId, userId, message) {
  try {
    const response = await fetch('/send/text', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${companyToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        instance_id: instanceId,
        to: userId,
        message: message
      })
    });
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Erro ao enviar mensagem:', error);
    throw error;
  }
}
```

### PHP (Backend)

```php
<?php
// Conectar Instagram
function connectInstagram($companyToken) {
    $url = 'https://gapi.sockets.com.br/api/instagram/auth-url';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $companyToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Enviar mensagem
function sendInstagramMessage($companyToken, $instanceId, $userId, $message) {
    $url = 'https://gapi.sockets.com.br/send/text';
    
    $data = [
        'instance_id' => $instanceId,
        'to' => $userId,
        'message' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $companyToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
```

### Python (Backend)

```python
import requests
import json

class InstagramAPI:
    def __init__(self, base_url, company_token):
        self.base_url = base_url
        self.company_token = company_token
        self.headers = {
            'Authorization': f'Bearer {company_token}',
            'Content-Type': 'application/json'
        }
    
    def get_auth_url(self):
        response = requests.get(
            f'{self.base_url}/api/instagram/auth-url',
            headers=self.headers
        )
        return response.json()
    
    def send_message(self, instance_id, user_id, message):
        data = {
            'instance_id': instance_id,
            'to': user_id,
            'message': message
        }
        
        response = requests.post(
            f'{self.base_url}/send/text',
            headers=self.headers,
            data=json.dumps(data)
        )
        return response.json()
    
    def send_media(self, instance_id, user_id, media_type, media_url, caption=None):
        data = {
            'instance_id': instance_id,
            'to': user_id,
            'media_type': media_type,
            'media_url': media_url
        }
        
        if caption:
            data['caption'] = caption
        
        response = requests.post(
            f'{self.base_url}/send/media',
            headers=self.headers,
            data=json.dumps(data)
        )
        return response.json()

# Uso
api = InstagramAPI('https://gapi.sockets.com.br', 'your-company-token')

# Enviar mensagem
result = api.send_message(15, '123456789', 'Olá! Como posso ajudar?')
print(result)
```
