# 🔔 Verificação Completa de Webhooks

## 🎯 Dois Pontos de Verificação

### 1️⃣ Webhook chega no nosso backend?
### 2️⃣ Webhook é enviado para o cliente?

---

## 🧪 Teste 1: Webhook Chega no Backend?

### Passo 1: Ver Logs em Tempo Real

```powershell
# Windows
.\scripts\windows\logs-api.ps1

# Linux/Mac
make dev-logs-api
```

### Passo 2: Enviar Webhook de Teste

```powershell
# Windows
.\scripts\windows\test-webhook.ps1 -InstanceId 9

# Linux/Mac (manual)
curl -X POST http://localhost:8000/webhook/waha/9 \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "payload": {
      "id": "test123",
      "from": "5511999999999@c.us",
      "body": "Teste",
      "fromMe": false
    }
  }'
```

### Passo 3: Verificar Logs

Procure por:
```
[INFO] WAHA webhook received {
  "instance_id": "9",
  "event_type": "message"
}

[INFO] WAHA webhook processed and sent to queue {
  "instance_id": "9",
  "company_id": "2",
  "routing_key": "company.2"
}
```

✅ **Se aparecer:** Webhook está chegando no backend!

---

## 📨 Teste 2: Webhook é Enviado para Cliente?

### Verificar Fila RabbitMQ

**Passo 1: Acessar RabbitMQ Management**
```
URL: http://localhost:15672
Login: admin / admin123
```

**Passo 2: Ir em Queues**
Procure pela fila: `company.2.inbound`

**Passo 3: Ver Mensagens**
- Se tiver mensagens na fila, **eventos estão sendo recebidos** ✅
- Clique em "Get messages" para ver o conteúdo

### Verificar Worker

**Passo 1: Ver se worker está rodando**
```powershell
# Windows
docker-compose -f docker-compose.dev.yml ps worker-inbound

# Linux/Mac  
make ps
```

**Passo 2: Ver logs do worker**
```powershell
# Windows
docker-compose -f docker-compose.dev.yml logs -f worker-inbound

# Linux/Mac
docker-compose -f docker-compose.dev.yml logs -f worker-inbound
```

Procure por:
```
[INFO] Processing inbound message
[INFO] Forwarding to webhook: https://webhook.site/...
```

---

## 🔍 Fluxo Completo do Webhook

```
1. WhatsApp → 2. WAHA → 3. GrowHub → 4. RabbitMQ → 5. Worker → 6. Cliente
              webhook    backend      fila          processa    webhook
```

### Checkpoint 1: WAHA → GrowHub
**URL:** `https://seu-ngrok.app/webhook/waha/9`

**Como testar:**
```bash
curl -X POST https://seu-ngrok.app/webhook/waha/9 \
  -H "Content-Type: application/json" \
  -d '{"event": "message", "payload": {...}}'
```

**Logs esperados:**
```
[INFO] WAHA webhook received
```

### Checkpoint 2: GrowHub → RabbitMQ
**Verificar:** Fila `company.2.inbound` no RabbitMQ

**Como verificar:**
1. http://localhost:15672
2. Queues → `company.2.inbound`
3. Ver se tem mensagens

### Checkpoint 3: RabbitMQ → Worker → Cliente
**Verificar:** Logs do worker + webhook.site

**Worker logs esperados:**
```
[INFO] Processing inbound message
[INFO] Forwarding to webhook
```

**Webhook.site esperado:**
- Deve aparecer POST com os dados do evento

---

## 🐛 Problemas Comuns

### 1. Webhook não chega no backend

**Sintomas:**
- Logs não mostram "WAHA webhook received"
- Sem mensagens em RabbitMQ

**Causas possíveis:**
1. **Ngrok não está rodando**
2. **URL do webhook mudou**
3. **Instância não está conectada**

**Debug:**
```powershell
# Testar webhook local
.\scripts\windows\test-webhook.ps1 -InstanceId 9

# Ver logs
.\scripts\windows\logs-api.ps1
```

**Se teste local funcionar mas WAHA não:**
- Problema está na URL do ngrok
- Recrie a instância com nova URL

---

### 2. Webhook chega mas não vai para fila

**Sintomas:**
- Logs mostram "WAHA webhook received"
- Mas não mostra "sent to queue"
- Sem mensagens em RabbitMQ

**Causa:** Erro ao processar webhook

**Debug:**
```powershell
# Ver logs completos
.\scripts\windows\logs-api.ps1

# Procurar por [ERROR] próximo ao webhook
```

---

### 3. Webhook na fila mas não é enviado ao cliente

**Sintomas:**
- Mensagens acumulando em RabbitMQ
- Worker não processa

**Causas:**
1. **Worker não está rodando**
2. **Worker com erro**
3. **URL do cliente inválida**

**Debug:**
```powershell
# Ver se worker está rodando
docker-compose -f docker-compose.dev.yml ps worker-inbound

# Ver logs do worker
docker-compose -f docker-compose.dev.yml logs -f worker-inbound

# Reiniciar worker
docker-compose -f docker-compose.dev.yml restart worker-inbound
```

---

### 4. QR Code Raw não funciona

**Problema identificado:** O endpoint `format=raw` retorna null

**Causa:** WAHA pode estar retornando formato diferente

**Solução temporária:** Use `format=image` que funciona!

**Vou investigar e corrigir o formato raw...**

---

## 📊 Verificação Rápida

Execute este checklist:

```powershell
# 1. Testar webhook local
.\scripts\windows\test-webhook.ps1 -InstanceId 9

# 2. Ver logs (em outro terminal)
.\scripts\windows\logs-api.ps1

# 3. Verificar RabbitMQ
# Abrir: http://localhost:15672
# Ir em: Queues → company.2.inbound

# 4. Ver logs do worker
docker-compose -f docker-compose.dev.yml logs -f worker-inbound
```

---

Deixa eu corrigir os problemas identificados agora! 🔧

<function_calls>
<invoke name="read_file">
<parameter name="target_file">workers/event_processor_worker.php
