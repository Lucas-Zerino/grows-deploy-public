# 🔄 Guia Rápido de Restart

## Comandos Super Rápidos

### Reiniciar Tudo
```bash
make r
```
ou
```bash
make restart
```

## Reiniciar Serviços Específicos

### 🌐 Reiniciar apenas a API
```bash
make restart-api
```
Reinicia: `nginx` + `php-fpm`

### 👷 Reiniciar apenas os Workers
```bash
make restart-workers
```
Reinicia: todos os workers (outbound, inbound, outbox, health)

### 🗄️ Reiniciar apenas o Banco de Dados
```bash
make restart-db
```
Reinicia: `postgres`

### 🐰 Reiniciar apenas o RabbitMQ
```bash
make restart-rabbitmq
```
Reinicia: `rabbitmq`

### 🔴 Reiniciar apenas o Redis
```bash
make restart-redis
```
Reinicia: `redis`

## Outros Comandos Úteis

### Ver Status dos Containers
```bash
make ps
```

### Ver Logs em Tempo Real
```bash
# Todos os serviços
make dev-logs

# Apenas API
make dev-logs-api

# Apenas Workers
make dev-logs-workers
```

### Acessar Shell
```bash
# PHP Container
make dev-shell

# Banco de Dados
make dev-shell-db
```

### Rebuild Completo (quando houver mudanças no Dockerfile)
```bash
make dev-rebuild
```

### Parar Tudo
```bash
make dev-down
```

### Subir Tudo
```bash
make dev-up
```

## 💡 Dicas

1. **Mudou código PHP?** → `make restart-api` ou simplesmente `make r`
2. **Mudou código de worker?** → `make restart-workers`
3. **Problemas com fila?** → `make restart-rabbitmq`
4. **Problemas com cache?** → `make restart-redis`
5. **Mudou Dockerfile/Docker Compose?** → `make dev-rebuild` seguido de `make dev-up`

## ⚡ Pro Tips

- Use `make r` para restart rápido de tudo
- Use `make ps` para verificar status antes de reiniciar
- Use `make dev-logs` após reiniciar para ver se tudo subiu corretamente
- Mantenha um terminal com `make dev-logs-api` aberto durante desenvolvimento

## 🆘 Problemas Comuns

### Container não reinicia?
```bash
make dev-down
make dev-up
```

### Erro de porta já em uso?
```bash
make dev-down
# Aguarde alguns segundos
make dev-up
```

### Banco de dados travou?
```bash
make restart-db
# ou
make dev-down && make dev-up
```

### Workers não processam fila?
```bash
make restart-workers
# Verifique os logs
make dev-logs-workers
```

