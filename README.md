# GrowHub Gateway - Sistema de Mensagens Escalável

Gateway de mensagens escalável que integra múltiplas APIs de WhatsApp (WAHA e UAZAPI) com sistema de filas robusto usando RabbitMQ e PostgreSQL.

## 🚀 Características

- **Multi-Provider**: Suporte para WAHA e UAZAPI com balanceamento automático
- **Filas Escaláveis**: Filas dinâmicas por empresa com sistema de prioridades
- **OutboxDB Pattern**: Garantia de entrega de mensagens mesmo em caso de falhas
- **Sistema de Eventos**: Processamento de eventos (leitura, entrega, conexão, etc)
- **Health Check**: Monitoramento automático de providers
- **Rate Limiting**: Controle de taxa por empresa
- **Logs Estruturados**: Logs detalhados em JSON com múltiplos níveis

## 📋 Requisitos

- Docker Desktop
- Docker Compose v2+
- Git

**Não precisa de PHP, Composer ou PostgreSQL instalado localmente!**

## 🛠️ Instalação (Um comando!)

### Windows (PowerShell ou CMD):

```bash
# Clone o repositório
git clone <repo>
cd growhub

# Subir tudo (setup automático)
docker-compose -f docker-compose.dev.yml up -d
```

### Linux/Mac:

```bash
# Clone o repositório
git clone <repo>
cd growhub

# Subir tudo (setup automático)
docker-compose -f docker-compose.dev.yml up -d
```

**Pronto!** Aguarde ~30 segundos e estará tudo configurado automaticamente:

✅ PostgreSQL com schema aplicado  
✅ RabbitMQ com exchanges e queues  
✅ Superadmin criado  
✅ Dados de teste (empresa + provider)  
✅ API rodando  
✅ 4 Workers em background  

## 🌐 Acessar Serviços

Após `docker-compose up -d`:

- **API Gateway**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672
  - Usuário: `admin`
  - Senha: `admin123`
- **PostgreSQL**: `localhost:5432`
  - Usuário: `postgres`
  - Senha: `postgres`

## ⚡ Atalhos Rápidos

### Reiniciar tudo (após mudanças no código)
```bash
make r
```

### Comandos mais usados

**Linux/Mac:**
```bash
make restart        # Reiniciar todos os containers
make restart-api    # Reiniciar apenas a API
make dev-logs       # Ver logs em tempo real
make ps             # Ver status dos containers
make help           # Ver todos os comandos disponíveis
```

**Windows PowerShell:**
```powershell
.\scripts\windows\restart.ps1       # Reiniciar todos os containers
.\scripts\windows\restart-api.ps1   # Reiniciar apenas a API
.\scripts\windows\logs-api.ps1      # Ver logs em tempo real
.\scripts\windows\status.ps1        # Ver status dos containers
```

---

## 📚 Documentação Completa

### 🌟 Começar Aqui
- 👋 **[LEIA-ME-PRIMEIRO.md](LEIA-ME-PRIMEIRO.md)** ⭐ **Comece por aqui!**
- 📮 [Guia Rápido do Postman](GUIA-RAPIDO-POSTMAN.md)
- ⚡ [Referência Rápida de Comandos](COMANDOS.md)

### Guias de Comandos
- 🪟 [Comandos Windows (PowerShell)](docs/COMANDOS-WINDOWS.md)
- 🐧 [Comandos Make (Linux/Mac)](docs/COMANDOS-MAKE.md)

### Guias de Uso
- 📱 [Como Conectar Instância e Pegar QR Code](docs/COMO-CONECTAR-INSTANCIA.md)
- 🔐 [Autenticação de Instância (QR Code ou Código)](docs/AUTENTICACAO-INSTANCIA.md)
- 📨 [Formato de Webhooks de Mensagens](docs/FORMATO-WEBHOOK-MENSAGENS.md)
- 🔔 [Como Verificar se Webhooks Estão Funcionando](docs/VERIFICAR-WEBHOOKS-COMPLETO.md)
- 🔄 [Migração: Connect → Authenticate](docs/MIGRACAO-AUTHENTICATE.md)
- 📝 [Padronização de Nomes de Instâncias](docs/PADRONIZACAO-NOMES-INSTANCIAS.md)

### Troubleshooting
- 🐛 [Troubleshooting Geral](docs/TROUBLESHOOTING.md)
- 🔍 [Debug de QR Code](docs/DEBUG-QRCODE.md)
- 🔧 [Status: Webhooks e QR Code](docs/STATUS-WEBHOOKS-QRCODE.md)

## 🔐 Login Superadmin

```json
{
  "email": "admin@growhub.com",
  "password": "Admin@123456"
}
```

## 📊 Testar a API

```bash
# Health check
curl http://localhost:8000/health

# Login do superadmin
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@growhub.com\",\"password\":\"Admin@123456\"}"

# Salve o token retornado e use nos próximos comandos
TOKEN="seu-token-aqui"

# Criar empresa
curl -X POST http://localhost:8000/api/admin/companies \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Minha Empresa\"}"
```

Ver mais exemplos em `api-examples.http`

## 🔧 Comandos Docker

