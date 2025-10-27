# 🪟 Scripts PowerShell para Windows

Scripts prontos para desenvolvimento no Windows.

## 🚀 Como Usar

Todos os comandos devem ser executados na **raiz do projeto**.

### Comandos Disponíveis

```powershell
# Reiniciar
.\scripts\windows\restart.ps1          # Reiniciar tudo
.\scripts\windows\restart-api.ps1      # Reiniciar apenas API
.\scripts\windows\restart-workers.ps1  # Reiniciar apenas workers
.\scripts\windows\restart-db.ps1       # Reiniciar apenas PostgreSQL
.\scripts\windows\restart-rabbitmq.ps1 # Reiniciar apenas RabbitMQ

# Logs
.\scripts\windows\logs-api.ps1         # Ver logs da API
.\scripts\windows\logs-workers.ps1     # Ver logs dos workers
.\scripts\windows\logs-webhooks.ps1    # Ver logs de webhooks

# Ambiente
.\scripts\windows\dev-up.ps1           # Subir ambiente
.\scripts\windows\dev-down.ps1         # Parar ambiente
.\scripts\windows\status.ps1           # Ver status dos containers

# Utilitários
.\scripts\windows\shell.ps1            # Acessar shell do container PHP
```

## ⚠️ Política de Execução

Se encontrar erro ao executar scripts, rode:

```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
```

## 💡 Criar Aliases (Opcional)

Para facilitar, adicione ao seu perfil PowerShell (`$PROFILE`):

```powershell
# Editar perfil
notepad $PROFILE

# Adicione:
function r { .\scripts\windows\restart.ps1 }
function ra { .\scripts\windows\restart-api.ps1 }
function logs { .\scripts\windows\logs-api.ps1 }
function webhooks { .\scripts\windows\logs-webhooks.ps1 }
function st { .\scripts\windows\status.ps1 }
function up { .\scripts\windows\dev-up.ps1 }
function down { .\scripts\windows\dev-down.ps1 }
```

Depois disso:
```powershell
r          # reinicia tudo
ra         # reinicia API
logs       # ver logs
webhooks   # ver webhooks
```

## 📖 Documentação Completa

Ver: [../../docs/COMANDOS-WINDOWS.md](../../docs/COMANDOS-WINDOWS.md)

