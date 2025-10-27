# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [BREAKING CHANGE] - 2025-10-14

### 🔄 Autenticação de Instâncias Reformulada

#### ❌ Removido
- **`POST /instance/connect`** - Endpoint deprecado e removido

#### ✅ Adicionado
- **`POST /instance/authenticate`** - Novo endpoint unificado de autenticação
  - Suporta múltiplos métodos: `qrcode` e `phone_code`
  - QR code vem diretamente na resposta
  - Melhor UX e mais flexível
  
- **`GET /instance/authenticate/qrcode?format={raw|image}`** - Obter QR code
  - `format=raw`: Retorna string do QR code
  - `format=image`: Retorna imagem PNG diretamente

#### 🔧 Migração Necessária

**Antes:**
```http
POST /instance/connect
{ "phone": "5511999999999" }
```

**Depois:**
```http
POST /instance/authenticate
{ 
  "method": "phone_code",
  "phone_number": "5511999999999"
}
```

Ver guia completo: [docs/MIGRACAO-AUTHENTICATE.md](docs/MIGRACAO-AUTHENTICATE.md)

#### 📚 Documentação Atualizada
- [Como Conectar Instância](docs/COMO-CONECTAR-INSTANCIA.md)
- [Autenticação de Instância](docs/AUTENTICACAO-INSTANCIA.md)
- [Diferença entre Endpoints](docs/DIFERENCA-ENDPOINTS.md)
- Coleção Postman atualizada

### 📨 Formato Customizado de Webhooks de Mensagens

#### ✅ Implementado
- **Formato padronizado** para webhooks de mensagens
- **Detecção automática** de tipo (text, audio, video, image, document)
- **Suporte completo** para mídias com metadados
- **Detecção de grupos** e participantes
- **Suporte a LID** (LinkedIn Identifier)

#### 📋 Campos do Webhook

```json
{
  "usalid": false,
  "type": "text",
  "isMedia": false,
  "de_para_json": true,
  "container": "api-{session}",
  "session": 9,
  "device": "558498537596",
  "event": "on-message",
  "pushName": "Nome",
  "from": "5511999999999",
  "lid": "",
  "id": "msg_id",
  "content": "texto da mensagem",
  "isgroup": false,
  "participant": "",
  "timestamp": 1760471539000,
  "content_msg": { ... },
  "webhook": "webhook_wh_message",
  "ambiente": "dev",
  "token": "instance_token",
  "file": { ... }  // apenas se isMedia: true
}
```

Ver: [docs/FORMATO-WEBHOOK-MENSAGENS.md](docs/FORMATO-WEBHOOK-MENSAGENS.md)

### 🐛 Bugs Corrigidos

#### ✅ QueueService::publishToExchange() não existia
- Webhooks chegavam mas falhavam com erro 500
- Adicionado método `publishToExchange()` como alias de `publish()`
- Webhooks agora processam e enviam para RabbitMQ corretamente

---

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2024-10-13

### Adicionado

#### Infraestrutura
- Configuração completa do Docker (desenvolvimento e produção)
- Docker Compose para PostgreSQL e RabbitMQ
- Dockerfile para PHP 8.1 com extensões necessárias
- Nginx configurado com PHP-FPM
- Scripts de inicialização automática

#### Banco de Dados
- Schema PostgreSQL completo com 9 tabelas
- Suporte a JSONB para dados flexíveis
- Índices otimizados para performance
- Triggers automáticos para updated_at
- Constraints e foreign keys para integridade

#### Sistema de Filas
- Integração completa com RabbitMQ
- 5 Exchanges configurados (outbound, inbound, events, retry, dlq)
- Filas dinâmicas por empresa com prioridades
- Sistema de Dead Letter Queues
- Retry automático com backoff exponencial
- Filas globais para processamento centralizado

#### API REST
- 15+ endpoints RESTful
- Autenticação via Bearer token
- Sistema de autorização por empresa
- Rate limiting por empresa
- CORS configurado
- Respostas padronizadas JSON

#### Models
- Company - Gerenciamento de empresas
- Provider - Servidores WAHA/UAZAPI
- Instance - Instâncias de WhatsApp
- Message - Histórico de mensagens
- Event - Eventos do sistema
- OutboxMessage - Pattern OutboxDB

