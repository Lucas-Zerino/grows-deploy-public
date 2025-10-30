# 🐘 pgAdmin - Interface Web do PostgreSQL

## 🎯 O que é o pgAdmin?

O pgAdmin é uma interface web completa para gerenciar bancos PostgreSQL. Com ele você pode:

- ✅ **Ver tabelas e dados** de forma visual
- ✅ **Executar queries SQL** com syntax highlighting
- ✅ **Monitorar performance** e estatísticas
- ✅ **Gerenciar usuários** e permissões
- ✅ **Ver logs** e atividades do banco
- ✅ **Exportar/Importar** dados

---

## 🚀 Como Iniciar o pgAdmin

### **Opção 1: Scripts Windows (Recomendado)**
```powershell
# Iniciar pgAdmin
.\scripts\windows\start-pgadmin.ps1

# Parar pgAdmin
.\scripts\windows\stop-pgadmin.ps1

# Ver logs
.\scripts\windows\logs-pgadmin.ps1
```

### **Opção 2: Makefile (Linux/Mac)**
```bash
# Iniciar pgAdmin
make pgadmin-up

# Parar pgAdmin
make pgadmin-down

# Ver logs
make pgadmin-logs
```

### **Opção 3: Docker Compose Direto**
```bash
# Iniciar
docker-compose -f docker-compose.pgadmin.yml up -d

# Parar
docker-compose -f docker-compose.pgadmin.yml down

# Ver logs
docker-compose -f docker-compose.pgadmin.yml logs -f
```

---

## 🌐 Acessando o pgAdmin

### **URL de Acesso:**
```
http://localhost:8080
```

### **Credenciais de Login:**
- **Email:** `admin@growhub.com`
- **Senha:** `Admin@123456`

---

## 🔗 Conectando ao PostgreSQL

### **1. Primeira Conexão**

Após fazer login, você verá uma tela para adicionar servidores:

1. **Clique em "Add New Server"**
2. **Na aba "General":**
   - **Name:** `GrowHub Development`
3. **Na aba "Connection":**
   - **Host name/address:** `growhub_postgres_dev`
   - **Port:** `5432`
   - **Maintenance database:** `growhub_gateway`
   - **Username:** `postgres`
   - **Password:** `postgres`
4. **Clique em "Save"**

### **2. Conexão Automática (Pré-configurada)**

O arquivo `docker/pgadmin/servers.json` já está configurado com as conexões:

- **GrowHub Development** → `growhub_postgres_dev`
- **GrowHub Production** → `growhub_postgres`

---

## 📊 Explorando o Banco de Dados

### **Estrutura Principal:**
```
growhub_gateway/
├── 📁 public/
│   ├── 📋 companies
│   ├── 📋 instances
│   ├── 📋 messages
│   ├── 📋 events
│   ├── 📋 logs          ← AQUI ESTÃO OS LOGS!
│   ├── 📋 outbox_messages
│   └── 📋 providers
```

### **Tabelas Importantes:**

#### **1. `logs` - Todos os Logs do Sistema**
```sql
-- Ver todos os logs
SELECT * FROM logs ORDER BY created_at DESC LIMIT 20;

-- Ver webhooks recebidos
SELECT * FROM logs 
WHERE message LIKE '%webhook%' 
ORDER BY created_at DESC LIMIT 10;

-- Ver erros
SELECT * FROM logs 
WHERE level IN ('ERROR', 'CRITICAL')
ORDER BY created_at DESC LIMIT 10;
```

#### **2. `messages` - Mensagens Enviadas/Recebidas**
```sql
-- Ver mensagens recentes
SELECT * FROM messages 
ORDER BY created_at DESC LIMIT 20;

-- Ver mensagens por instância
SELECT * FROM messages 
WHERE instance_id = 9 
ORDER BY created_at DESC LIMIT 10;
```

#### **3. `events` - Eventos do Sistema**
```sql
-- Ver eventos recentes
SELECT * FROM events 
ORDER BY created_at DESC LIMIT 20;

-- Ver eventos de webhook
SELECT * FROM events 
WHERE event_type LIKE '%webhook%'
ORDER BY created_at DESC LIMIT 10;
```

#### **4. `instances` - Instâncias WhatsApp**
```sql
-- Ver status das instâncias
SELECT id, name, status, provider, created_at 
FROM instances 
ORDER BY created_at DESC;
```

