# Desenvolvimento com Docker - GrowHub Gateway

## 🎯 Visão Geral

Todo o ambiente de desenvolvimento roda em Docker, incluindo:
- ✅ PHP-FPM 8.1
- ✅ Nginx
- ✅ PostgreSQL 16
- ✅ RabbitMQ 3.12
- ✅ 4 Workers (background)
- ✅ Hot-reload do código (bind mount)

## 🚀 Setup Inicial

### Pré-requisitos

- Docker Desktop instalado
- Docker Compose v2+
- Git

### Opção 1: Setup Automático (Recomendado)

```bash
# Tudo em um comando
make quick-start

# Ou execute o script diretamente
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh
```

### Opção 2: Setup Manual

```bash
# 1. Criar arquivo .env
cp env.example .env
# Edite .env se necessário

# 2. Subir containers
docker-compose -f docker-compose.dev.yml up -d --build

# 3. Aguardar serviços (15-20s)
sleep 20

# 4. Configurar RabbitMQ
docker-compose -f docker-compose.dev.yml exec php-fpm php config/rabbitmq_setup.php

# 5. Criar superadmin
docker-compose -f docker-compose.dev.yml exec php-fpm php scripts/seed-superadmin.php

# 6. Criar dados de teste (opcional)
docker-compose -f docker-compose.dev.yml exec php-fpm php scripts/create-test-data.php
```

## 📦 Containers

O ambiente possui os seguintes containers:

| Container | Serviço | Porta | Descrição |
|-----------|---------|-------|-----------|
| `growhub_postgres_dev` | PostgreSQL | 5432 | Banco de dados |
| `growhub_rabbitmq_dev` | RabbitMQ | 5672, 15672 | Message broker |
| `growhub_php_dev` | PHP-FPM | 9000 | Processa PHP |
| `growhub_nginx_dev` | Nginx | 8000 | Web server |
| `growhub_worker_outbound_dev` | Worker | - | Envia mensagens |
| `growhub_worker_inbound_dev` | Worker | - | Processa eventos |
| `growhub_worker_outbox_dev` | Worker | - | OutboxDB pattern |
| `growhub_worker_health_dev` | Worker | - | Health checks |

## 🌐 Acessar Serviços

- **API Gateway**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672
  - Usuário: `admin`
  - Senha: `admin123`
- **PostgreSQL**: `localhost:5432`
  - Usuário: `postgres`
  - Senha: `postgres`
  - Database: `growhub_gateway`

## 🔧 Comandos Úteis (Makefile)

```bash
# Subir ambiente
make dev-up

# Parar ambiente
make dev-down

# Reiniciar
make dev-restart

# Ver logs (todos)
make dev-logs

# Ver logs da API
make dev-logs-api

# Ver logs dos workers
make dev-logs-workers

# Acessar shell PHP
make dev-shell

# Acessar shell do banco
make dev-shell-db

# Rebuild das imagens
make dev-rebuild

# Ver status dos containers
make dev-ps
```

## 🔧 Comandos Úteis (Docker Compose)

```bash
# Subir todos os containers
docker-compose -f docker-compose.dev.yml up -d

# Parar todos os containers
docker-compose -f docker-compose.dev.yml down

# Ver logs em tempo real
docker-compose -f docker-compose.dev.yml logs -f

# Ver logs de um serviço específico
docker-compose -f docker-compose.dev.yml logs -f php-fpm
docker-compose -f docker-compose.dev.yml logs -f worker-outbound

# Reiniciar um serviço
docker-compose -f docker-compose.dev.yml restart nginx

# Acessar shell de um container
docker-compose -f docker-compose.dev.yml exec php-fpm sh
docker-compose -f docker-compose.dev.yml exec postgres sh

# Ver status dos containers
docker-compose -f docker-compose.dev.yml ps

# Rebuild de um serviço
docker-compose -f docker-compose.dev.yml build php-fpm

# Rebuild completo (sem cache)
docker-compose -f docker-compose.dev.yml build --no-cache
```

## 🔄 Hot Reload

O código local está montado nos containers via bind mount:
```yaml
volumes:
  - .:/var/www/html
```

**Isso significa:**
- ✅ Edições no código refletem imediatamente
- ✅ Não precisa rebuild para mudanças em PHP
- ✅ Logs ficam em `./logs` localmente
- ⚠️ Se adicionar dependências no composer.json, precisa rebuild

## 🐛 Debug

### Xdebug está habilitado

Configure seu IDE para conectar em:
- Host: `localhost`
- Port: `9003`

### Ver logs de um worker específico

```bash
# Logs do worker de envio
docker-compose -f docker-compose.dev.yml logs -f worker-outbound

# Logs do worker de eventos
docker-compose -f docker-compose.dev.yml logs -f worker-inbound

# Logs em arquivo
tail -f logs/app.log
```

