# 🔔 Sistema Completo de Eventos

## 🎯 Fluxo de Eventos

```
1. WhatsApp/WAHA → Evento acontece
   ↓
2. WAHA → POST /webhook/waha/{id}
   ↓
3. Backend → PROCESSA INTERNAMENTE
   ├─ Atualiza status da instância (se state.change)
   ├─ Atualiza status de mensagem (se message.ack)
   └─ Registra estatísticas
   ↓
4. Backend → TRADUZ para formato customizado
   ↓
5. RabbitMQ → Fila company.{id}.inbound
   ↓
6. Worker → Consome e envia para webhook do cliente
   ↓
7. Cliente → Recebe evento formatado!
```

---

## 📋 Eventos Capturados

### ✅ Mensagens

| Evento WAHA | Evento Cliente | Quando Acontece |
|-------------|----------------|-----------------|
| `message` | `on-message` | Nova mensagem recebida |
| `message.any` | `on-message` | Qualquer mensagem |
| `message.ack` | `on-message-sent`/`delivered`/`read` | Mensagem enviada/entregue/lida |

### ✅ Estado da Conexão

| Evento WAHA | Evento Cliente | Quando Acontece |
|-------------|----------------|-----------------|
| `state.change` | `on-connected`/`on-disconnected` | Conectou ou desconectou |
| `session.status` | `on-state-change` | Status da sessão mudou |

### ✅ Grupos

| Evento WAHA | Evento Cliente | Quando Acontece |
|-------------|----------------|-----------------|
| `group.join` | `on-group-join` | Entrou em grupo |
| `group.leave` | `on-group-leave` | Saiu do grupo |

### ✅ Outros

| Evento WAHA | Evento Cliente | Quando Acontece |
|-------------|----------------|-----------------|
| `presence.update` | `on-presence-update` | Alguém ficou online/offline |
| `poll.vote` | `on-poll-vote` | Voto em enquete |
| `call` | `on-call` | Chamada recebida |

---

## 🔄 Processamento Interno

### 1️⃣ State Change (Conexão/Desconexão)

**O que fazemos internamente:**
- ✅ Atualiza `instances.status` no banco de dados
- ✅ Registra log da mudança

**Exemplo:**
```
WAHA: state = "CONNECTED"
↓
Backend: UPDATE instances SET status = 'connected' WHERE id = 9
↓
Cliente: { "event": "on-connected", "status": "connected" }
```

**Estados mapeados:**
- `CONNECTED` / `WORKING` → `connected`
- `DISCONNECTED` / `STOPPED` / `FAILED` → `disconnected`
- `STARTING` / `SCAN_QR_CODE` → `connecting`

---

### 2️⃣ Message ACK (Enviada/Entregue/Lida)

**O que fazemos internamente:**
- ✅ Atualiza `messages.status` no banco (quando implementarmos external_message_id)
- ✅ Registra log do ACK

**Status do ACK:**
- `1` → `sent` (Enviada)
- `2` → `delivered` (Entregue)
- `3` → `read` (Lida)
- `4` → `played` (Reproduzida - áudio/vídeo)

**Webhook enviado ao cliente:**
```json
{
  "event": "on-message-read",  // ou sent, delivered, played
  "message_id": "msg_id",
  "status": "read",
  "ack": 3,
  "from": "5511999999999",
  "timestamp": 1760471539000,
  ...
}
```

---

### 3️⃣ Message Received (Mensagem Recebida)

**O que fazemos internamente:**
- ✅ Registra log da mensagem
- ✅ Contabiliza estatísticas (futuro)

**Webhook enviado ao cliente:**
```json
{
  "type": "text",
  "event": "on-message",
  "from": "5511999999999",
  "content": "Texto da mensagem",
  "pushName": "Nome",
  "timestamp": 1760471539000,
  ...
}
```

---

## 📊 Tabela de Eventos Completa

| # | Evento | Atualiza BD? | Envia para Cliente? | Formato |
|---|--------|--------------|---------------------|---------|
| 1 | message | ✅ Log | ✅ Sim | on-message (customizado) |
| 2 | message.ack | ⏳ Futuro | ✅ Sim | on-message-sent/delivered/read |
| 3 | state.change | ✅ Status instância | ✅ Sim | on-connected/disconnected |
| 4 | session.status | ✅ Status instância | ✅ Sim | on-state-change |
| 5 | group.join | ❌ Não | ✅ Sim | on-group-join |
| 6 | group.leave | ❌ Não | ✅ Sim | on-group-leave |
| 7 | presence.update | ❌ Não | ✅ Sim | on-presence-update |
| 8 | poll.vote | ❌ Não | ✅ Sim | on-poll-vote |
| 9 | call | ❌ Não | ✅ Sim | on-call |

---

## 🧪 Testar Cada Evento

### Teste 1: Mensagem (on-message)

1. Envie mensagem de texto no WhatsApp
2. Verifique webhook.site
3. Deve ter: `"event": "on-message"`, `"type": "text"`

