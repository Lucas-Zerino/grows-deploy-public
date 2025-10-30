# Integração Instagram Business API

Este documento descreve como configurar e usar a integração com Instagram Business API no GrowHub Gateway.

## Visão Geral

A integração Instagram permite:
- Conectar contas Instagram Business via OAuth2
- Enviar mensagens para usuários que iniciaram conversa
- Receber webhooks de mensagens e eventos
- Gerenciar múltiplas contas Instagram por empresa

## Pré-requisitos

1. **Conta Instagram Business** - Sua conta deve ser convertida para Business
2. **Meta Developer Account** - Acesso ao [Meta Developer](https://developers.facebook.com/)
3. **Instagram App** - App criado no Meta Developer com permissões necessárias

## Configuração do Instagram App

### 1. Criar App no Meta Developer

1. Acesse [Meta Developer](https://developers.facebook.com/)
2. Clique em "Meus Apps" > "Criar App"
3. Selecione "Business" como tipo de app
4. Preencha os dados do app:
   - **Nome do App**: Ex: "GrowHub Instagram Integration"
   - **Email de contato**: Seu email
   - **Categoria**: "Business"

### 2. Adicionar Produto Instagram

1. No painel do app, clique em "Adicionar Produto"
2. Encontre "Instagram" e clique em "Configurar"
3. Selecione "Instagram API with Instagram Login"

### 3. Configurar Permissões

No painel do Instagram, configure as seguintes permissões:

- `instagram_business_basic` - Acesso básico à conta
- `instagram_business_manage_messages` - Gerenciar mensagens
- `instagram_business_manage_comments` - Gerenciar comentários
- `instagram_business_content_publish` - Publicar conteúdo

### 4. Configurar OAuth Redirect URIs

Adicione a seguinte URL como Redirect URI:
```
https://gapi.sockets.com.br/api/instagram/callback
```

### 5. Configurar Webhooks

1. No painel do Instagram, vá para "Webhooks"
2. Adicione a seguinte URL de webhook:
   ```
   https://gapi.sockets.com.br/webhook/instagram/{company_id}
   ```
   Substitua `{company_id}` pelo ID da sua empresa no sistema.

3. Configure os seguintes campos:
   - **Verify Token**: Use o valor de `INSTAGRAM_WEBHOOK_VERIFY_TOKEN` do seu `.env`
   - **Callback URL**: A URL do webhook acima

4. Selecione os seguintes eventos:
   - `messages` - Mensagens recebidas
   - `messaging_postbacks` - Botões e quick replies
   - `messaging_referrals` - Referrals de links/ads

## Configuração no GrowHub

### 1. Variáveis de Ambiente

Adicione as seguintes variáveis no seu arquivo `.env`:

```env
# Instagram Integration
INSTAGRAM_REDIRECT_URI=https://gapi.sockets.com.br/api/instagram/callback
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=seu-token-secreto-aqui
ENCRYPTION_KEY=sua-chave-de-criptografia-aqui
```

### 2. Executar Migration

Execute a migration para criar as tabelas necessárias:

```bash
docker-compose exec php-fpm php scripts/run-migration.php
```

### 3. Criar Provider Instagram

No painel admin, crie um novo provider do tipo "instagram":

```json
{
  "type": "instagram",
  "name": "Instagram Business API",
  "base_url": "https://graph.instagram.com",
  "is_active": true
}
```

## Uso da API

### 1. Configurar Instagram App da Empresa

```bash
POST /api/instagram/app
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "app_id": "1234567890123456",
  "app_secret": "abc123def456ghi789..."
}
```

### 2. Obter URL de Autenticação

```bash
GET /api/instagram/auth-url
Authorization: Bearer {company_token}
```

Resposta:
```json
{
  "success": true,
  "data": {
    "auth_url": "https://www.instagram.com/oauth/authorize?...",
    "redirect_uri": "https://gapi.sockets.com.br/api/instagram/callback",
    "scopes": ["instagram_business_basic", "instagram_business_manage_messages", ...]
  }
}
```

### 3. Verificar Status de Autenticação

```bash
GET /api/instagram/auth-status
Authorization: Bearer {company_token}
```

Resposta:
```json
{
  "success": true,
  "data": {
    "authenticated": true,
    "status": "connected",
    "user_id": "123456789",
    "username": "minha_empresa",
    "account_type": "BUSINESS",
    "token_expires_at": "2024-12-29 15:30:00"
  }
}
```

### 4. Criar Instância Instagram

```bash
POST /api/instances
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_name": "instagram-principal",
  "provider_id": 3
}
```

Resposta:
```json
{
  "success": true,
  "message": "Instância criada com sucesso",
  "data": {
    "id": 15,
    "instance_name": "instagram-principal",
    "provider_id": 3,
    "status": "creating",
    "auth_url": "https://www.instagram.com/oauth/authorize?...",
    "requires_oauth": true
  }
}
```

### 5. Enviar Mensagens

```bash
POST /send/text
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_id": 15,
  "to": "123456789",
  "message": "Olá! Como posso ajudar?"
}
```

### 6. Enviar Mídia

```bash
POST /send/media
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_id": 15,
  "to": "123456789",
  "media_type": "image",
  "media_url": "https://example.com/image.jpg",
  "caption": "Confira esta imagem!"
}
```

## Webhooks

### Estrutura do Webhook

Os webhooks do Instagram são recebidos em:
```
POST /webhook/instagram/{company_id}
```

### Eventos Suportados

- `message` - Mensagem de texto recebida
- `message.postback` - Botão ou quick reply clicado
- `message.referral` - Referral de link ou anúncio
- `message.reaction` - Reação a mensagem
- `message.ack` - Mensagem lida/entregue
- `typing.start` - Usuário começou a digitar
- `typing.stop` - Usuário parou de digitar

### Exemplo de Webhook

```json
{
  "event": "message",
  "timestamp": 1703123456789,
  "instance_id": "18-instagram-principal",
  "company_id": 18,
  "user_id": "123456789",
  "page_id": "987654321",
  "content": {
    "text": "Olá, preciso de ajuda!",
    "message_id": "msg_123456"
  },
  "source": "instagram"
}
```

## Limitações do Instagram

### 1. Janela de 24 Horas

- Só é possível enviar mensagens para usuários que iniciaram conversa
- A janela de 24h é resetada a cada nova mensagem do usuário
- Após 24h sem interação, é necessário usar templates aprovados

### 2. Tipos de Mídia Suportados

- **Imagens**: JPG, PNG, GIF
- **Vídeos**: MP4, MOV
- **Áudios**: MP3, M4A, OGG
- **Arquivos**: PDF, DOC, XLS, etc.

### 3. Rate Limits

- **200 requisições por hora** por usuário
- **1000 requisições por dia** por usuário
- **100 requisições por hora** para webhooks

### 4. Templates de Mensagem

Para mensagens fora da janela de 24h, use templates aprovados:

```bash
POST /send/template
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_id": 15,
  "to": "123456789",
  "template_name": "welcome_message",
  "parameters": {
    "name": "João"
  }
}
```

## Troubleshooting

### Erro: "Instagram App não configurado"

**Causa**: A empresa não configurou o Instagram App.

**Solução**: Configure o Instagram App usando `POST /api/instagram/app`.

### Erro: "Token inválido ou expirado"

**Causa**: O access token expirou (válido por 60 dias).

**Solução**: O sistema tenta renovar automaticamente. Se falhar, reconecte via OAuth.

### Erro: "Signature inválida" no webhook

**Causa**: O `INSTAGRAM_WEBHOOK_VERIFY_TOKEN` não confere.

**Solução**: Verifique se o token no `.env` é o mesmo configurado no Instagram App.

### Erro: "Mensagem não enviada"

**Causa**: Usuário não iniciou conversa ou janela de 24h expirou.

**Solução**: Aguarde o usuário enviar uma mensagem ou use templates aprovados.

### Erro: "Rate limit exceeded"

**Causa**: Excedeu o limite de requisições.

**Solução**: Aguarde 1 hora ou implemente retry com backoff exponencial.

## Monitoramento

### Logs Importantes

```bash
# Verificar logs do Instagram
docker logs growhub_php_dev | grep -i instagram

# Verificar webhooks
docker logs growhub_php_dev | grep -i "instagram webhook"

# Verificar OAuth
docker logs growhub_php_dev | grep -i "instagram oauth"
```

### Métricas Recomendadas

- Taxa de sucesso de envio de mensagens
- Tempo de resposta dos webhooks
- Renovação automática de tokens
- Uso de rate limits

## Segurança

### 1. Criptografia

- `app_secret` é criptografado no banco usando AES-256-CBC
- Use uma chave forte para `ENCRYPTION_KEY`

### 2. Validação de Webhooks

- Todos os webhooks são validados via HMAC SHA-256
- Nunca processe webhooks sem validação de signature

### 3. Tokens

- Access tokens são armazenados de forma segura
- Renovação automática quando possível
- Logout remove tokens do banco

## Suporte

Para dúvidas ou problemas:

1. Verifique os logs do sistema
2. Consulte a [documentação oficial do Instagram](https://developers.facebook.com/docs/instagram-platform)
3. Abra uma issue no repositório do projeto
