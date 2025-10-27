# 🐧 Comandos Make (Linux/Mac)

Guia completo dos comandos `make` disponíveis no projeto.

## 🚀 Comandos Mais Usados

```bash
make r                 # Reiniciar tudo (atalho super rápido!)
make restart           # Reiniciar todos os containers
make restart-api       # Reiniciar apenas API
make dev-logs          # Ver logs em tempo real
make ps                # Ver status dos containers
```

## 📋 Todos os Comandos

### Restart
```bash
make r                  # Atalho super rápido para restart
make restart            # Reiniciar todos os containers
make restart-api        # Reiniciar nginx + php-fpm
make restart-workers    # Reiniciar todos os workers
make restart-db         # Reiniciar PostgreSQL
make restart-rabbitmq   # Reiniciar RabbitMQ
make restart-redis      # Reiniciar Redis
```

### Logs
```bash
make dev-logs           # Ver logs de todos os serviços
make dev-logs-api       # Ver logs apenas da API
make dev-logs-workers   # Ver logs apenas dos workers
make dev-logs-webhooks  # Ver apenas webhooks recebidos
```

### Ambiente
```bash
make dev-up             # Subir ambiente de desenvolvimento
make dev-down           # Parar ambiente de desenvolvimento
make dev-rebuild        # Rebuild das imagens Docker
make ps                 # Ver status dos containers
```

### Shell & Debug
```bash
make dev-shell          # Acessar shell do container PHP
make dev-shell-db       # Acessar shell do PostgreSQL
```

### Setup & Dados
```bash
make dev-setup          # Setup completo do ambiente
make setup-rabbitmq     # Configurar RabbitMQ
make seed-admin         # Criar superadmin
make test-data          # Criar dados de teste
```

### Utilitários
```bash
make help               # Ver todos os comandos disponíveis
make clean              # Limpar cache e logs
make lint               # Verificar erros de sintaxe PHP
```

### Produção
```bash
make prod-up            # Subir ambiente de produção
make prod-down          # Parar ambiente de produção
make prod-logs          # Ver logs de produção
make prod-shell         # Acessar shell (produção)
```

## 💡 Dicas de Uso

### 1. Múltiplos Terminais

Mantenha terminais abertos para:
```bash
# Terminal 1: Logs da API
make dev-logs-api

# Terminal 2: Logs de Webhooks
make dev-logs-webhooks

# Terminal 3: Comandos
make restart-api
```

### 2. Workflow de Desenvolvimento

```bash
# 1. Subir ambiente
make dev-up

# 2. Ver logs
make dev-logs-api

# 3. Fazer mudanças no código...

# 4. Restart rápido
make r

# 5. Testar...
```

### 3. Debug de Problemas

```bash
# Ver status
make ps

# Ver logs completos
make dev-logs

# Acessar container
make dev-shell

# Verificar banco
make dev-shell-db
```

## 🔄 Equivalência Windows

Se estiver no Windows, use:

| Make (Linux/Mac)        | PowerShell (Windows)                      |
|-------------------------|-------------------------------------------|
| `make r`                | `.\scripts\windows\restart.ps1`           |
| `make restart-api`      | `.\scripts\windows\restart-api.ps1`       |
| `make dev-logs-api`     | `.\scripts\windows\logs-api.ps1`          |
| `make dev-logs-webhooks`| `.\scripts\windows\logs-webhooks.ps1`     |
| `make ps`               | `.\scripts\windows\status.ps1`            |

📖 Ver: [COMANDOS-WINDOWS.md](COMANDOS-WINDOWS.md)

## 🛠️ Instalação do Make

### macOS
```bash
# Via Homebrew
brew install make
```

### Linux (Ubuntu/Debian)
```bash
sudo apt-get install make
```

### Linux (CentOS/RHEL)
```bash
sudo yum install make
```

## 📖 Documentação Relacionada

- [Comandos Windows](COMANDOS-WINDOWS.md) - Para Windows PowerShell
- [Restart Rápido](RESTART-RAPIDO.md) - Guia completo de restart
- [Como Conectar Instância](COMO-CONECTAR-INSTANCIA.md)
- [Verificar Webhooks](VERIFICAR-WEBHOOKS.md)

