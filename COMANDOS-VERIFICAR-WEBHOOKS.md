# 🔍 Comandos para Verificar Webhooks Enviados

## 1. Ver logs do worker-inbound (últimas 50 linhas com filtros)
```bash
docker logs --tail 50 growhub_worker_inbound_dev | grep -E "Processing inbound event|Webhook notification sent|Instance found|Failed to send webhook|on-message|on-connected"
```

## 2. Ver TODOS os logs recentes do worker (sem filtro)
```bash
docker logs --tail 100 growhub_worker_inbound_dev
```

## 3. Verificar eventos processados e webhooks enviados
```bash
docker logs --tail 200 growhub_worker_inbound_dev | grep -E "webhook" -i
```

## 4. Ver logs da API para verificar se evento foi recebido e enviado para fila
```bash
docker logs --tail 50 growhub_php_dev | grep -E "webhook|message|sent to queue" -i
```

## 5. Verificar quantas mensagens há nas filas agora (comparar com antes)
```bash
docker exec growhub_rabbitmq_dev rabbitmqctl list_queues name messages consumers | grep -E "inbound|messages"
```

## 6. Monitorar em tempo real os próximos eventos
```bash
docker logs -f growhub_worker_inbound_dev | grep -E "Processing|Webhook|Instance"
```

## 7. Verificar especificamente webhooks enviados com sucesso
```bash
docker logs growhub_worker_inbound_dev 2>&1 | grep "Webhook notification sent" | tail -20
```

## 8. Verificar erros ao enviar webhooks
```bash
docker logs growhub_worker_inbound_dev 2>&1 | grep "Failed to send webhook" | tail -20
```

