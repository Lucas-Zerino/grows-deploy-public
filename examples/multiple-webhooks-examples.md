# 🔗 Exemplos de Múltiplos Webhooks

## 📋 Resumo da Implementação

✅ **Implementado com sucesso!** O sistema agora suporta múltiplos webhooks por instância.

## 🚀 Exemplos de Uso

### 1. Criar Instância com 2 Webhooks

```bash
curl -X POST "http://localhost:8000/api/instances" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "instance_name": "minha-instancia",
    "provider_id": 1,
    "phone_number": "5511999999999",
    "webhooks": [
      {
        "url": "https://webhook.site/11111111-1111-1111-1111-11111111",
        "events": ["message", "message.ack"],
        "is_active": true
      },
      {
        "url": "https://webhook.cool/22222222-2222-2222-2222-22222222",
        "events": ["session.status", "presence.update"],
        "is_active": true
      }
    ]
  }'
```

### 2. Exemplo com 3 Webhooks Especializados

```bash
curl -X POST "http://localhost:8000/api/instances" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "instance_name": "sistema-completo",
    "provider_id": 1,
    "phone_number": "5511888888888",
    "webhooks": [
      {
        "url": "https://notificacoes.com/webhook/mensagens",
        "events": ["message", "message.any", "message.ack"],
        "is_active": true
      },
      {
        "url": "https://analytics.com/webhook/status",
        "events": ["session.status", "presence.update", "group.v2.join", "group.v2.leave"],
        "is_active": true
      },
      {
        "url": "https://backup.com/webhook/todos",
        "events": ["message", "message.ack", "session.status", "presence.update", "group.v2.join", "group.v2.leave", "poll.vote"],
        "is_active": true
      }
    ]
  }'
```

### 3. Exemplo com Headers Customizados

```bash
curl -X POST "http://localhost:8000/api/instances" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "instance_name": "webhook-com-headers",
    "provider_id": 1,
    "phone_number": "5511777777777",
    "webhooks": [
      {
        "url": "https://api.meuservidor.com/webhook/seguro",
        "events": ["message", "message.ack"],
        "is_active": true,
        "customHeaders": {
          "X-API-Key": "minha-chave-secreta-123",
          "X-Custom-Header": "valor-customizado"
        }
      },
      {
        "url": "https://webhook.site/33333333-3333-3333-3333-33333333",
        "events": ["session.status"],
        "is_active": true
      }
    ]
  }'
```

## 🔧 Gerenciamento via API

### Listar Webhooks de uma Instância
```bash
curl -X GET "http://localhost:8000/api/instances/1/webhooks" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Adicionar Novo Webhook
```bash
curl -X POST "http://localhost:8000/api/instances/1/webhooks" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "url": "https://meu-servidor.com/webhook",
    "events": ["message", "message.ack", "presence.update"],
    "is_active": true
  }'
```

### Atualizar Webhook Existente
```bash
curl -X PUT "http://localhost:8000/api/instances/1/webhooks/2" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "events": ["message", "message.ack", "session.status"],
    "is_active": false
  }'
```

### Deletar Webhook
```bash
curl -X DELETE "http://localhost:8000/api/instances/1/webhooks/2" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 📊 Exemplo de Resposta da API

### Listar Webhooks
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "instance_id": 1,
      "webhook_url": "https://webhook.site/11111111-1111-1111-1111-11111111",
      "events": ["message", "message.ack"],
      "is_active": true,
      "retry_count": 0,
      "last_retry_at": null,
      "created_at": "2025-01-29T10:30:00Z",
      "updated_at": "2025-01-29T10:30:00Z"
    },
    {
      "id": 2,
      "instance_id": 1,
      "webhook_url": "https://webhook.cool/22222222-2222-2222-2222-22222222",
      "events": ["session.status", "presence.update"],
      "is_active": true,
      "retry_count": 0,
      "last_retry_at": null,
      "created_at": "2025-01-29T10:30:00Z",
      "updated_at": "2025-01-29T10:30:00Z"
    }
  ],
  "total": 2
}
```

## 🔍 Debug dos Webhooks

### Verificar se Webhooks Estão Funcionando
```bash
# Verificar logs do worker
tail -f logs/app-$(date +%Y-%m-%d).log | grep "webhook"

# Verificar logs do nginx
tail -f logs/nginx-access.log | grep "webhook"
```

### Exemplo de Log de Sucesso
```json
{
  "message": "Webhook notification sent to client",
  "context": {
    "instance_id": "1",
    "webhook_id": "2",
    "webhook_url": "https://webhook.cool/22222222-2222-2222-2222-22222222",
    "event_type": "session.status",
    "status_code": 200
  }
}
```

## 🚨 Troubleshooting

### Webhook não recebe eventos
1. **Verificar se está ativo**: `is_active: true`
2. **Verificar eventos configurados**: Confirme se o tipo de evento está na lista
3. **Verificar logs**: Procure por erros nos logs
4. **Testar URL**: Use curl para testar se a URL responde

### Erro 500 nos webhooks
1. **Verificar logs do nginx**: `tail -f logs/nginx-error.log`
2. **Verificar logs da aplicação**: `tail -f logs/app-$(date +%Y-%m-%d).log`
3. **Verificar se o banco está funcionando**: Teste a conexão
4. **Verificar se a migração foi executada**: `php scripts/run-migration.php`

## 📝 Migração

### Executar Migração
```bash
php scripts/run-migration.php
```

### Verificar se Migração Funcionou
```sql
-- Conectar ao PostgreSQL e verificar
SELECT * FROM instance_webhooks;
SELECT COUNT(*) FROM instance_webhooks;
```

## ✅ Status da Implementação

- ✅ **Múltiplos webhooks por instância**
- ✅ **Filtros por tipo de evento**
- ✅ **API REST completa**
- ✅ **Compatibilidade com webhook legado**
- ✅ **Contador de tentativas**
- ✅ **Logs detalhados**
- ✅ **Migração automática**
- ✅ **Documentação completa**

## 🎯 Próximos Passos

1. **Execute a migração**: `php scripts/run-migration.php`
2. **Teste a criação de instância** com múltiplos webhooks
3. **Configure seus webhooks** de teste
4. **Monitore os logs** para verificar funcionamento
5. **Use a API** para gerenciar webhooks conforme necessário
