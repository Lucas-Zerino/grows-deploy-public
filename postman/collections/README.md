# 📦 Coleções Postman - GrowHub Gateway

## 📂 Estrutura Modular

As rotas da API foram organizadas em **arquivos separados** para facilitar a manutenção:

```
postman/collections/
├── 01-auth.json                 # 🔐 Autenticação (Login, Perfil, Senha)
├── 02-companies.json            # 🏢 Empresas (CRUD)
├── 03-providers.json            # 🔌 Providers (WAHA, UAZAPI)
├── 04-instances-api.json        # 📱 Instâncias - API Empresa (CRUD)
├── 05-instance-uazapi.json      # 📲 Instance UAZAPI (Conectar, Status, etc)
├── 06-instances-superadmin.json # 🔧 Instâncias - Superadmin (Gerenciar)
├── 07-messages.json             # 💬 Mensagens
├── 08-events.json               # 📡 Eventos
└── 09-health.json               # 💚 Health & Monitoring
```

---

## 🚀 Como Usar

### Opção 1: Importar Todos no Postman (Recomendado)

1. Abra o Postman
2. Clique em **Import**
3. Selecione **a pasta** `postman/collections/`
4. O Postman vai importar todos os arquivos automaticamente
5. Você terá 9 pastas separadas! ✅

### Opção 2: Importar Individual

Importe apenas os arquivos que você precisa:
- Trabalhando com auth? → `01-auth.json`
- Testando instâncias? → `04-instances-api.json` + `05-instance-uazapi.json`

### Opção 3: Usar Arquivo Único (Legado)

Se preferir o arquivo único antigo:
```
postman/GrowHub-Gateway.postman_collection.json
```

**Nota**: O arquivo único será mantido por compatibilidade, mas pode ficar desatualizado.

---

## 🔑 Environment Variables

Importe também: `postman/GrowHub-Gateway.postman_environment.json`

**Variáveis:**
- `base_url`: http://localhost:8000
- `superadmin_token`: Preenchido automaticamente após login
- `superadmin_email`: admin@growhub.com
- `superadmin_password`: Admin@123456
- `company_token`: Preenchido ao criar empresa
- `company_id`: Preenchido ao criar empresa
- `instance_token`: ⭐ **NOVO** - Token da instância (para rotas UAZAPI)
- `instance_id`: ID da instância
- `instance_name`: Nome da instância
- `provider_id`: ID do provider
- `message_id`: ID da mensagem
- `event_id`: ID do evento

---

## 📊 Ordem de Teste Recomendada

### 1. **Setup Inicial** (01-auth.json)
```
1. Login → Salva superadmin_token
```

### 2. **Criar Empresa** (02-companies.json)
```
2. Criar Empresa → Salva company_token
```

### 3. **Criar Provider** (03-providers.json)
```
3. Criar Provider (ou listar o padrão)
```

### 4. **Criar Instância** (04-instances-api.json)
```
4. Criar Instância → Salva instance_token ⭐
```

### 5. **Operar Instância** (05-instance-uazapi.json)
```
5. Status (usa instance_token)
6. Conectar (usa instance_token)
7. Atualizar Presença (usa instance_token)
```

### 6. **Enviar Mensagens** (07-messages.json)
```
8. Enviar Mensagem
9. Listar Histórico
```

---

## 🔐 Diferença Entre Tokens

### ⚠️ IMPORTANTE: Entender os 3 tipos de token!

| Token | Uso | Rotas |
|-------|-----|-------|
| **Superadmin Token** | Gerenciar sistema | `/api/admin/*` |
| **Company Token** | Gerenciar instâncias | `/api/instances` (CRUD) |
| **Instance Token** | Operar WhatsApp | `/instance/*` (UAZAPI) |

### Exemplo Prático:

