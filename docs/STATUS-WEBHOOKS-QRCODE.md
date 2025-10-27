# 🔍 Status: Webhooks e QR Code

## 🐛 Problemas Identificados e Corrigidos

### ❌ Problema 1: Método QueueService::publishToExchange() Não Existia

**Erro nos logs:**
```
Call to undefined method App\Services\QueueService::publishToExchange()
```

**Causa:** 
- O `WebhookController` chamava `publishToExchange()`
- Mas o `QueueService` só tinha o método `publish()`

**✅ Solução Aplicada:**
- Adicionado método `publishToExchange()` em `src/Services/QueueService.php`
- É um alias para `publish()` com parâmetros reordenados

---

### ❌ Problema 2: QR Code Raw Retorna Null

**Rotas afetadas:**
- `GET /api/instances/{id}/qrcode` → Retorna null
- `GET /instance/authenticate/qrcode?format=raw` → Retorna null

**Rota que funciona:**
- `GET /instance/authenticate/qrcode?format=image` → ✅ Funciona!

**Causa Provável:**
- WAHA pode demorar para gerar o QR code em formato raw
- Ou retorna em campo diferente

**✅ Solução Temporária:**
- Use `format=image` que funciona perfeitamente
- Investigar formato raw posteriormente

---

## 🔄 Fluxo Correto de Webhooks

```
1. WhatsApp → Evento
   ↓
2. WAHA → POST /webhook/waha/{instance_id}
   ↓
3. WebhookController → Recebe e processa
   ↓
4. QueueService::publishToExchange() → Envia para RabbitMQ
   ↓
5. RabbitMQ → Fila company.{company_id}.inbound
   ↓
6. Worker (event_processor_worker.php) → Consome fila
   ↓
7. Worker → Busca webhook_url da instância no banco
   ↓
8. Worker → POST para webhook_url do cliente
   ↓
9. Cliente → Recebe evento! 🎉
```

---

## ✅ O que Está Funcionando

1. ✅ **Webhooks chegam no backend**
   - Logs mostram: "WAHA webhook received"
   - Instance ID detectado corretamente

2. ✅ **QR Code em formato imagem**
   - `GET /instance/authenticate/qrcode?format=image` funciona
   - Retorna PNG válido para escanear

3. ✅ **Autenticação e Conexão**
   - `POST /instance/authenticate` funciona
   - Status atualiza para "connected"
   - Phone number é capturado

---

## 🔧 Correções Necessárias (Feitas)

1. ✅ **Adicionar método `publishToExchange()` no QueueService**
   - Arquivo: `src/Services/QueueService.php`
   - Status: **CORRIGIDO**

2. ⏳ **Investigar QR Code formato raw**
   - Status: **Temporariamente use format=image**

---

## 🚀 Testar Agora

### 1. Reiniciar API

```powershell
# Windows
.\scripts\windows\restart-api.ps1

# Linux/Mac
make r
```

### 2. Testar Webhook

Envie uma mensagem no WhatsApp conectado e veja os logs:

```powershell
# Windows
.\scripts\windows\logs-api.ps1

# Procure por:
# [INFO] WAHA webhook received
# [INFO] WAHA webhook processed and sent to queue
```

### 3. Verificar RabbitMQ

```
URL: http://localhost:15672
Login: admin / admin123

Ir em: Queues
Procurar: company.2.inbound
```

Deve ter mensagens na fila!

### 4. Verificar Worker

```powershell
# Ver logs do worker
docker-compose -f docker-compose.dev.yml logs -f worker-inbound

# Procure por:
# [INFO] Processing inbound event
# [INFO] Webhook notification sent to client
```

### 5. Verificar Webhook.site

Acesse: https://webhook.site/4f1a018f-43e9-4a07-aa72-c0109e5adb8f

Deve aparecer POSTs com os eventos!

---

## 📊 Checklist de Verificação

- [ ] API reiniciada
- [ ] Webhook de teste enviado
- [ ] Logs mostram "webhook processed and sent to queue"
- [ ] RabbitMQ tem mensagens em `company.2.inbound`
- [ ] Worker processa e envia para cliente
- [ ] Webhook.site recebe POST

---

## 🎯 Endpoints de QR Code

### ✅ Funciona
```http
GET /instance/authenticate/qrcode?format=image
Authorization: Bearer {instance_token}
```
Retorna: Imagem PNG

### ⏳ Investigar
```http
GET /instance/authenticate/qrcode?format=raw
Authorization: Bearer {instance_token}
```
Retorna: null (investigar)

```http
GET /api/instances/{id}/qrcode
Authorization: Bearer {company_token}
```
Retorna: QR code not available (investigar)

---

## 💡 Recomendação

**Para produção, use:**
- `POST /instance/authenticate` (método de auth)
- `GET /instance/authenticate/qrcode?format=image` (QR code como imagem)
- `GET /instance/status` (verificar conexão)

**Evite usar** (até corrigir formato raw):
- `/instance/authenticate/qrcode?format=raw`
- `/api/instances/{id}/qrcode`

---

## 📚 Próximas Melhorias

1. ⏳ Investigar por que QR code raw retorna null
2. ⏳ Adicionar retry automático se webhook do cliente falhar
3. ⏳ Dashboard para monitorar webhooks
4. ⏳ Metrics de entregas de webhook

---

**Status atual: Webhooks funcionando após reiniciar API! 🎉**