### Teste 2: Conexão (on-connected)

1. Desconecte a instância: `POST /instance/disconnect`
2. Reconecte: `POST /instance/authenticate`
3. Escaneie QR code
4. Verifique:
   - Banco: `SELECT status FROM instances WHERE id = 9` → `connected`
   - Webhook.site: `"event": "on-connected"`

### Teste 3: Desconexão (on-disconnected)

1. Desconecte: `POST /instance/disconnect`
2. Verifique:
   - Banco: status = `disconnected`
   - Webhook.site: `"event": "on-disconnected"`

### Teste 4: Mensagem Lida (on-message-read)

1. Envie mensagem para o WhatsApp conectado
2. **Leia a mensagem** no WhatsApp
3. Verifique webhook.site: `"event": "on-message-read"`, `"ack": 3`

### Teste 5: Mensagem Entregue (on-message-delivered)

1. Envie mensagem
2. Mensagem é entregue (dois checks)
3. Webhook.site: `"event": "on-message-delivered"`, `"ack": 2`

---

## 🔍 Verificar Processamento Interno

### Ver se Status Atualiza

```sql
-- Execute no banco
SELECT id, instance_name, status, updated_at 
FROM instances 
WHERE id = 9;
```

Após conectar/desconectar, `status` e `updated_at` devem mudar!

### Ver Logs de Atualização

```powershell
.\scripts\windows\logs-api.ps1
```

Procure por:
```
[INFO] Instance status updated {
  "instance_id": 9,
  "old_status": "connecting",
  "new_status": "connected",
  "waha_state": "CONNECTED"
}
```

---

## 📚 Estrutura de Filas

### Filas Dinâmicas (Por Empresa)

```
company.1.inbound    → Eventos da empresa 1
company.1.outbound   → Mensagens para enviar (empresa 1)

company.2.inbound    → Eventos da empresa 2
company.2.outbound   → Mensagens para enviar (empresa 2)
```

### Filas Globais

```
outbox.processor     → Processa OutboxDB pattern
health.check         → Health checks de providers
queue.manager        → Gerenciamento dinâmico de filas
dlq.final            → Dead Letter Queue (mensagens que falharam)
```

### Exchanges

```
messaging.inbound.exchange   → Eventos recebidos (webhooks)
messaging.outbound.exchange  → Mensagens para enviar
events.exchange              → Eventos do sistema
retry.exchange               → Retry de mensagens
dlq.exchange                 → Dead letter queue
```

---

## 🎯 Eventos que Você Precisa

### ✅ Já Implementados

1. ✅ **on-message** - Mensagem recebida (texto, áudio, imagem, etc)
2. ✅ **on-message-sent** - Mensagem enviada (ACK 1)
3. ✅ **on-message-delivered** - Mensagem entregue (ACK 2)
4. ✅ **on-message-read** - Mensagem lida (ACK 3)
5. ✅ **on-connected** - Instância conectou
6. ✅ **on-disconnected** - Instância desconectou

### ⏳ Eventos Registrados mas não Formatados

7. ⏳ **on-group-join** - Entrou em grupo
8. ⏳ **on-group-leave** - Saiu do grupo
9. ⏳ **on-presence-update** - Alguém ficou online/offline
10. ⏳ **on-poll-vote** - Voto em enquete
11. ⏳ **on-call** - Chamada recebida

---

## 🚀 Próximos Passos

### 1. Reiniciar API e Workers

```powershell
.\scripts\windows\restart-api.ps1
docker-compose -f docker-compose.dev.yml restart worker-inbound
```

### 2. Testar Conexão

```http
# Desconectar
POST /instance/disconnect

# Ver banco
SELECT status FROM instances WHERE id = 9;
-- Deve ser: disconnected

# Reconectar
POST /instance/authenticate
{ "method": "qrcode" }

# Escanear QR
# Ver banco novamente - deve mudar para: connected
```

### 3. Testar ACKs

```http
# Enviar mensagem
POST /api/messages/send
{
  "phone_to": "5511999999999",
  "message_type": "text",
  "content": "Teste"
}

# Aguardar webhooks:
# 1. on-message-sent (enviada)
# 2. on-message-delivered (entregue)
# 3. on-message-read (quando ler no WhatsApp)
```

---

## 📝 Resumo

### O que Temos Agora

1. ✅ **3 Filas por empresa:**
   - Inbound (eventos recebidos)
   - Outbound (mensagens para enviar)
   - Priority queues (alta/normal/baixa)

2. ✅ **Processamento interno antes de enviar:**
   - Atualiza status da instância
   - Atualiza status de mensagens (futuro)
   - Registra logs

3. ✅ **11 tipos de eventos capturados:**
   - Mensagens (recebida, ACK)
   - Estado (conectado, desconectado)
   - Grupos
   - Presença
   - Chamadas
   - Enquetes

4. ✅ **Formato customizado:**
   - Cada evento no formato que você precisa
   - Campos consistentes
   - Metadados completos

---

**REINICIE E TESTE AGORA!** 🚀

