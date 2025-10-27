# Arquitetura do Sistema - GrowHub Gateway

## Visão Geral

O GrowHub Gateway é um sistema de mensagens escalável que atua como uma ponte entre aplicações cliente e múltiplas APIs de WhatsApp (WAHA e UAZAPI).

```
┌─────────────┐
│   App       │
│  Cliente    │
└──────┬──────┘
       │ REST API
       │ (Token Auth)
       ▼
┌─────────────────────────────────────┐
│      API Gateway (PHP)              │
│  ┌────────────────────────────┐    │
│  │  Controllers                │    │
│  │  - Instance, Message, etc   │    │
│  └────────────────────────────┘    │
│  ┌────────────────────────────┐    │
│  │  Middleware                 │    │
│  │  - Auth, RateLimit          │    │
│  └────────────────────────────┘    │
└────────┬────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│      PostgreSQL                      │
│  - companies, instances, messages    │
│  - events, outbox_messages, logs     │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│      OutboxDB Pattern                │
│  Garante entrega mesmo com falhas    │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│      RabbitMQ                        │
│  ┌────────────────────────────┐     │
│  │ Exchanges:                 │     │
│  │  - outbound (topic)        │     │
│  │  - inbound (fanout)        │     │
│  │  - events (topic)          │     │
│  │  - retry, dlq              │     │
│  └────────────────────────────┘     │
│  ┌────────────────────────────┐     │
│  │ Queues Dinâmicas:          │     │
│  │  - outbound.company.{id}.* │     │
│  │  - inbound.company.{id}    │     │
│  │  - events.company.{id}     │     │
│  └────────────────────────────┘     │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│      Workers (Background)            │
│  ┌────────────────────────────┐     │
│  │ - Message Sender           │     │
│  │ - Event Processor          │     │
│  │ - Outbox Processor         │     │
│  │ - Health Check             │     │
│  └────────────────────────────┘     │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│      Provider Manager                │
│  Abstração para WAHA/UAZAPI          │
└────────┬─────────────────────────────┘
         │
         ▼
┌─────────────────┐  ┌─────────────────┐
│  WAHA Server 1  │  │ UAZAPI Server 1 │
│  WAHA Server 2  │  │ UAZAPI Server 2 │
└─────────────────┘  └─────────────────┘
         │                    │
         └────────┬───────────┘
                  │
                  ▼ Webhooks
┌──────────────────────────────────────┐
│      Webhook Controller              │
│  Recebe eventos das APIs             │
└──────────────────────────────────────┘
```

## Componentes Principais

### 1. API Gateway (PHP)

**Responsabilidades:**
- Autenticação via token
- Rate limiting
- Validação de entrada
- Roteamento de requisições
- Response padronizado

**Tecnologias:**
- PHP 8.1+ puro
- PSR-4 Autoloading
- Composer para dependências

### 2. PostgreSQL (Banco de Dados)

**Tabelas Principais:**

1. **companies** - Empresas que usam o sistema
2. **providers** - Servidores WAHA/UAZAPI cadastrados
3. **instances** - Instâncias de WhatsApp por empresa
4. **messages** - Histórico completo de mensagens
5. **events** - Eventos (leitura, entrega, conexão)
6. **outbox_messages** - Pattern OutboxDB para garantir entrega
7. **logs** - Logs estruturados
8. **rate_limits** - Controle de taxa por empresa
9. **queue_metadata** - Metadados das filas dinâmicas

**Características:**
- JSONB para dados flexíveis (payload, eventos)
- Índices otimizados para queries frequentes
- Triggers para updated_at automático
- Constraints e foreign keys para integridade

### 3. RabbitMQ (Message Broker)

**Estratégia de Filas:**

#### Exchanges:
- `messaging.outbound.exchange` (topic) - Mensagens de saída
- `messaging.inbound.exchange` (fanout) - Mensagens recebidas
- `events.exchange` (topic) - Eventos diversos
- `retry.exchange` (topic) - Retry com delay
- `dlq.exchange` (direct) - Dead letter final

#### Filas Dinâmicas (por empresa):
- `outbound.company.{id}.priority.high` (max-priority: 10)
- `outbound.company.{id}.priority.normal` (max-priority: 5)
- `outbound.company.{id}.priority.low` (max-priority: 1)
- `inbound.company.{id}`
- `events.company.{id}`