### Reiniciar um worker

```bash
docker-compose -f docker-compose.dev.yml restart worker-outbound
```

### Executar comandos PHP

```bash
# Criar empresa
docker-compose -f docker-compose.dev.yml exec php-fpm \
  php scripts/create-test-data.php

# Qualquer comando PHP
docker-compose -f docker-compose.dev.yml exec php-fpm \
  php -r "echo 'Hello World';"

# Composer install
docker-compose -f docker-compose.dev.yml exec php-fpm \
  composer install
```

## 📊 Testar a API

```bash
# Health check
curl http://localhost:8000/health

# Login
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@growhub.com","password":"Admin@123456"}'

# Listar empresas (com token do login)
curl http://localhost:8000/api/admin/companies \
  -H "Authorization: Bearer {TOKEN}"
```

## 🗄️ Banco de Dados

### Acessar via psql

```bash
# Via docker-compose
docker-compose -f docker-compose.dev.yml exec postgres \
  psql -U postgres -d growhub_gateway

# Ou via Makefile
make dev-shell-db
```

### Executar SQL

```bash
# Query rápida
docker-compose -f docker-compose.dev.yml exec -T postgres \
  psql -U postgres -d growhub_gateway \
  -c "SELECT COUNT(*) FROM companies;"

# Arquivo SQL
docker-compose -f docker-compose.dev.yml exec -T postgres \
  psql -U postgres -d growhub_gateway < meu-script.sql
```

### Reset do banco

```bash
# Parar containers
docker-compose -f docker-compose.dev.yml down

# Remover volumes (⚠️ apaga dados)
docker-compose -f docker-compose.dev.yml down -v

# Subir novamente (vai recriar tudo)
docker-compose -f docker-compose.dev.yml up -d
```

## 🔄 Workflow de Desenvolvimento

### 1. Editar código
Edite arquivos normalmente no seu editor local. As mudanças refletem imediatamente.

### 2. Ver resultado
- API: Recarregue `http://localhost:8000`
- Workers: São reiniciados automaticamente pelo Docker

### 3. Ver logs
```bash
make dev-logs
# ou
tail -f logs/app.log
```

### 4. Testar
Use curl, Postman ou o arquivo `api-examples.http`

## ⚠️ Troubleshooting

### Container não inicia

```bash
# Ver logs de erro
docker-compose -f docker-compose.dev.yml logs php-fpm

# Verificar status
docker-compose -f docker-compose.dev.yml ps
```

### Porta já em uso

Se a porta 8000 já estiver em uso, edite `docker-compose.dev.yml`:
```yaml
nginx:
  ports:
    - "8080:80"  # Mudar para 8080 ou outra porta
```

### Workers não estão processando

```bash
# Ver se estão rodando
docker-compose -f docker-compose.dev.yml ps

# Ver logs
docker-compose -f docker-compose.dev.yml logs worker-outbound

# Reiniciar worker
docker-compose -f docker-compose.dev.yml restart worker-outbound
```

### Erro de conexão com banco

```bash
# Verificar se PostgreSQL está healthy
docker-compose -f docker-compose.dev.yml ps postgres

# Ver logs
docker-compose -f docker-compose.dev.yml logs postgres

# Aguardar mais tempo
sleep 20
```

### Erro de conexão com RabbitMQ

```bash
# Verificar status
docker-compose -f docker-compose.dev.yml ps rabbitmq

# Ver logs
docker-compose -f docker-compose.dev.yml logs rabbitmq

# Acessar management
# http://localhost:15672
```

### Limpar tudo e começar do zero

```bash
# Parar e remover tudo
docker-compose -f docker-compose.dev.yml down -v

# Remover imagens também
docker-compose -f docker-compose.dev.yml down -v --rmi all

# Rebuild completo
docker-compose -f docker-compose.dev.yml build --no-cache

# Subir novamente
docker-compose -f docker-compose.dev.yml up -d
```

## 📁 Estrutura de Volumes

```
Código:   ./ → /var/www/html (bind mount, hot reload)
Logs:     ./logs → /var/www/html/logs (bind mount)
DB:       postgres_data_dev (volume, persistente)
RabbitMQ: rabbitmq_data_dev (volume, persistente)
```

## 🎓 Dicas

1. **Use `make`**: Comandos mais curtos e fáceis
2. **Logs em tempo real**: `make dev-logs` sempre aberto
3. **Shell rápido**: `make dev-shell` para acessar container
4. **Rebuild quando**:
   - Alterar Dockerfile
   - Adicionar dependência no composer.json
   - Mudanças em configurações do PHP

## 🚀 Próximos Passos

1. Acesse http://localhost:8000/health
2. Faça login via API
3. Crie uma empresa
4. Adicione um provider
5. Crie uma instância
6. Envie uma mensagem de teste
7. Veja a documentação completa no README.md

