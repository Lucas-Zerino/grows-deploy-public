# 🔐 Arquitetura de Tokens - GrowHub Gateway

## 📋 Resumo

O sistema agora possui **3 tipos de tokens** para autenticação:

| Tipo | Tabela | Uso | Endpoints |
|------|--------|-----|-----------|
| **Superadmin** | `admins.token` | Gestão global do sistema | `/api/admin/*` |
| **Empresa** | `companies.token` | Gerenciar instâncias da empresa | `/api/instances` (CRUD) |
| **Instância** | `instances.token` | Operar a instância (WhatsApp) | `/instance/*` (UAZAPI) |

---

## 🎯 Fluxo Completo

### 1. **Superadmin** cria Empresa

```bash
POST /api/admin/companies
Authorization: Bearer {admin_token}
Body: {"name": "Minha Empresa"}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Minha Empresa",
    "token": "abc-123-empresa",  # ← TOKEN DA EMPRESA
    "status": "active"
  }
}
```

### 2. **Empresa** cria Instância

```bash
POST /api/instances
Authorization: Bearer abc-123-empresa  # ← Token da empresa
Body: {
  "instance_name": "vendas",
  "phone_number": "5511999999999"
}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "company_id": 1,
    "instance_name": "vendas",
    "token": "xyz-456-instancia",  # ← TOKEN DA INSTÂNCIA
    "status": "creating",
    ...
  },
  "message": "Use the instance token for operations..."
}
```

### 3. **Instância** opera (WhatsApp)

```bash
# Conectar
POST /instance/connect
Authorization: Bearer xyz-456-instancia  # ← Token da instância
Body: {"phone": "5511999999999"}

# Status
GET /instance/status
Authorization: Bearer xyz-456-instancia

# Enviar mensagem
POST /message/text
Authorization: Bearer xyz-456-instancia
Body: {
  "phone": "5511888888888",
  "text": "Olá!"
}
```

---

## 🔒 Validação de Tokens

### AuthMiddleware - Ordem de Verificação

```php
// 1. Verifica se é Admin (superadmin/staff)
$admin = Admin::findByToken($token);
if ($admin) return ['type' => 'admin', ...];

// 2. Verifica se é Empresa
$company = Company::findByToken($token);
if ($company) return ['type' => 'company', ...];

// 3. Verifica se é Instância
$instance = Instance::findByToken($token);
if ($instance) return ['type' => 'instance', ...];

// 4. Token inválido
return null; // 401 Unauthorized
```

### Validações Aplicadas

#### ✅ **Validação de Tipo de Token**

```php
// Endpoints /instance/* SÓ aceitam token de instância
if ($auth['type'] !== 'instance') {
    return 401: "This endpoint requires an instance token"
}
```

#### ✅ **Validação de Status**

- **Admin:** Deve estar `active`
- **Company:** Deve estar `active`
- **Instance:** Não pode estar `deleted`

#### ✅ **Validação de Formato**

- Token vazio → `401: "Invalid token format"`
- Token < 10 chars → `401: "Invalid token format"`
- Placeholder `{{token}}` → `401: "Token placeholder not replaced"`

---

## 🗄️ Estrutura no Banco

### Tabela `admins`
```sql
id | name | email | token (UUID) | password | role | status
1  | Admin| admin@...| abc-admin   | hash    | su..| active
```

### Tabela `companies`
```sql
id | name       | token (UUID)    | status
1  | Empresa 1  | abc-company-123 | active
2  | Empresa 2  | def-company-456 | active
```

### Tabela `instances`
```sql
id | company_id | instance_name | token (UUID)       | external_id | status
1  | 1          | vendas        | xyz-instance-111   | vendas     | active
2  | 1          | suporte       | xyz-instance-222   | suporte    | active
3  | 2          | atendimento   | xyz-instance-333   | atend      | active
```

**Relação:**
- 1 Empresa → N Instâncias
- Cada Instância pertence a 1 Empresa (`company_id`)
- Cada Instância tem seu próprio `token` único

---

## 🔄 Endpoints por Tipo de Token

### 🔑 Token de Superadmin

```
GET    /api/admin/companies          - Listar empresas
POST   /api/admin/companies          - Criar empresa
PUT    /api/admin/companies/{id}     - Atualizar empresa
DELETE /api/admin/companies/{id}     - Deletar empresa

GET    /api/admin/providers          - Listar providers
POST   /api/admin/providers          - Criar provider
PUT    /api/admin/providers/{id}     - Atualizar provider
DELETE /api/admin/providers/{id}     - Deletar provider

GET    /api/admin/health              - Status do sistema
```

### 🏢 Token de Empresa

```
GET    /api/instances                 - Listar instâncias
POST   /api/instances                 - Criar instância (retorna token)
GET    /api/instances/{id}            - Detalhes da instância
DELETE /api/instances/{id}            - Deletar instância

GET    /api/messages                  - Histórico de mensagens
GET    /api/events                    - Eventos das instâncias
```

### 📱 Token de Instância (UAZAPI)