#### Filas Globais:
- `outbox.processor` - Processa outbox pattern
- `health.check` - Health checks
- `queue.manager` - Gerencia filas dinâmicas
- `webhook.fanout` - Distribui webhooks
- `dlq.final` - Dead letter para análise manual

**Configurações de Fila:**
- `x-max-priority` - Priorização de mensagens
- `x-dead-letter-exchange` - DLQ automático
- `x-max-length` - Limite de 50k mensagens por fila
- `x-overflow: drop-head` - Descarta mais antigas se lotar

### 4. OutboxDB Pattern

**Fluxo:**

1. API recebe requisição para enviar mensagem
2. Em uma transação:
   - Insere em `messages` (status: queued)
   - Insere em `outbox_messages` (status: pending)
3. Commit da transação
4. Outbox Processor Worker (loop):
   - Busca mensagens pending
   - Publica no RabbitMQ
   - Marca como completed
5. Se RabbitMQ falhar:
   - Mensagem fica em pending
   - Será tentada novamente no próximo loop

**Vantagens:**
- Garantia de entrega mesmo se RabbitMQ cair
- Transação ACID no banco
- Nenhuma mensagem perdida
- Idempotência

### 5. Workers (Background Processes)

#### Message Sender Worker
- **Consome:** `outbound.company.*.priority.*`
- **Prefetch:** 5 mensagens
- **Função:** Enviar mensagens via providers
- **Retry:** 3 tentativas com backoff
- **Escala:** 10-50 instâncias em produção

#### Event Processor Worker
- **Consome:** `inbound.company.*`, `events.company.*`
- **Prefetch:** 10 mensagens
- **Função:** Processar eventos e notificar webhooks
- **Escala:** 5-20 instâncias

#### Outbox Processor Worker
- **Consome:** Polling do banco (não RabbitMQ)
- **Intervalo:** 5 segundos
- **Função:** Garantir outbox pattern
- **Escala:** 2-5 instâncias

#### Health Check Worker
- **Intervalo:** 60 segundos
- **Função:** Verificar providers e cleanup
- **Tasks:**
  - Health check de todos providers
  - Cleanup de outbox antigo (7 dias)
  - Cleanup de rate limits (24h)
  - Cleanup de filas inativas

### 6. Provider Manager

