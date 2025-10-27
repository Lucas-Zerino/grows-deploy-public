# 🔔 Como Verificar se os Webhooks Estão Funcionando

## 🔧 Passo 1: Reiniciar a API (aplicar correção)

```bash
make restart-api
```

Aguarde 5 segundos e tente o status novamente:

```http
GET /instance/status
Authorization: Bearer {instance_token}
```

Agora você deve receber:
```json
{
  "id": "1-vendas",
  "name": "vendas",
  "token": "...",
  "status": "connecting",  ⬅️ NOVO!
  "qrcode": "2@..."       ⬅️ NOVO! (se disponível)
}
```

## 📡 Passo 2: Verificar se Webhooks Estão Chegando

### Opção 1: Ver Logs em Tempo Real

```bash
make dev-logs-api
```

Quando um webhook chegar, você verá logs como:

```
[INFO] WAHA webhook received {
  "instance_id": "7",
  "event_type": "state.change"
}

[INFO] WAHA webhook processed and sent to queue {
  "instance_id": "7",
  "company_id": "1",
  "routing_key": "company.1"
}
```

### Opção 2: Ver Logs do Arquivo

```bash
# Windows PowerShell
Get-Content logs/app-2025-10-14.log -Wait -Tail 50

# Linux/Mac
tail -f logs/app-2025-10-14.log
```

Ou veja direto no arquivo: `logs/app-2025-10-14.log`

### Opção 3: Acessar RabbitMQ Management

1. Abra: http://localhost:15672
2. Login: `admin` / `admin123`
3. Vá em **Queues**
4. Procure pela fila: `company.1.inbound`
5. Se tiver mensagens, os webhooks estão chegando! 📥

## 🧪 Testar Webhooks Manualmente

### Simular Webhook WAHA

```http
POST http://localhost:8000/webhook/waha/7
Content-Type: application/json

{
  "event": "state.change",
  "payload": {
    "state": "CONNECTED"
  }
}
```

### Simular Webhook de Mensagem

```http
POST http://localhost:8000/webhook/waha/7
Content-Type: application/json

{
  "event": "message",
  "payload": {
    "id": "msg123",
    "from": "5511999999999@c.us",
    "body": "Teste de mensagem",
    "timestamp": 1697300000,
    "fromMe": false
  }
}
```

## 📊 Eventos que Você Receberá

Sua instância está configurada para receber estes eventos:

### 1. `state.change` - Mudança de Estado
```json
{
  "event": "state.change",
  "payload": {
    "state": "CONNECTED"  // ou DISCONNECTED, STARTING, etc
  }
}
```

**Quando acontece:**
- Quando conectar (escanear QR code)
- Quando desconectar
- Quando reconectar

### 2. `message` ou `message.any` - Nova Mensagem
```json
{
  "event": "message",
  "payload": {
    "id": "msg123",
    "from": "5511999999999@c.us",
    "body": "Olá!",
    "timestamp": 1697300000,
    "fromMe": false
  }
}
```

**Quando acontece:**
- Toda vez que receber uma mensagem

### 3. `group.join` - Entrou em Grupo
```json
{
  "event": "group.join",
  "payload": {
    "groupId": "123@g.us",
    "participants": ["5511999999999@c.us"]
  }
}
```

### 4. `group.leave` - Saiu de Grupo
```json
{
  "event": "group.leave",
  "payload": {
    "groupId": "123@g.us",
    "participants": ["5511999999999@c.us"]
  }
}
```

### 5. `presence.update` - Mudança de Presença
```json
{
  "event": "presence.update",
  "payload": {
    "from": "5511999999999@c.us",
    "presence": "available"  // ou unavailable
  }
}
```

## 🔍 Debug de Webhooks

### Verificar URL do Webhook

Sua instância está configurada com:
```
https://6bce4996f62c.ngrok-free.app/webhook/waha/7
```

⚠️ **Importante:** Se você reiniciar o ngrok, essa URL muda! Você precisaria recriar a instância com a nova URL.

### Testar se o Ngrok Está Funcionando

```bash
# Teste externo (de fora do seu computador)
curl https://6bce4996f62c.ngrok-free.app/health
```

Deve retornar:
```json
{
  "data": {
    "status": "healthy",
    "timestamp": "2025-10-14 16:00:00"
  }
}
```

### Verificar Worker de Eventos

O worker processa os eventos da fila. Veja se está rodando:

```bash
make ps
```

Deve aparecer:
```
worker-inbound     running
```

Ver logs do worker:
```bash
docker-compose -f docker-compose.dev.yml logs -f worker-inbound
```

## 🎯 Checklist Completo

- [ ] API reiniciada (`make restart-api`)
- [ ] Status retorna campo `status` e `qrcode`
- [ ] QR code escaneado no celular
- [ ] Status mudou para `"connected"`
- [ ] Logs mostram "WAHA webhook received"
- [ ] RabbitMQ mostra mensagens na fila `company.1.inbound`
- [ ] Worker processa as mensagens

## 📝 Exemplo de Fluxo Completo

1. **Criar instância** → `POST /api/instances`
2. **Conectar** → `POST /instance/connect`
3. **Ver QR code** → `GET /instance/status`
4. **Escanear QR no celular** 📱
5. **WAHA envia webhook** → `POST /webhook/waha/7` (automático)
6. **Ver log:** `"WAHA webhook received", "event_type": "state.change"`
7. **Verificar fila RabbitMQ:** mensagem em `company.1.inbound`
8. **Worker processa:** evento disponível para sua aplicação

## 🚀 Próximos Passos

Depois que confirmar que os webhooks estão chegando:

1. **Consumir eventos:** Conecte sua aplicação na fila `company.1.inbound`
2. **Processar mensagens:** Implemente lógica de resposta automática
3. **Enviar mensagens:** Use `POST /api/messages/send`

## 🆘 Problemas Comuns

### Webhook não chega

**Causa 1:** Ngrok não está rodando
- **Solução:** Reinicie o ngrok e recrie a instância

**Causa 2:** Worker não está rodando
- **Solução:** `docker-compose -f docker-compose.dev.yml restart worker-inbound`

**Causa 3:** Instância não está conectada
- **Solução:** Verifique `GET /instance/status` → deve estar `"connected"`

### Mensagem fica presa na fila

**Causa:** Worker não está consumindo
- **Solução:** Ver logs do worker e reiniciá-lo

### Webhook chega mas não processa

**Causa:** Erro no processamento
- **Solução:** Ver logs: `make dev-logs-api` e procurar por ERRO

