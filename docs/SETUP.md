# Setup do GrowHub Gateway

## 📋 Pré-requisitos

- PHP 8.1+
- Composer
- Docker e Docker Compose
- Git

## 🚀 Instalação Rápida

### 1. Clone e Configure

```bash
# Clone o repositório
git clone <url>
cd growhub

# Copie o arquivo de ambiente
cp env.example .env

# IMPORTANTE: Edite o .env e configure:
# - Credenciais do superadmin (SUPERADMIN_EMAIL, SUPERADMIN_PASSWORD)
# - Senhas do banco (DB_PASSWORD)
# - Senhas do RabbitMQ (RABBITMQ_PASSWORD)
vim .env
```

### 2. Instale Dependências

```bash
composer install
```

### 3. Inicie o Ambiente (Automático)

```bash
# Dá permissão aos scripts
chmod +x scripts/*.sh

# Roda setup completo
./scripts/start-dev.sh
```

Isso irá:
- ✅ Subir PostgreSQL e RabbitMQ
- ✅ Aplicar schema do banco
- ✅ Configurar RabbitMQ (exchanges e queues)
- ✅ Criar superadmin com as credenciais do .env

### 4. Inicie a API e Workers

```bash
# Terminal 1 - API
php -S localhost:8000 -t public

# Terminal 2 - Workers
./scripts/start-workers.sh

# Terminal 3 - Monitorar logs (opcional)
tail -f logs/app.log
```

## 🔐 Credenciais do Superadmin

Após rodar o setup, o superadmin será criado com as credenciais definidas no `.env`:

```env
SUPERADMIN_NAME=Admin
SUPERADMIN_EMAIL=admin@growhub.com
SUPERADMIN_PASSWORD=Admin@123456
```

### Como fazer login

**Opção 1 - Via API (Email/Senha):**

```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@growhub.com",
    "password": "Admin@123456"
  }'
```

Retorna:
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "admin": {
      "id": 1,
      "name": "Admin",
      "email": "admin@growhub.com",
      "token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
      "is_superadmin": true,
      "status": "active"
    },
    "token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
  }
}
```

**Opção 2 - Via Token (Direto):**

Use o token retornado ou visualize executando:

```bash
php scripts/seed-superadmin.php
```

Então use:

```bash
# Substitua {TOKEN} pelo token do superadmin
curl http://localhost:8000/api/admin/health \
  -H "Authorization: Bearer {TOKEN}"
```

## 📊 Verificar se está funcionando

### Acessar Interfaces

- **API**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672
  - Usuário: `admin`
  - Senha: `admin123`
- **PostgreSQL**: `localhost:5432`
  - Usuário: `postgres`
  - Senha: `postgres`

### Testar Endpoints

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@growhub.com","password":"Admin@123456"}' \
  | jq -r '.data.token')

# 2. Criar empresa
curl -X POST http://localhost:8000/api/admin/companies \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Minha Empresa"}'

# 3. Health check
curl http://localhost:8000/api/admin/health \
  -H "Authorization: Bearer $TOKEN"
```

## 🔧 Configuração Manual (Se não usar o script)

Se preferir fazer passo a passo:

```bash
# 1. Subir containers
docker-compose -f docker-compose.dev.yml up -d

# 2. Aguardar serviços (10-15s)
sleep 15

# 3. Aplicar schema
docker-compose -f docker-compose.dev.yml exec -T postgres \
  psql -U postgres -d growhub_gateway < database/schema.sql

# 4. Configurar RabbitMQ
php config/rabbitmq_setup.php

# 5. Criar superadmin
php scripts/seed-superadmin.php
```

## 🛠️ Comandos Úteis

```bash
# Ver status dos containers
docker-compose -f docker-compose.dev.yml ps

# Ver logs
docker-compose -f docker-compose.dev.yml logs -f

# Acessar PostgreSQL
docker-compose -f docker-compose.dev.yml exec postgres \
  psql -U postgres -d growhub_gateway

# Parar ambiente
docker-compose -f docker-compose.dev.yml down

# Parar e remover volumes (⚠️ apaga dados)
docker-compose -f docker-compose.dev.yml down -v
```

## 📝 Arquivo .env

Configurações importantes:

```env
# Superadmin (altere em produção!)
SUPERADMIN_NAME=Admin
SUPERADMIN_EMAIL=admin@growhub.com
SUPERADMIN_PASSWORD=SenhaSegura@123

# Banco de Dados
DB_HOST=localhost
DB_PORT=5432
DB_NAME=growhub_gateway
DB_USER=postgres
DB_PASSWORD=postgres

# RabbitMQ
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=admin123

# Segurança
PASSWORD_MIN_LENGTH=8
```

## ⚠️ Importante para Produção

Ao deployar em produção:

1. ✅ Altere TODAS as senhas
2. ✅ Use senhas fortes (mínimo 16 caracteres)
3. ✅ Configure HTTPS
4. ✅ Use `.env.prod` com credenciais seguras
5. ✅ Não commite arquivos .env no git
6. ✅ Use secrets management (Vault, AWS Secrets, etc)

## 🐛 Troubleshooting

### Superadmin já existe

Se rodar o seed novamente:

```bash
# Para recriar, delete primeiro
docker-compose -f docker-compose.dev.yml exec postgres \
  psql -U postgres -d growhub_gateway \
  -c "DELETE FROM admins WHERE email='admin@growhub.com'"

# Depois rode novamente
php scripts/seed-superadmin.php
```

### Erro de conexão com banco

```bash
# Verifique se PostgreSQL está rodando
docker-compose -f docker-compose.dev.yml ps postgres

# Veja os logs
docker-compose -f docker-compose.dev.yml logs postgres
```

### Erro de conexão com RabbitMQ

```bash
# Verifique se RabbitMQ está rodando
docker-compose -f docker-compose.dev.yml ps rabbitmq

# Teste conexão
docker-compose -f docker-compose.dev.yml exec rabbitmq \
  rabbitmq-diagnostics ping
```

## 📚 Próximos Passos

Após o setup:

1. Adicionar providers WAHA/UAZAPI
2. Criar empresas
3. Criar instâncias
4. Enviar mensagens de teste
5. Ver documentação completa no README.md