```
1. Criar instância:
   POST /api/instances
   Authorization: Bearer {company_token} ← Token da empresa
   
   Resposta:
   {
     "data": {
       "token": "xyz-123" ← Salvar este token!
     }
   }

2. Conectar instância:
   POST /instance/connect
   Authorization: Bearer xyz-123 ← Token da INSTÂNCIA
```

---

## 📝 Fluxo Completo de Teste

```bash
# 1. Login Superadmin (01-auth.json)
POST /api/admin/login
→ superadmin_token

# 2. Criar Empresa (02-companies.json)
POST /api/admin/companies
Authorization: Bearer {superadmin_token}
→ company_token

# 3. Criar Instância (04-instances-api.json)
POST /api/instances
Authorization: Bearer {company_token}
→ instance_token ⭐

# 4. Conectar ao WhatsApp (05-instance-uazapi.json)
POST /instance/connect
Authorization: Bearer {instance_token} ← Usar token da instância!
→ QR Code ou Pair Code

# 5. Verificar Status (05-instance-uazapi.json)
GET /instance/status
Authorization: Bearer {instance_token}
→ status: connected
```

---

## 🔧 Gerenciamento pelo Superadmin

O superadmin pode gerenciar TODAS as instâncias:

```
# Listar todas as instâncias do sistema
GET /api/admin/instances
Authorization: Bearer {superadmin_token}

# Listar instâncias de um provider
GET /api/admin/providers/{id}/instances
Authorization: Bearer {superadmin_token}

# Desconectar qualquer instância
POST /api/admin/instances/{providerId}/{externalInstanceId}/disconnect
Authorization: Bearer {superadmin_token}

# Deletar instância diretamente no provider
DELETE /api/admin/instances/{providerId}/{externalInstanceId}
Authorization: Bearer {superadmin_token}
```

---

## 🎯 Novas Rotas Adicionadas

### ✅ Gerenciamento de Instâncias (Superadmin)

| Método | Rota | Descrição | Arquivo |
|--------|------|-----------|---------|
| GET | `/api/admin/instances` | Listar todas as instâncias | 06 |
| GET | `/api/admin/providers/{id}/instances` | Instâncias do provider | 03 |
| POST | `/api/admin/instances/{providerId}/{externalInstanceId}/disconnect` | Desconectar instância | 06 |
| DELETE | `/api/admin/instances/{providerId}/{externalInstanceId}` | Deletar instância | 06 |

### ✅ Rotas UAZAPI (Token da Instância)

| Método | Rota | Token | Arquivo |
|--------|------|-------|---------|
| GET | `/instance/status` | instance_token | 05 |
| POST | `/instance/connect` | instance_token | 05 |
| POST | `/instance/disconnect` | instance_token | 05 |
| POST | `/instance/updateInstanceName` | instance_token | 05 |
| DELETE | `/instance` | instance_token | 05 |
| GET | `/instance/privacy` | instance_token | 05 |
| POST | `/instance/privacy` | instance_token | 05 |
| POST | `/instance/presence` | instance_token | 05 |

---

## 🧪 Testes Automatizados

Cada endpoint possui scripts que:
- ✅ Salvam tokens automaticamente
- ✅ Salvam IDs de recursos
- ✅ Facilitam o teste em cadeia

**Exemplo**: Após criar uma instância, o `instance_token` é salvo automaticamente!

---

## 📖 Documentação

Para mais detalhes sobre o sistema:
- `ARQUITETURA-TOKENS.md` - Explicação dos 3 tipos de token
- `TRADUCAO-UAZAPI-WAHA.md` - Como funciona a tradução entre APIs
- `README.md` - Documentação geral do projeto

---

## ✅ Conclusão

**Coleções modularizadas** facilitam:
- 📁 Organização por domínio
- 🔍 Encontrar rotas rapidamente
- ✏️ Manutenção individual
- 🎯 Importar apenas o necessário

**Total de endpoints:** 30+ rotas organizadas em 9 categorias! 🚀

