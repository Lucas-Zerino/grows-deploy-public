# 🔗 Suporte a Múltiplos Webhooks

## 📋 Visão Geral

O sistema agora suporta **múltiplos webhooks por instância**, permitindo que você configure diferentes URLs para receber diferentes tipos de eventos.

## ✨ Funcionalidades

- ✅ **Múltiplos webhooks por instância**
- ✅ **Filtros por tipo de evento**
- ✅ **Controle de ativação/desativação**
- ✅ **Contador de tentativas de entrega**
- ✅ **Compatibilidade com webhook legado**
- ✅ **API REST completa para gerenciamento**

## 🚀 Como Usar

### 1. Criar Instância com Múltiplos Webhooks

```bash
curl -X POST "http://localhost:8000/api/instances" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
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

### 2. Gerenciar Webhooks via API

#### Listar Webhooks de uma Instância
```bash
curl -X GET "http://localhost:8000/api/instances/1/webhooks" \
  -H "Authorization: Bearer SEU_TOKEN"
```

#### Adicionar Novo Webhook
```bash
curl -X POST "http://localhost:8000/api/instances/1/webhooks" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "url": "https://meu-servidor.com/webhook",
    "events": ["message", "message.ack", "presence.update"],
    "is_active": true
  }'
```

#### Atualizar Webhook
```bash
curl -X PUT "http://localhost:8000/api/instances/1/webhooks/2" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "events": ["message", "message.ack", "session.status"],
    "is_active": false
  }'
```

#### Deletar Webhook
```bash
curl -X DELETE "http://localhost:8000/api/instances/1/webhooks/2" \
  -H "Authorization: Bearer SEU_TOKEN"
```

## 📊 Tipos de Eventos Suportados

### Eventos de Mensagem
- `message` - Mensagens recebidas
- `message.any` - Todas as mensagens
- `message.ack` - Confirmações de entrega/leitura
- `message.reaction` - Reações
- `message.revoked` - Mensagens revogadas
- `message.edited` - Mensagens editadas

### Eventos de Sessão
- `session.status` - Status da sessão
- `state.change` - Mudanças de estado

### Eventos de Presença
- `presence.update` - Atualizações de presença

### Eventos de Grupo
- `group.v2.join` - Entrou em grupo
- `group.v2.leave` - Saiu do grupo
- `group.v2.update` - Grupo atualizado
- `group.v2.participants` - Participantes alterados

### Eventos de Enquete
- `poll.vote` - Votos em enquetes
- `poll.vote.failed` - Falha no voto

### Eventos de Chat
- `chat.archive` - Chat arquivado

### Eventos de Chamada
- `call.received` - Chamada recebida
- `call.accepted` - Chamada aceita
- `call.rejected` - Chamada rejeitada

### Eventos de Label
- `label.upsert` - Label criada/atualizada
- `label.deleted` - Label deletada
- `label.chat.added` - Label adicionada ao chat
- `label.chat.deleted` - Label removida do chat

### Eventos de Sistema
- `event.response` - Resposta do evento
- `event.response.failed` - Falha na resposta
- `engine.event` - Evento interno

## 🔧 Configuração Avançada

### Webhook com Headers Customizados
```json
{
  "url": "https://meu-servidor.com/webhook",
  "events": ["message", "message.ack"],
  "is_active": true,
  "customHeaders": {
    "X-API-Key": "minha-chave-secreta",
    "X-Custom-Header": "valor-customizado"
  }
}
```

### Webhook com HMAC
```json
{
  "url": "https://meu-servidor.com/webhook",
  "events": ["message"],
  "is_active": true,
  "hmac": "sha256",
  "retries": 3
}
```

## 📈 Monitoramento

### Logs de Webhook
O sistema registra logs detalhados para cada webhook:

```json
{
  "message": "Webhook notification sent to client",
  "context": {
    "instance_id": "1",
    "webhook_id": "2",
    "webhook_url": "https://meu-servidor.com/webhook",
    "event_type": "message",
    "status_code": 200
  }
}
```

### Contador de Tentativas
- Cada webhook mantém um contador de tentativas de entrega
- Em caso de falha, o contador é incrementado
- Em caso de sucesso, o contador é resetado

## 🔄 Compatibilidade

### Webhook Legado
- O campo `webhook_url` na tabela `instances` continua funcionando
- É automaticamente incluído como webhook legado
- Recebe todos os tipos de eventos por padrão

### Migração
- Execute a migração: `php scripts/run-migration.php`
- Webhooks existentes são migrados automaticamente
- Nenhuma perda de funcionalidade

## 🚨 Troubleshooting

### Webhook não está recebendo eventos
1. Verifique se o webhook está ativo (`is_active: true`)
2. Confirme se o tipo de evento está configurado
3. Verifique os logs para erros de entrega

### Erro 500 nos webhooks
1. Verifique se a URL do webhook está acessível
2. Confirme se o servidor está retornando 200 OK
3. Verifique os logs do nginx e da aplicação

### Muitas tentativas de entrega
1. Verifique se o servidor de destino está funcionando
2. Considere desativar temporariamente o webhook
3. Verifique se há rate limiting no servidor de destino

## 📝 Exemplos Completos

### Exemplo 1: Sistema de Notificações
```json
{
  "webhooks": [
    {
      "url": "https://notificacoes.com/webhook/mensagens",
      "events": ["message", "message.ack"],
      "is_active": true
    },
    {
      "url": "https://notificacoes.com/webhook/status",
      "events": ["session.status", "presence.update"],
      "is_active": true
    }
  ]
}
```

### Exemplo 2: Sistema de Analytics
```json
{
  "webhooks": [
    {
      "url": "https://analytics.com/webhook/todos-eventos",
      "events": ["message", "message.ack", "session.status", "presence.update", "group.v2.join", "group.v2.leave"],
      "is_active": true
    }
  ]
}
```

### Exemplo 3: Sistema de Backup
```json
{
  "webhooks": [
    {
      "url": "https://backup.com/webhook/mensagens",
      "events": ["message", "message.any"],
      "is_active": true
    },
    {
      "url": "https://backup.com/webhook/status",
      "events": ["session.status"],
      "is_active": true
    }
  ]
}
```
