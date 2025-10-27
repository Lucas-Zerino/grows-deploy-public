# 🆘 Troubleshooting - Solucionando Problemas

## 🔴 Status "FAILED" na Instância

### Sintomas
- Status da WAHA retorna `"FAILED"`
- Status da API retorna `"disconnected"`
- QR code não está disponível
- `engine.gows.connected: false`

### Causa
A sessão na WAHA falhou ao iniciar, geralmente por:
- Resíduos de sessão anterior
- Tentativa de conexão que falhou
- Problema temporário na WAHA

### ✅ Solução 1: Deletar e Recriar (Recomendado)

```bash
# 1. Deletar instância pela API (usando company_token)
DELETE /api/instances/{id}
Authorization: Bearer {company_token}

# 2. Recriar instância
POST /api/instances
Authorization: Bearer {company_token}
{
  "instance_name": "vendas"
}

# 3. Conectar (usando o NOVO instance_token)
POST /instance/connect
Authorization: Bearer {novo_instance_token}
{}

# 4. Pegar QR code
GET /instance/status
Authorization: Bearer {novo_instance_token}
```

### ✅ Solução 2: Forçar Restart na WAHA

Se a solução 1 não funcionar, pode ser necessário deletar diretamente na WAHA:

```bash
# Via RabbitMQ Management ou diretamente na WAHA API
DELETE http://sua-waha:3000/api/sessions/1-vendas
X-Api-Key: {sua_api_key}
```

Depois recrie pela nossa API.

---

## 🔴 QR Code Não Aparece

### Sintomas
- Status retorna `"connecting"` mas sem QR code
- Campo `qrcode` vem `null`

### Solução

Aguarde alguns segundos (2-5s) e tente novamente. O QR code demora um pouco para ser gerado.

```bash
# Continue tentando até aparecer
GET /instance/status
Authorization: Bearer {instance_token}

# Aguarde 3 segundos entre cada tentativa
```

Se após 30 segundos ainda não aparecer:
1. Desconecte: `POST /instance/disconnect`
2. Conecte novamente: `POST /instance/connect`

---

## 🔴 Webhooks Não Chegam

### Sintomas
- Instância conectada mas webhooks não chegam
- Logs não mostram "WAHA webhook received"

### Diagnóstico

**1. Verificar se instância está conectada**
```http
GET /instance/status
Authorization: Bearer {instance_token}

# Deve retornar: "status": "connected"
```

**2. Ver logs da API**
```powershell
# Windows
.\scripts\windows\logs-webhooks.ps1

# Linux/Mac
make dev-logs-webhooks
```

**3. Verificar RabbitMQ**
- Acesse: http://localhost:15672
- Login: admin/admin123
- Vá em **Queues**
- Procure: `company.{sua_company_id}.inbound`

**4. Testar webhook manualmente**
```http
POST http://localhost:8000/webhook/waha/{instance_id}
Content-Type: application/json

{
  "event": "message",
  "payload": {
    "id": "test123",
    "from": "5511999999999@c.us",
    "body": "Teste",
    "fromMe": false
  }
}
```

Se o teste manual funcionar mas webhooks reais não chegam:

### Possíveis Causas

**Causa 1: Ngrok URL mudou**

Se você reiniciou o ngrok, a URL mudou. Você precisa:
1. Deletar a instância antiga
2. Recriar com a nova URL do ngrok

**Causa 2: Webhook não foi registrado**

Verifique se o webhook foi registrado ao criar a instância:
```json
{
  "config": {
    "webhooks": [
      {
        "url": "https://seu-ngrok.ngrok-free.app/webhook/waha/7"
      }
    ]
  }
}
```

Se não tiver, recrie a instância.

**Causa 3: Worker não está rodando**

```bash
# Ver status dos workers
docker-compose -f docker-compose.dev.yml ps worker-inbound

# Reiniciar worker
docker-compose -f docker-compose.dev.yml restart worker-inbound

# Ver logs do worker
docker-compose -f docker-compose.dev.yml logs -f worker-inbound
```

---

## 🔴 Erro "Instance not found"

### Sintomas
- Ao chamar endpoints com instance_token
- Retorna 404 "Instance not found"

### Causa
Você está usando o **token errado** ou a instância foi deletada.

### Solução

**1. Verificar se instância existe**
```http
GET /api/instances
Authorization: Bearer {company_token}
```

**2. Confirmar que está usando instance_token**

Para endpoints `/instance/*`, você DEVE usar o `instance_token`, não o `company_token`:

```http
✅ CORRETO:
POST /instance/connect
Authorization: Bearer inst_abc123...

❌ ERRADO:
POST /instance/connect
Authorization: Bearer comp_xyz...
```

---

## 🔴 Instância Conecta mas Desconecta Sozinha