---

## 🔍 Queries Úteis para Debug

### **1. Ver Webhooks Recebidos**
```sql
SELECT 
    created_at,
    level,
    message,
    payload->>'event_type' as event_type,
    instance_id,
    company_id
FROM logs 
WHERE message LIKE '%webhook received%'
ORDER BY created_at DESC 
LIMIT 20;
```

### **2. Ver Eventos de Mensagem**
```sql
SELECT 
    created_at,
    level,
    message,
    payload->>'event' as event,
    payload->>'from' as from_number,
    payload->>'body' as message_body,
    instance_id
FROM logs 
WHERE payload->>'event' IN ('message', 'message.ack', 'message.any')
ORDER BY created_at DESC 
LIMIT 20;
```

### **3. Ver Status de Conexão**
```sql
SELECT 
    created_at,
    level,
    message,
    payload->>'state' as state,
    instance_id
FROM logs 
WHERE message LIKE '%state change%' 
   OR message LIKE '%connected%'
ORDER BY created_at DESC 
LIMIT 20;
```

### **4. Ver Erros de Webhook**
```sql
SELECT 
    created_at,
    level,
    message,
    payload,
    instance_id
FROM logs 
WHERE level IN ('ERROR', 'CRITICAL')
  AND (message LIKE '%webhook%' 
       OR message LIKE '%WAHA%' 
       OR message LIKE '%UAZAPI%')
ORDER BY created_at DESC 
LIMIT 20;
```

### **5. Estatísticas de Mensagens**
```sql
-- Mensagens por instância
SELECT 
    instance_id,
    COUNT(*) as total_messages,
    COUNT(CASE WHEN direction = 'outbound' THEN 1 END) as sent,
    COUNT(CASE WHEN direction = 'inbound' THEN 1 END) as received
FROM messages 
GROUP BY instance_id
ORDER BY total_messages DESC;
```

### **6. Logs por Nível**
```sql
-- Distribuição de logs por nível
SELECT 
    level,
    COUNT(*) as count,
    MAX(created_at) as last_occurrence
FROM logs 
GROUP BY level
ORDER BY count DESC;
```

---

## 🛠️ Funcionalidades Avançadas

### **1. Query Tool**
- **Acesse:** Clique com botão direito em qualquer tabela → "View/Edit Data" → "All Rows"
- **Execute:** Digite sua query SQL e pressione F5

### **2. Dashboard de Performance**
- **Acesse:** Clique com botão direito no servidor → "Dashboard"
- **Veja:** Estatísticas de conexões, queries lentas, etc.

### **3. Exportar Dados**
- **Acesse:** Clique com botão direito na tabela → "Import/Export Data"
- **Formatos:** CSV, JSON, SQL, etc.

### **4. Monitor de Atividade**
- **Acesse:** Tools → Server Activity
- **Veja:** Queries em execução, conexões ativas, etc.

---

## 🐛 Troubleshooting

### **Problema: pgAdmin não conecta**
```bash
# Verificar se PostgreSQL está rodando
docker ps | grep postgres

# Verificar logs do pgAdmin
docker-compose -f docker-compose.pgadmin.yml logs

# Reiniciar pgAdmin
docker-compose -f docker-compose.pgadmin.yml restart
```

### **Problema: Erro de rede**
```bash
# Verificar se a rede existe
docker network ls | grep growhub_network

# Se não existir, iniciar o ambiente principal primeiro
docker-compose -f docker-compose.dev.yml up -d
```

### **Problema: Senha incorreta**
- **PostgreSQL:** `postgres` / `postgres`
- **pgAdmin:** `admin@growhub.com` / `Admin@123456`

---

## 📚 Recursos Adicionais

### **Documentação Oficial:**
- [pgAdmin Documentation](https://www.pgadmin.org/docs/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

### **Comandos Úteis:**
```bash
# Ver status dos containers
docker ps

# Acessar shell do PostgreSQL
docker exec -it growhub_postgres_dev psql -U postgres -d growhub_gateway

# Ver logs em tempo real
docker-compose -f docker-compose.dev.yml logs -f postgres
```

---

**🎉 Agora você tem uma interface visual completa para explorar seus dados e logs!**