**Interface unificada para:**
- WAHA (https://waha.devlike.pro/)
- UAZAPI (https://uazapi.com/)

**Métodos:**
- `createInstance()`
- `deleteInstance()`
- `sendTextMessage()`
- `sendMediaMessage()`
- `getInstanceStatus()`
- `healthCheck()`
- `getQRCode()`

**Balanceamento:**
- Ao criar instância, escolhe provider com:
  1. `health_status = 'healthy'`
  2. `current_instances < max_instances`
  3. Menor `current_instances` (balanceamento)

## Fluxos Principais

### Fluxo 1: Envio de Mensagem (Outbound)

```
1. App → POST /api/messages/send
         ↓
2. AuthMiddleware valida token
         ↓
3. RateLimitMiddleware verifica limite
         ↓
4. MessageController.send()
         ↓
5. BEGIN TRANSACTION
         ↓
6. INSERT messages (status: queued)
         ↓
7. INSERT outbox_messages (status: pending)
         ↓
8. COMMIT
         ↓
9. Response 201 Created (imediato)
         
   [Async - Worker Outbox]
10. SELECT outbox WHERE status = pending
         ↓
11. PUBLISH to RabbitMQ
         ↓
12. UPDATE outbox (status: completed)
         
   [Async - Worker Message Sender]
13. CONSUME from queue
         ↓
14. UPDATE message (status: processing)
         ↓
15. ProviderManager.sendMessage()
         ↓
16. HTTP POST to WAHA/UAZAPI
         ↓
17. UPDATE message (status: sent, external_id)
         ↓
18. ACK message from queue
```

### Fluxo 2: Recebimento de Mensagem (Inbound)

```
1. WAHA/UAZAPI → POST /webhook/{instance_id}
         ↓
2. WebhookController.receive()
         ↓
3. Detecta tipo de evento
         ↓
4. BEGIN TRANSACTION
         ↓
5. INSERT messages (direction: inbound)
         ↓
6. INSERT events
         ↓
7. INSERT outbox (para notificar empresa)
         ↓
8. COMMIT
         ↓
9. Response 200 OK
         
   [Async - Worker Event Processor]
10. CONSUME from inbound queue
         ↓
11. Se tem webhook_url:
         ↓
12. HTTP POST to company webhook
         ↓
13. ACK message
```

### Fluxo 3: Atualização de Status (ACK)

```
1. WAHA/UAZAPI → POST /webhook/{instance_id}
         ↓
2. Detecta event_type = delivered/read
         ↓
3. Busca message por external_id
         ↓
4. UPDATE message (status, delivered_at/read_at)
         ↓
5. INSERT event
         ↓
6. Notifica empresa via webhook (se configurado)
```

## Escalabilidade

### Horizontal

**Workers:**
- Múltiplas instâncias do mesmo worker
- Consumem da mesma fila (round-robin)
- Stateless (pode escalar infinitamente)

**API:**
- Múltiplas instâncias atrás de load balancer
- Stateless (sessões via token, não cookies)

**RabbitMQ:**
- Clustering (futuro)
- Mirrored queues (futuro)

**PostgreSQL:**
- Read replicas para queries (futuro)
- Particionamento de tabelas grandes (futuro)

### Vertical

**Providers:**
- Múltiplos servidores WAHA/UAZAPI
- Balanceamento automático
- Health check contínuo

**Filas:**
- Filas independentes por empresa
- Priorização dentro da empresa
- Isolamento de tráfego

## Monitoramento

### Métricas Importantes

1. **RabbitMQ:**
   - Tamanho das filas
   - Taxa de publicação/consumo
   - Mensagens em DLQ

2. **PostgreSQL:**
   - Tamanho das tabelas
   - Queries lentas
   - Conexões ativas

3. **Workers:**
   - CPU/Memory usage
   - Mensagens processadas/min
   - Taxa de erro

4. **Providers:**
   - Health status
   - Latência de resposta
   - Taxa de erro

### Alertas

- Fila > 10.000 mensagens
- Provider unhealthy > 5 minutos
- Taxa de erro > 5%
- DLQ com mensagens
- Outbox com mensagens pending > 1 hora

## Segurança

### Autenticação
- Token UUID v4 por empresa
- Superadmin token separado
- Bearer token em header

### Autorização
- Empresa só acessa suas próprias instâncias
- Empresa só vê suas mensagens/eventos
- Admin endpoints apenas para superadmin

### Validação
- Input validation em todos endpoints
- Prepared statements (SQL injection)
- Rate limiting por empresa

### Webhooks
- Validação de origem (opcional)
- HTTPS obrigatório em produção
- Retry com backoff

## Performance

### Otimizações

1. **Banco:**
   - Índices em colunas frequentemente consultadas
   - JSONB para flexibilidade sem JOINs
   - Connection pooling

2. **Filas:**
   - Prefetch configurado por worker
   - QoS para balanceamento
   - Priorização de mensagens urgentes

3. **API:**
   - Response imediato (não aguarda processamento)
   - Rate limiting para proteção
   - CORS otimizado

4. **Workers:**
   - Processamento em batch quando possível
   - Graceful shutdown (SIGTERM)
   - Auto-restart em caso de crash

## Limitações Conhecidas

1. **Wildcards no RabbitMQ:** Nem todas bibliotecas AMQP suportam wildcards em nomes de filas. Workers precisam conhecer filas específicas ou usar plugin adicional.

2. **Outbox Polling:** O Outbox Processor usa polling (5s). Para menor latência, considerar triggers do PostgreSQL com NOTIFY/LISTEN.

3. **Single Point of Failure:** RabbitMQ e PostgreSQL são single instance. Para HA, configurar clustering.

4. **Webhook Retry:** Retry de webhook é limitado. Considerar implementar retry exponencial com max attempts.

## Melhorias Futuras

1. **Observabilidade:**
   - Prometheus metrics
   - Grafana dashboards
   - Distributed tracing (Jaeger)

2. **Alta Disponibilidade:**
   - RabbitMQ clustering
   - PostgreSQL streaming replication
   - Multi-region deployment

3. **Features:**
   - Agendamento de mensagens
   - Templates de mensagens
   - Broadcast para múltiplos contatos
   - Chatbot integration

4. **Performance:**
   - Redis para cache
   - Elasticsearch para busca de mensagens
   - CDN para mídia