### Sintomas
- Conecta com sucesso
- Depois de alguns minutos/horas desconecta
- Precisa escanear QR code novamente

### Causas Possíveis

1. **WhatsApp deslogou remotamente**
   - Isso pode acontecer se você:
     - Fez logout no celular
     - Deletou WhatsApp do celular
     - Trocou de celular

2. **Container PHP reiniciou**
   - Verifica se o container está rodando:
   ```bash
   docker-compose -f docker-compose.dev.yml ps
   ```

3. **Sessão expirou na WAHA**
   - A WAHA pode ter reiniciado
   - Dados de sessão foram perdidos

### Solução

**Para produção (evitar desconexões):**
1. Use volumes persistentes para dados da WAHA
2. Configure health check adequado
3. Monitore status da instância periodicamente

---

## 🔴 Erro 409 "Nome duplicado"

### Sintomas
```json
{
  "error": "Já existe uma instância com este nome. Por favor, escolha outro nome."
}
```

### Causa
Você já tem uma instância com esse nome na sua empresa.

### Solução

**Opção 1:** Use outro nome
```json
{
  "instance_name": "vendas-2"  // ou vendas_novo, etc
}
```

**Opção 2:** Delete a instância antiga primeiro
```http
DELETE /api/instances/{id_da_antiga}
Authorization: Bearer {company_token}
```

---

## 🔴 Rate Limit Exceeded

### Sintomas
```json
{
  "error": "Rate limit exceeded. Try again later."
}
```

### Causa
Você fez muitas requisições em pouco tempo.

### Solução
Aguarde alguns segundos/minutos e tente novamente.

Para desenvolvimento, você pode ajustar os limites em:
- `src/Middleware/RateLimitMiddleware.php`

---

## 🔴 Container não Inicia

### Sintomas
- `docker-compose up -d` falha
- Container fica reiniciando
- Erro de porta em uso

### Diagnóstico

```bash
# Ver logs do container
docker-compose -f docker-compose.dev.yml logs php-fpm

# Ver containers rodando
docker ps -a
```

### Soluções Comuns

**Porta já em uso (8000)**
```bash
# Windows - ver o que está usando a porta 8000
netstat -ano | findstr :8000

# Matar o processo
taskkill /PID {numero_do_pid} /F

# Ou mudar a porta no docker-compose.dev.yml
ports:
  - "8001:80"  # muda de 8000 para 8001
```

**Banco de dados não responde**
```bash
# Reiniciar só o banco
docker-compose -f docker-compose.dev.yml restart postgres

# Ver logs do banco
docker-compose -f docker-compose.dev.yml logs postgres
```

---

## 🔴 Provider Não Disponível

### Sintomas
```json
{
  "error": "No available provider at the moment"
}
```

### Causa
- Nenhum provider configurado
- Todos providers atingiram limite de instâncias
- Provider está inativo

### Solução

**1. Verificar providers**
```http
GET /api/admin/providers
Authorization: Bearer {admin_token}
```

**2. Criar provider (como superadmin)**
```http
POST /api/admin/providers
Authorization: Bearer {admin_token}
{
  "name": "WAHA Local",
  "type": "waha",
  "base_url": "http://waha:3000",
  "api_key": null,
  "max_instances": 100
}
```

**3. Ativar provider inativo**
```http
PUT /api/admin/providers/{id}
Authorization: Bearer {admin_token}
{
  "is_active": true
}
```

---

## 🛠️ Ferramentas de Debug

### Ver todos os logs
```bash
# Ver tudo em tempo real
docker-compose -f docker-compose.dev.yml logs -f

# Ver só PHP
docker-compose -f docker-compose.dev.yml logs -f php-fpm

# Ver arquivo de log
cat logs/app-2025-10-14.log
```

### Acessar shell do container
```bash
docker-compose -f docker-compose.dev.yml exec php-fpm sh

# Dentro do container, você pode:
# - Ver arquivos: ls -la
# - Ver processos: ps aux
# - Testar conectividade: ping waha
```

### Verificar RabbitMQ
```bash
# Via web: http://localhost:15672
# Login: admin/admin123

# Via CLI
docker-compose -f docker-compose.dev.yml exec rabbitmq rabbitmqctl list_queues
```

### Verificar Banco de Dados
```bash
docker-compose -f docker-compose.dev.yml exec postgres psql -U postgres -d growhub_gateway

# Dentro do psql:
\dt                    # listar tabelas
SELECT * FROM instances;
SELECT * FROM companies;
```

---

## 📞 Precisa de Mais Ajuda?

1. Ver logs completos: `docker-compose -f docker-compose.dev.yml logs`
2. Verificar documentação: pasta `docs/`
3. Issues conhecidos: ver próximas seções deste guia

