# 🪟 Comandos Rápidos para Windows (PowerShell)

## 🚀 Scripts PowerShell Prontos

Criei scripts `.ps1` prontos para você! Basta executar:

### Comandos Mais Usados

```powershell
# Reiniciar tudo
.\restart.ps1

# Reiniciar apenas API
.\restart-api.ps1

# Ver logs da API
.\logs-api.ps1

# Ver logs de webhooks
.\logs-webhooks.ps1

# Ver status dos containers
.\status.ps1

# Subir ambiente
.\dev-up.ps1

# Parar ambiente
.\dev-down.ps1
```

## ⚠️ Erro de Política de Execução?

Se você receber o erro:
```
O arquivo não pode ser carregado porque a execução de scripts foi desabilitada neste sistema
```

**Solução 1 (Recomendada):** Permitir execução temporária
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
```

**Solução 2:** Permitir permanentemente (como Administrador)
```powershell
Set-ExecutionPolicy RemoteSigned
```

## 📋 Tabela de Equivalência

| Make (Linux/Mac)        | PowerShell (Windows)    | O que faz                          |
|------------------------|-------------------------|-------------------------------------|
| `make r`               | `.\restart.ps1`         | Reiniciar tudo                     |
| `make restart`         | `.\restart.ps1`         | Reiniciar todos os containers      |
| `make restart-api`     | `.\restart-api.ps1`     | Reiniciar apenas API               |
| `make dev-logs-api`    | `.\logs-api.ps1`        | Ver logs da API                    |
| `make dev-logs-webhooks` | `.\logs-webhooks.ps1` | Ver logs de webhooks               |
| `make ps`              | `.\status.ps1`          | Ver status dos containers          |
| `make dev-up`          | `.\dev-up.ps1`          | Subir ambiente                     |
| `make dev-down`        | `.\dev-down.ps1`        | Parar ambiente                     |

## 🔧 Comandos Docker Diretos (Alternativa)

Se preferir usar Docker Compose direto:

```powershell
# Reiniciar API
docker-compose -f docker-compose.dev.yml restart nginx php-fpm

# Ver logs da API
docker-compose -f docker-compose.dev.yml logs -f nginx php-fpm

# Ver logs de webhooks
Get-Content logs\app-*.log -Wait -Tail 50 | Select-String "webhook"

# Ver status
docker-compose -f docker-compose.dev.yml ps

# Subir tudo
docker-compose -f docker-compose.dev.yml up -d

# Parar tudo
docker-compose -f docker-compose.dev.yml down

# Reiniciar tudo
docker-compose -f docker-compose.dev.yml restart
```

## 📦 Instalar Make no Windows (Opcional)

Se quiser usar os comandos `make`:

### Via Chocolatey (Recomendado)

1. **Instalar Chocolatey** (PowerShell como Administrador):
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
```

2. **Instalar Make**:
```powershell
choco install make
```

3. **Reiniciar PowerShell**

### Via Scoop

```powershell
# Instalar Scoop
iwr -useb get.scoop.sh | iex

# Instalar Make
scoop install make
```

### Via Git Bash

Se você tem Git for Windows instalado, pode usar o Git Bash que já vem com `make`:

1. Abra o **Git Bash**
2. Navegue até a pasta do projeto
3. Use os comandos `make` normalmente

## 💡 Dicas para Windows

### 1. Alias PowerShell (Facilitar Comandos)

Adicione ao seu perfil do PowerShell (`$PROFILE`):

```powershell
# Criar/editar perfil
notepad $PROFILE

# Adicione:
function r { .\restart.ps1 }
function logs { .\logs-api.ps1 }
function webhooks { .\logs-webhooks.ps1 }
function st { .\status.ps1 }
```

Depois disso, você pode usar:
```powershell
r          # em vez de .\restart.ps1
logs       # em vez de .\logs-api.ps1
webhooks   # em vez de .\logs-webhooks.ps1
st         # em vez de .\status.ps1
```

### 2. VS Code Terminal

Se usa VS Code, ele já vem com PowerShell integrado. Basta abrir o terminal (Ctrl+`) e rodar os scripts.

### 3. Windows Terminal (Recomendado)

Instale o **Windows Terminal** da Microsoft Store para uma experiência melhor:
- Suporta múltiplas abas
- Cores e temas
- Melhor performance

## 🎯 Exemplo de Uso

```powershell
# 1. Subir ambiente
.\dev-up.ps1

# 2. Ver logs em tempo real (em outra aba do terminal)
.\logs-api.ps1

# 3. Fazer mudanças no código...

# 4. Reiniciar API
.\restart-api.ps1

# 5. Ver webhooks (em outra aba)
.\logs-webhooks.ps1
```

## 🚨 Problemas Comuns

### Erro: "docker-compose não é reconhecido"

**Solução:** Certifique-se que Docker Desktop está instalado e rodando.

### Scripts não executam

**Causa:** Política de execução bloqueando scripts

**Solução:** 
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
```

### Logs não aparecem

**Causa:** Arquivo de log não existe

**Solução:** Execute alguma requisição na API primeiro para gerar logs

## 📖 Mais Informações

- **Docker Desktop:** https://www.docker.com/products/docker-desktop/
- **Windows Terminal:** Microsoft Store
- **Chocolatey:** https://chocolatey.org/
- **Git for Windows:** https://git-scm.com/download/win

