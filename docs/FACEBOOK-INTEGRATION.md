Facebook Messenger (Conversations API)

Referência: https://developers.facebook.com/docs/messenger-platform/conversations/

Requisitos:
- Permissões: pages_manage_metadata, pages_read_engagement
- Token: Page Access Token (usuário com MESSAGING ou MODERATE)
- Webhook: X-Hub-Signature-256 e Verify Token por empresa

Rotas Webhook:
- POST /webhook/facebook/{companyId}
- GET /webhook/facebook/{companyId}

Endpoints úteis:
- GET /{PAGE-ID}/conversations?platform=messenger
- GET /{CONVERSATION-ID}?fields=messages
- GET /{MESSAGE-ID}?fields=id,created_time,from,to,message,reply_to

Limitações:
- Detalhes apenas das 20 mensagens mais recentes por conversa
- Requests inativos >30 dias não retornam via API