```
POST   /instance/connect              - Conectar ao WhatsApp
POST   /instance/disconnect           - Desconectar
GET    /instance/status               - Status da instância
POST   /instance/updateInstanceName   - Atualizar nome
DELETE /instance                       - Deletar

GET    /instance/privacy              - Buscar privacidade
POST   /instance/privacy              - Atualizar privacidade
POST   /instance/presence             - Atualizar presença

POST   /message/text                  - Enviar mensagem texto
POST   /message/media                 - Enviar mídia
POST   /message/location              - Enviar localização
...
```

---

## 💡 Casos de Uso

### ✅ Caso 1: Empresa com Múltiplas Instâncias

```
Empresa: Loja ABC (token: abc-loja)
├── Instância: vendas      (token: xyz-vendas)     - WhatsApp: +5511999990001
├── Instância: suporte     (token: xyz-suporte)    - WhatsApp: +5511999990002
└── Instância: financeiro  (token: xyz-financeiro) - WhatsApp: +5511999990003
```

**Criar todas:**
```bash
# Usar token da empresa para criar
curl POST /api/instances -H "Authorization: Bearer abc-loja" -d '{"instance_name":"vendas",...}'
curl POST /api/instances -H "Authorization: Bearer abc-loja" -d '{"instance_name":"suporte",...}'
curl POST /api/instances -H "Authorization: Bearer abc-loja" -d '{"instance_name":"financeiro",...}'
```

**Operar cada uma:**
```bash
# Cada instância usa SEU token
curl POST /instance/connect -H "Authorization: Bearer xyz-vendas"
curl POST /instance/connect -H "Authorization: Bearer xyz-suporte"
curl POST /instance/connect -H "Authorization: Bearer xyz-financeiro"
```

### ✅ Caso 2: Integração com App Cliente

**Backend do Cliente:**
```javascript
// Salva tokens no banco do cliente
const empresa = {
  id: 1,
  nome: "Loja ABC",
  growhub_token: "abc-loja"  // Token da empresa
};

const instancias = [
  { id: 1, nome: "vendas", growhub_token: "xyz-vendas" },
  { id: 2, nome: "suporte", growhub_token: "xyz-suporte" }
];

// Para criar nova instância
async function criarInstancia(nome) {
  const response = await fetch('http://gateway/api/instances', {
    headers: { 'Authorization': `Bearer ${empresa.growhub_token}` },
    body: JSON.stringify({ instance_name: nome })
  });
  
  const data = await response.json();
  // Salvar data.token da instância no banco
}

// Para enviar mensagem
async function enviarMensagem(instanciaId, phone, text) {
  const instancia = instancias.find(i => i.id === instanciaId);
  
  await fetch('http://gateway/message/text', {
    headers: { 'Authorization': `Bearer ${instancia.growhub_token}` },
    body: JSON.stringify({ phone, text })
  });
}
```

---

## 🔐 Segurança

### ✅ Tokens são UUID v4
```
Formato: 9ac4925b-548b-4676-94ed-ed72a18808cb
Bits de entropia: 122 bits
Impossível de adivinhar
```

### ✅ Índices no Banco
```sql
CREATE UNIQUE INDEX idx_admins_token ON admins(token);
CREATE UNIQUE INDEX idx_companies_token ON companies(token);
CREATE UNIQUE INDEX idx_instances_token ON instances(token);
```

### ✅ Validações
- Token não pode ser vazio
- Token não pode ser placeholder `{{token}}`
- Token deve ter no mínimo 10 caracteres
- Entity (admin/company/instance) deve estar ativa/válida

---

## 📊 Exemplo Real

**Setup:**
```bash
# 1. Superadmin cria empresa
POST /api/admin/companies
Auth: Bearer {admin_token}
→ Recebe: company_token

# 2. Empresa cria instância
POST /api/instances
Auth: Bearer {company_token}
→ Recebe: instance_token

# 3. Instância conecta
POST /instance/connect
Auth: Bearer {instance_token}
→ QR Code gerado

# 4. Instância envia mensagem
POST /message/text
Auth: Bearer {instance_token}
Body: {"phone": "...", "text": "..."}
→ Mensagem enviada
```

---

## 🎯 Vantagens desta Arquitetura

1. ✅ **Isolamento**: Cada instância tem seu próprio token
2. ✅ **Segurança**: Tokens separados por nível de acesso
3. ✅ **Flexibilidade**: Empresa pode ter N instâncias
4. ✅ **Rastreabilidade**: Logs sabem exatamente qual instância fez ação
5. ✅ **Escalabilidade**: Tokens independentes permitem distribuição
6. ✅ **Padrão UAZAPI**: Compatível com a especificação original

---

## 📝 Comandos Úteis

```bash
# Listar tokens ativos
psql -d growhub_gateway -c "SELECT token FROM companies WHERE status='active';"
psql -d growhub_gateway -c "SELECT i.instance_name, i.token, c.name FROM instances i JOIN companies c ON i.company_id=c.id;"

# Criar backup dos tokens
docker exec growhub_postgres_dev pg_dump -U postgres -t companies -t instances > tokens_backup.sql
```

---

## ✅ Conclusão

Sistema implementado com **3 níveis de autenticação**:
- **Superadmin** → Gestão global
- **Empresa** → Gestão de instâncias
- **Instância** → Operações WhatsApp

Todos os endpoints validam o tipo correto de token, garantindo segurança e isolamento! 🚀