#### Services
- QueueService - Integração RabbitMQ
- QueueManagerService - Gerenciamento de filas dinâmicas
- OutboxService - Implementação OutboxDB pattern
- ProviderManager - Gerenciamento de providers
- HealthCheckService - Monitoramento de saúde

#### Providers
- WahaProvider - Integração completa com WAHA API
- UazapiProvider - Integração completa com UAZAPI
- Interface unificada para ambos providers

#### Workers
- Message Sender - Envio de mensagens via providers
- Event Processor - Processamento de eventos e webhooks
- Outbox Processor - Garantia de entrega (OutboxDB)
- Health Check - Monitoramento e cleanup

#### Endpoints da API

**Empresas (Company):**
- POST /api/instances - Criar instância
- GET /api/instances - Listar instâncias
- GET /api/instances/{id} - Obter instância
- DELETE /api/instances/{id} - Deletar instância
- GET /api/instances/{id}/qrcode - Obter QR code
- POST /api/messages/send - Enviar mensagem
- GET /api/messages - Listar mensagens
- GET /api/messages/{id} - Obter mensagem
- GET /api/events - Listar eventos
- GET /api/events/{id} - Obter evento

**Admin (Superadmin):**
- POST /api/admin/companies - Criar empresa
- GET /api/admin/companies - Listar empresas
- GET /api/admin/companies/{id} - Obter empresa
- PUT /api/admin/companies/{id}/status - Atualizar status
- DELETE /api/admin/companies/{id} - Deletar empresa
- POST /api/admin/providers - Criar provider
- GET /api/admin/providers - Listar providers
- GET /api/admin/providers/{id} - Obter provider
- PUT /api/admin/providers/{id} - Atualizar provider
- DELETE /api/admin/providers/{id} - Deletar provider
- GET /api/admin/health - Health check completo

**Webhooks:**
- POST /webhook/{instanceId} - Receber eventos de providers

#### Middleware
- AuthMiddleware - Autenticação via token
- RateLimitMiddleware - Controle de taxa

#### Utilitários
- Database - Wrapper PDO com helpers
- Logger - Logs estruturados com Monolog
- Response - Respostas JSON padronizadas
- Router - Sistema de rotas simples

#### Scripts
- start-dev.sh - Inicialização automática (dev)
- start-workers.sh - Iniciar todos workers
- stop-workers.sh - Parar todos workers
- create-test-data.php - Criar dados de teste
- rabbitmq_setup.php - Configuração inicial RabbitMQ

#### Documentação
- README.md - Documentação principal
- QUICKSTART.md - Guia de início rápido
- ARCHITECTURE.md - Detalhes da arquitetura
- IMPLEMENTATION_SUMMARY.md - Resumo da implementação
- api-examples.http - Exemplos de uso da API
- CHANGELOG.md - Este arquivo

#### Segurança
- Tokens UUID v4 para empresas
- Prepared statements (proteção SQL Injection)
- Rate limiting por empresa e endpoint
- Validação de input em todos endpoints
- Suporte a HTTPS

#### Funcionalidades
- Envio de mensagens (texto, imagem, vídeo, áudio, documento)
- Sistema de prioridades (alta, normal, baixa)
- Recebimento de mensagens via webhook
- Eventos de leitura, entrega e conexão
- Balanceamento de carga entre providers
- Health check automático de providers
- OutboxDB pattern para garantia de entrega
- Cleanup automático de dados antigos

### Tecnologias

- PHP 8.1+
- PostgreSQL 16
- RabbitMQ 3.12
- Docker & Docker Compose
- Nginx
- Monolog (Logs)
- Guzzle (HTTP Client)
- php-amqplib (RabbitMQ Client)
- Ramsey UUID

### Notas

Esta é a versão inicial (MVP) do GrowHub Gateway. O sistema está totalmente funcional e pronto para uso em desenvolvimento. Para uso em produção, consulte o IMPLEMENTATION_SUMMARY.md para considerações adicionais.

---

## Formato

- **[Adicionado]** para novas funcionalidades
- **[Alterado]** para mudanças em funcionalidades existentes
- **[Descontinuado]** para funcionalidades que serão removidas
- **[Removido]** para funcionalidades removidas
- **[Corrigido]** para correção de bugs
- **[Segurança]** para vulnerabilidades corrigidas

