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
 docker compose -f docker-compose.dev.yml up -d
 docker-compose -f docker-compose.dev.yml exec php-fpm php scripts/run-migration.php