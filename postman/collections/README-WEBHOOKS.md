# 🔗 Collection: Instance Webhooks

## 📋 Visão Geral

Esta coleção contém todos os endpoints para gerenciar **múltiplos webhooks por instância** no GrowHub Gateway.

## 🚀 Endpoints Disponíveis

### 1. **Listar Webhooks da Instância**
- **Método**: `GET`
- **URL**: `/api/instances/{id}/webhooks`
- **Descrição**: Lista todos os webhooks configurados para uma instância específica

### 2. **Criar Webhook para Instância**
- **Método**: `POST`
- **URL**: `/api/instances/{id}/webhooks`
- **Descrição**: Cria um novo webhook para uma instância específica

### 3. **Atualizar Webhook - Eventos**
- **Método**: `PUT`
- **URL**: `/api/instances/{instanceId}/webhooks/{webhookId}`
- **Descrição**: Atualiza apenas os eventos de um webhook existente

### 4. **Atualizar Webhook - Ativar/Desativar**
- **Método**: `PUT`
- **URL**: `/api/instances/{instanceId}/webhooks/{webhookId}`
- **Descrição**: Ativa ou desativa um webhook existente

### 5. **Atualizar Webhook - URL**
- **Método**: `PUT`
- **URL**: `/api/instances/{instanceId}/webhooks/{webhookId}`
- **Descrição**: Atualiza a URL de um webhook existente

### 6. **Atualizar Webhook - Completo**
- **Método**: `PUT`
- **URL**: `/api/instances/{instanceId}/webhooks/{webhookId}`
- **Descrição**: Atualiza todos os campos de um webhook (URL, eventos e status)

### 7. **Deletar Webhook**
- **Método**: `DELETE`
- **URL**: `/api/instances/{instanceId}/webhooks/{webhookId}`
- **Descrição**: Remove um webhook de uma instância

### 8. **Criar Instância com Múltiplos Webhooks**
- **Método**: `POST`
- **URL**: `/api/instances`
- **Descrição**: Cria uma nova instância com múltiplos webhooks configurados

### 9. **Exemplo - Webhook com Headers Customizados**
- **Método**: `POST`
- **URL**: `/api/instances/{id}/webhooks`
- **Descrição**: Exemplo de criação de webhook com headers customizados (para referência)

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

## 🔧 Variáveis de Ambiente

Configure as seguintes variáveis no Postman:

- `base_url`: `http://localhost:8000` (ou sua URL do servidor)
- `company_token`: Seu token de empresa
- `instance_id`: ID da instância (ex: `1`)
- `webhook_id`: ID do webhook (ex: `1`)

## 📝 Exemplos de Uso

### Criar Webhook Simples
```json
{
  "url": "https://webhook.site/11111111-1111-1111-1111-11111111",
  "events": ["message", "message.ack"],
  "is_active": true
}
```

### Criar Webhook Completo
```json
{
  "url": "https://meu-servidor.com/webhook",
  "events": ["message", "message.ack", "session.status", "presence.update"],
  "is_active": true
}
```

### Atualizar Apenas Eventos
```json
{
  "events": ["message", "message.ack", "session.status", "presence.update"]
}
```

### Ativar/Desativar Webhook
```json
{
  "is_active": false
}
```

### Atualizar URL
```json
{
  "url": "https://nova-url.com/webhook/atualizado"
}
```

### Atualização Completa
```json
{
  "url": "https://webhook-final.com/endpoint",
  "events": ["message", "message.ack", "session.status", "presence.update", "group.v2.join"],
  "is_active": true
}
```

## 🚨 Códigos de Resposta

- **200 OK**: Operação realizada com sucesso
- **201 Created**: Webhook criado com sucesso
- **400 Bad Request**: Dados inválidos
- **401 Unauthorized**: Token inválido
- **404 Not Found**: Instância ou webhook não encontrado
- **500 Internal Server Error**: Erro interno do servidor

## 🔍 Monitoramento

### Logs de Sucesso
```json
{
  "message": "Webhook notification sent to client",
  "context": {
    "instance_id": "1",
    "webhook_id": "2",
    "webhook_url": "https://webhook.cool/22222222-2222-2222-2222-22222222",
    "event_type": "message",
    "status_code": 200
  }
}
```

### Logs de Erro
```json
{
  "message": "Failed to send webhook notification to client",
  "context": {
    "instance_id": "1",
    "webhook_id": "2",
    "webhook_url": "https://webhook.cool/22222222-2222-2222-2222-22222222",
    "event_type": "message",
    "error": "Connection timeout"
  }
}
```

## 📋 Checklist de Testes

- [ ] Listar webhooks de uma instância
- [ ] Criar webhook com eventos básicos
- [ ] Criar webhook com múltiplos eventos
- [ ] Atualizar eventos de um webhook
- [ ] Ativar/desativar webhook
- [ ] Atualizar URL de webhook
- [ ] Atualização completa de webhook
- [ ] Deletar webhook
- [ ] Criar instância com múltiplos webhooks
- [ ] Verificar logs de entrega
- [ ] Testar com diferentes tipos de eventos

## 🎯 Próximos Passos

1. **Configure as variáveis** de ambiente no Postman
2. **Execute a migração** do banco: `php scripts/run-migration.php`
3. **Teste a criação** de uma instância com webhooks
4. **Configure seus webhooks** de teste
5. **Monitore os logs** para verificar funcionamento
6. **Use os endpoints** para gerenciar webhooks conforme necessário

## 📚 Documentação Relacionada

- [Múltiplos Webhooks - Documentação Completa](../../docs/MULTIPLE-WEBHOOKS.md)
- [Exemplos de Uso](../../examples/multiple-webhooks-examples.md)
- [API de Instâncias](../04-instances.json)
- [Eventos do Sistema](../12-events.json)
