# 🐛 Debug: QR Code não Aparece

## 📋 Checklist de Verificação

### 1. Reiniciar API (aplicar correções)

```powershell
.\scripts\windows\restart-api.ps1
```

### 2. Ver Logs em Tempo Real

Em outro terminal:

```powershell
.\scripts\windows\logs-api.ps1
```

### 3. Fluxo Completo de Teste

Execute os comandos **nesta ordem**, aguardando entre cada um:

#### Passo 1: Conectar a Instância

```http
POST http://localhost:8000/instance/connect
Authorization: Bearer {instance_token}
Content-Type: application/json

{}
```

**Aguarde 5 segundos!** ⏳

#### Passo 2: Ver Status da Sessão

```http
GET http://localhost:8000/instance/status
Authorization: Bearer {instance_token}
```

**Verifique nos logs:**
```
[DEBUG] WAHA session status {
  "waha_status": "SCAN_QR_CODE",  ← Deve ser isso!
  "mapped_status": "connecting"
}
```

#### Passo 3: Tentar Pegar QR Code

Se `waha_status` for `SCAN_QR_CODE`, o QR code deve estar disponível.

Se não aparecer, **aguarde mais 3-5 segundos** e tente novamente.

---

## 🔍 Possíveis Status da WAHA

| Status WAHA     | Tem QR Code? | O que fazer                          |
|-----------------|--------------|--------------------------------------|
| `STOPPED`       | ❌           | Chamar `/instance/connect`           |
| `STARTING`      | ⏳           | Aguardar 3-5s e tentar novamente     |
| `SCAN_QR_CODE`  | ✅           | QR code está disponível!             |
| `WORKING`       | ❌           | Já está conectado, não precisa QR    |
| `FAILED`        | ❌           | Deletar e recriar instância          |

---

## 🧪 Teste Manual Direto na WAHA

Para confirmar que a WAHA está funcionando:

```http
GET http://192.168.11.160:3000/api/sessions/1-vendas
X-Api-Key: {sua_api_key_se_tiver}
```

**Resposta esperada:**
```json
{
  "name": "1-vendas",
  "status": "SCAN_QR_CODE",  ← Confirme este status
  ...
}
```

Se status for `SCAN_QR_CODE`, tente pegar o QR code direto:

```http
GET http://192.168.11.160:3000/api/1-vendas/auth/qr
X-Api-Key: {sua_api_key_se_tiver}
```

**Resposta esperada:**
```json
{
  "value": "2@...long string..."  ← Este é o QR code!
}
```

---

## 🔴 Se o Status Ficar Preso em "STARTING"

Isso significa que a sessão está demorando para iniciar. Pode ser:

1. **WAHA sobrecarregada** - Aguarde mais tempo
2. **Problema na WAHA** - Reinicie a WAHA
3. **Sessão corrompida** - Delete e recrie

### Solução: Reiniciar WAHA

```bash
docker-compose -f docker-compose.dev.yml restart waha

# ou se a WAHA está rodando separadamente
docker restart waha_container_name
```

---

## 🔴 Se o Status for "FAILED"

A sessão falhou. **Solução: Deletar e recriar**

```http
# 1. Deletar
DELETE http://localhost:8000/api/instances/{id}
Authorization: Bearer {company_token}

# 2. Recriar
POST http://localhost:8000/api/instances
Authorization: Bearer {company_token}
{
  "instance_name": "vendas"
}

# 3. Conectar (com NOVO instance_token)
POST http://localhost:8000/instance/connect
Authorization: Bearer {novo_instance_token}
{}

# 4. Aguardar 5 segundos

# 5. Pegar status
GET http://localhost:8000/instance/status
Authorization: Bearer {novo_instance_token}
```

---

## 📊 Logs Importantes

Nos logs, procure por:

### ✅ Logs de Sucesso

```
[INFO] WAHA instance created
[INFO] WAHA session started
[DEBUG] WAHA session status { "waha_status": "SCAN_QR_CODE" }
```

### ❌ Logs de Erro

```
[ERROR] WAHA get QR code failed
```

Se ver este erro, copie a mensagem completa e veja:
- Status code: 404, 422, 500?
- Mensagem de erro da WAHA

---

## 🛠️ Verificar Versão da WAHA

Diferentes versões da WAHA podem ter endpoints diferentes.

```http
GET http://192.168.11.160:3000/api/server/version
```

**Versões conhecidas:**
- WAHA 2023.x: `/api/{session}/auth/qr`
- WAHA 2024.x: `/api/{session}/auth/qr` (mesmo endpoint)

---

## 🎯 Solução Alternativa: QR Code como Imagem

Se nada funcionar, a WAHA também aceita retornar QR como imagem:

```http
GET http://192.168.11.160:3000/api/1-vendas/auth/qr?format=image
Accept: image/png
```

Isso retorna a imagem PNG do QR code diretamente.

---

## 📝 Checklist Final

- [ ] API reiniciada após correções
- [ ] Logs abertos em outro terminal
- [ ] Chamado `/instance/connect`
- [ ] Aguardado 5 segundos
- [ ] Status retorna `"connecting"`
- [ ] Logs mostram `waha_status: "SCAN_QR_CODE"`
- [ ] QR code aparece no response

Se todos os passos acima estiverem OK e ainda não funcionar, o problema pode estar na WAHA em si, não no nosso código.

