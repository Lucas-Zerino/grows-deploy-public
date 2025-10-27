# 🚀 GrowHub Webhook Tester

Servidor Node.js simples para testar webhooks do sistema GrowHub.

## 📋 Funcionalidades

- ✅ Interface web amigável
- ✅ Recebe webhooks em múltiplos endpoints
- ✅ Armazena histórico de webhooks recebidos
- ✅ Auto-refresh da interface
- ✅ Limpeza de histórico
- ✅ Visualização detalhada dos webhooks

## 🚀 Como usar

### 1. Instalar dependências
```bash
npm install
```

### 2. Iniciar o servidor
```bash
npm start
```

### 3. Acessar a interface
Abra seu navegador em: `http://localhost:3001`

## 📡 Endpoints Disponíveis

O servidor aceita webhooks em qualquer endpoint que comece com `/webhook/`:

- `POST http://localhost:3001/webhook/1`
- `POST http://localhost:3001/webhook/2`
- `POST http://localhost:3001/webhook/3`
- `POST http://localhost:3001/webhook/instancia1`
- `POST http://localhost:3001/webhook/vendas`
- etc...

## 🔧 Configuração no GrowHub

Para configurar webhooks no GrowHub, use os endpoints do servidor:

1. Acesse o GrowHub em `http://localhost:8000`
2. Configure o webhook da instância para: `http://localhost:3001/webhook/instancia1`
3. Comece a enviar mensagens
4. Veja os webhooks chegando na interface do servidor

## 📊 API Endpoints

- `GET /` - Interface web principal
- `POST /webhook/:id` - Receber webhook
- `GET /webhooks` - Listar todos os webhooks
- `POST /clear` - Limpar histórico
- `GET /status` - Status do servidor

## 🛠️ Desenvolvimento

Para modo de desenvolvimento com auto-reload:
```bash
npm run dev
```

## 📝 Exemplo de Webhook

Quando uma mensagem é enviada, o webhook receberá algo como:

```json
{
  "event": "message.sent",
  "data": {
    "message_id": "123",
    "phone_to": "5511999999999",
    "content": "Olá! Como posso ajudar?",
    "status": "sent"
  }
}
```

## 🎯 Próximos Passos

1. Configure seus webhooks no GrowHub
2. Envie mensagens através das rotas `/send/*`
3. Monitore os webhooks chegando em tempo real
4. Use os dados para debug e desenvolvimento