```bash
# Ver logs de tudo
docker-compose -f docker-compose.dev.yml logs -f

# Ver logs da API
docker-compose -f docker-compose.dev.yml logs -f nginx php-fpm

# Ver logs dos workers
docker-compose -f docker-compose.dev.yml logs -f worker-outbound worker-inbound

# Ver status dos containers
docker-compose -f docker-compose.dev.yml ps

# Acessar shell PHP
docker-compose -f docker-compose.dev.yml exec php-fpm sh

# Acessar PostgreSQL
docker-compose -f docker-compose.dev.yml exec postgres psql -U postgres -d growhub_gateway

# Reiniciar tudo
docker-compose -f docker-compose.dev.yml restart

# Parar tudo
docker-compose -f docker-compose.dev.yml down

# Parar e remover volumes (⚠️ apaga dados)
docker-compose -f docker-compose.dev.yml down -v
```

## 📁 Estrutura de Containers

| Container | Serviço | Porta | Descrição |
|-----------|---------|-------|-----------|
| growhub_postgres_dev | PostgreSQL | 5432 | Banco de dados |
| growhub_rabbitmq_dev | RabbitMQ | 5672, 15672 | Message broker |
| growhub_php_dev | PHP-FPM | 9000 | Processa PHP |
| growhub_nginx_dev | Nginx | 8000 | Web server |
| growhub_worker_outbound_dev | Worker | - | Envia mensagens |
| growhub_worker_inbound_dev | Worker | - | Processa eventos |
| growhub_worker_outbox_dev | Worker | - | OutboxDB pattern |
| growhub_worker_health_dev | Worker | - | Health checks |
| growhub_setup_dev | Setup | - | Config automática (roda 1x) |

## 📚 API Endpoints

### Empresas (Company API)

- `POST /api/instances` - Criar instância
- `GET /api/instances` - Listar instâncias
- `POST /api/messages/send` - Enviar mensagem
- `GET /api/messages` - Histórico de mensagens
- `GET /api/events` - Eventos

### Superadmin

- `POST /api/admin/login` - Login
- `POST /api/admin/companies` - Criar empresa
- `GET /api/admin/companies` - Listar empresas
- `POST /api/admin/providers` - Adicionar provider
- `GET /api/admin/health` - Health check

### Webhooks

- `POST /webhook/{instanceId}` - Receber eventos de WAHA/UAZAPI

## 🔄 Hot Reload

O código local está montado nos containers. **Edite e veja as mudanças instantaneamente!**

```
Seu código → /var/www/html (no container)
```

## 🏗️ Arquitetura

```
App Cliente → API Gateway → RabbitMQ → Workers → APIs (WAHA/UAZAPI)
                   ↓            ↑          ↓
              PostgreSQL    OutboxDB    Logs
                   ↑
              Webhooks ← APIs (WAHA/UAZAPI)
```

### Filas RabbitMQ

- **Filas dinâmicas por empresa** com 3 prioridades (high, normal, low)
- **Dead Letter Queues** com retry automático
- **OutboxDB Pattern** para garantia de entrega

### Componentes

- **OutboxDB**: Garante que nenhuma mensagem se perca
- **Health Check**: Monitora providers a cada minuto
- **Rate Limiting**: Por empresa e endpoint
- **Logs**: Estruturados em JSON (arquivo + banco)

## 📖 Documentação Completa

- [QUICKSTART.md](QUICKSTART.md) - Guia rápido
- [DOCKER-DEV.md](DOCKER-DEV.md) - Desenvolvimento com Docker
- [ARCHITECTURE.md](ARCHITECTURE.md) - Detalhes da arquitetura
- [SETUP.md](SETUP.md) - Setup avançado
- [api-examples.http](api-examples.http) - Exemplos de requisições

## 🔐 Segurança

- Tokens UUID v4 para empresas
- Prepared statements (SQL Injection protection)
- Rate limiting configurável
- HTTPS obrigatório em produção
- Validação de input em todos endpoints

## 🚀 Produção

```bash
# Configure .env.prod com credenciais seguras
cp env.example .env.prod

# Subir em produção
docker-compose -f docker-compose.prod.yml up -d
```

## 🐛 Troubleshooting

### Containers não iniciam

```bash
# Ver logs de erro
docker-compose -f docker-compose.dev.yml logs

# Verificar se Docker está rodando
docker info
```

### Porta 8000 já em uso

Edite `docker-compose.dev.yml` e mude a porta:
```yaml
nginx:
  ports:
    - "8080:80"  # Mude para outra porta
```

### Resetar tudo

```bash
# Para e remove tudo (incluindo volumes)
docker-compose -f docker-compose.dev.yml down -v

# Sobe novamente (será reconfigurado)
docker-compose -f docker-compose.dev.yml up -d
```

## 📝 Tecnologias

- PHP 8.1 (puro, sem frameworks)
- PostgreSQL 16
- RabbitMQ 3.12
- Nginx
- Docker & Docker Compose

## 📄 Licença

Proprietário - GrowHub

## 🤝 Suporte

Para problemas ou dúvidas:
- Verifique os logs: `docker-compose -f docker-compose.dev.yml logs -f`
- Consulte a documentação em `/docs`
- Verifique `ARCHITECTURE.md` para detalhes técnicos
