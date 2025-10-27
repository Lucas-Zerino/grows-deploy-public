# Guia de Início Rápido - GrowHub Gateway

## 🎯 Um Comando, Tudo Pronto!

### 1. Clone o Repositório

```bash
git clone <repo-url>
cd growhub
```

### 2. Suba os Containers

**Windows (PowerShell ou CMD):**
```bash
docker-compose -f docker-compose.dev.yml up -d
```

**Linux/Mac:**
```bash
docker-compose -f docker-compose.dev.yml up -d
```

### 3. Aguarde (~30 segundos)

O sistema fará automaticamente:

```
✓ Subindo PostgreSQL...
✓ Subindo RabbitMQ...
✓ Subindo PHP-FPM e Nginx...
✓ Subindo 4 Workers...
✓ Configurando RabbitMQ (exchanges, queues)...
✓ Criando superadmin...
✓ Criando dados de teste...
✓ Pronto!
```

### 4. Acessar

- **API**: http://localhost:8000
- **RabbitMQ**: http://localhost:15672 (admin/admin123)

## 🔐 Login

```bash
# Via curl
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@growhub.com\",\"password\":\"Admin@123456\"}"
```

Ou use o Postman/Insomnia com:
```json
{
  "email": "admin@growhub.com",
  "password": "Admin@123456"
}
```

## 📊 Ver Logs

```bash
# Todos os logs
docker-compose -f docker-compose.dev.yml logs -f

# Apenas API
docker-compose -f docker-compose.dev.yml logs -f php-fpm nginx

# Apenas workers
docker-compose -f docker-compose.dev.yml logs -f worker-outbound worker-inbound
```

## 🔧 Comandos Úteis

```bash
# Ver status
docker-compose -f docker-compose.dev.yml ps

# Acessar shell PHP
docker-compose -f docker-compose.dev.yml exec php-fpm sh

# Acessar PostgreSQL
docker-compose -f docker-compose.dev.yml exec postgres psql -U postgres -d growhub_gateway

# Reiniciar
docker-compose -f docker-compose.dev.yml restart

# Parar
docker-compose -f docker-compose.dev.yml down

# Resetar tudo (⚠️ apaga dados)
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
```

## 📝 Testar API

Use o arquivo `api-examples.http` ou:

```bash
# 1. Login e pegar token
TOKEN=$(curl -s -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@growhub.com","password":"Admin@123456"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# 2. Criar empresa
curl -X POST http://localhost:8000/api/admin/companies \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Minha Empresa"}'

# 3. Health check
curl http://localhost:8000/api/admin/health \
  -H "Authorization: Bearer $TOKEN"
```

## 🎉 Pronto!

Seu ambiente está funcionando. Próximos passos:

1. Adicionar provider WAHA ou UAZAPI
2. Criar instâncias
3. Enviar mensagens de teste
4. Ver documentação completa no README.md

## ❓ Problemas?

```bash
# Ver o que está acontecendo
docker-compose -f docker-compose.dev.yml logs setup

# Container específico não subiu?
docker-compose -f docker-compose.dev.yml ps
docker-compose -f docker-compose.dev.yml logs <container-name>

# Refazer setup
docker-compose -f docker-compose.dev.yml restart setup
```
