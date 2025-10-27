# GrowHub Gateway - Exemplos de API

## 🔐 Autenticação do Superadmin

### Login
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "admin@growhub.com",
    "password": "Admin@123456"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "id": 1,
    "name": "Admin",
    "email": "admin@growhub.com",
    "token": "dev-superadmin-token-123",
    "is_superadmin": true
  }
}
```

### Ver dados do admin logado
```bash
curl -X GET http://localhost:8000/api/admin/me \
  -H 'Authorization: Bearer dev-superadmin-token-123'
```

### Trocar senha
```bash
curl -X POST http://localhost:8000/api/admin/change-password \
  -H 'Authorization: Bearer dev-superadmin-token-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "current_password": "Admin@123456",
    "new_password": "NovaAdmin@987654"
  }'
```

## 🏢 Gerenciamento de Empresas (Superadmin)

### Criar Empresa
```bash
curl -X POST http://localhost:8000/api/admin/companies \
  -H 'Authorization: Bearer dev-superadmin-token-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Minha Empresa LTDA"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "message": "Empresa criada com sucesso",
  "data": {
    "id": 1,
    "name": "Minha Empresa LTDA",
    "token": "uuid-gerado-automaticamente",
    "status": "active",
    "created_at": "2025-10-13 19:00:00",
    "updated_at": "2025-10-13 19:00:00"
  }
}
```

> **⚡ Ao criar empresa, as filas RabbitMQ são criadas automaticamente e os workers começam a processar!**

### Listar Todas as Empresas
```bash
curl -X GET http://localhost:8000/api/admin/companies \
  -H 'Authorization: Bearer dev-superadmin-token-123'
```

### Buscar Empresa Específica
```bash
curl -X GET http://localhost:8000/api/admin/companies/1 \
  -H 'Authorization: Bearer dev-superadmin-token-123'
```

### Atualizar Empresa
```bash
curl -X PUT http://localhost:8000/api/admin/companies/1 \
  -H 'Authorization: Bearer dev-superadmin-token-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Novo Nome da Empresa",
    "status": "active"
  }'
```

### Deletar Empresa (Soft Delete)
```bash
curl -X DELETE http://localhost:8000/api/admin/companies/1 \
  -H 'Authorization: Bearer dev-superadmin-token-123'
```

## 📦 Gerenciamento de Providers (Superadmin)

### Criar Provider (WAHA/UAZAPI)
```bash
curl -X POST http://localhost:8000/api/admin/providers \
  -H 'Authorization: Bearer dev-superadmin-token-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "waha",
    "name": "Servidor WAHA 01",
    "base_url": "http://waha-server:3000",
    "api_key": null,
    "max_instances": 50,
    "is_active": true
  }'
```

### Listar Providers
```bash
curl -X GET http://localhost:8000/api/admin/providers \
  -H 'Authorization: Bearer dev-superadmin-token-123'
```

## 📱 API da Empresa (Usando Token da Empresa)

> **Importante**: Use o token gerado ao criar a empresa!

### Criar Instância de WhatsApp
```bash
curl -X POST http://localhost:8000/api/instances \
  -H 'Authorization: Bearer {COMPANY_TOKEN}' \
  -H 'Content-Type: application/json' \
  -d '{
    "instance_name": "vendas",
    "phone_number": "5511999999999",
    "webhook_url": "https://meuapp.com/webhook"
  }'
```

### Listar Instâncias da Empresa
```bash
curl -X GET http://localhost:8000/api/instances \
  -H 'Authorization: Bearer {COMPANY_TOKEN}'
```

### Enviar Mensagem
```bash
curl -X POST http://localhost:8000/api/messages/send \
  -H 'Authorization: Bearer {COMPANY_TOKEN}' \
  -H 'Content-Type: application/json' \
  -d '{
    "instance_id": 1,
    "phone_to": "5511888888888",
    "message_type": "text",
    "content": "Olá! Esta é uma mensagem de teste.",
    "priority": "normal"
  }'
```

### Ver Histórico de Mensagens
```bash
curl -X GET http://localhost:8000/api/messages \
  -H 'Authorization: Bearer {COMPANY_TOKEN}'
```

### Ver Eventos (read, delivered, etc)
```bash
curl -X GET http://localhost:8000/api/events \
  -H 'Authorization: Bearer {COMPANY_TOKEN}'
```

## 🔄 Fluxo Completo de Uso

### 1. Login como Superadmin
```bash
SUPERADMIN_TOKEN=$(curl -X POST http://localhost:8000/api/admin/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@growhub.com","password":"Admin@123456"}' | jq -r '.data.token')

echo "Superadmin Token: $SUPERADMIN_TOKEN"
```

### 2. Criar uma Empresa
```bash
COMPANY_TOKEN=$(curl -X POST http://localhost:8000/api/admin/companies \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"name":"Minha Empresa"}' | jq -r '.data.token')

echo "Company Token: $COMPANY_TOKEN"
```

### 3. Criar um Provider (opcional, se não tiver)
```bash
curl -X POST http://localhost:8000/api/admin/providers \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "type":"waha",
    "name":"WAHA Local",
    "base_url":"http://localhost:3000",
    "max_instances":10
  }'
```

### 4. Criar Instância (como empresa)
```bash
curl -X POST http://localhost:8000/api/instances \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "instance_name":"vendas",
    "phone_number":"5511999999999"
  }'
```

### 5. Enviar Mensagem
```bash
curl -X POST http://localhost:8000/api/messages/send \
  -H "Authorization: Bearer $COMPANY_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "instance_id":1,
    "phone_to":"5511888888888",
    "message_type":"text",
    "content":"Olá!"
  }'
```

## ⚙️ Monitoramento

### Health Check
```bash
curl http://localhost:8000/health
```

### RabbitMQ Management UI
Acesse: http://localhost:15672
- User: `admin`
- Password: `admin123`

### Logs
```bash
# Ver logs da API
docker compose -f docker-compose.dev.yml logs -f php-fpm

# Ver logs dos workers
docker compose -f docker-compose.dev.yml logs -f worker-outbound
docker compose -f docker-compose.dev.yml logs -f worker-inbound
```

## 📊 Arquitetura das Filas

Quando você cria uma empresa, são criadas automaticamente:

1. **Filas de Saída (Outbound):**
   - `outbound.company.{id}.priority.high`
   - `outbound.company.{id}.priority.normal`
   - `outbound.company.{id}.priority.low`

2. **Fila de Entrada (Inbound):**
   - `inbound.company.{id}`

3. **Fila de Eventos:**
   - `events.company.{id}`

Os workers consomem dessas filas via exchange automaticamente! 🚀

