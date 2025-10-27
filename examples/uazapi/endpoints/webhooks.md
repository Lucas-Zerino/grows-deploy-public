GET
/webhook
Ver Webhook da Instância
Retorna a configuração atual do webhook da instância, incluindo:

URL configurada
Eventos ativos
Filtros aplicados
Configurações adicionais
Exemplo de resposta:

[
  {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "enabled": true,
    "url": "https://example.com/webhook",
    "events": ["messages", "messages_update"],
    "excludeMessages": ["wasSentByApi", "isGroupNo"],
    "addUrlEvents": true,
    "addUrlTypesMessages": true
  },
  {
    "id": "987fcdeb-51k3-09j8-x543-864297539100",
    "enabled": true,
    "url": "https://outro-endpoint.com/webhook",
    "events": ["connection", "presence"],
    "excludeMessages": [],
    "addUrlEvents": false,
    "addUrlTypesMessages": false
  }
]
A resposta é sempre um array, mesmo quando há apenas um webhook configurado.

Responses

200
Configuração do webhook retornada com sucesso

401
Token inválido ou não fornecido

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/webhook
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/webhook \
  --header 'Accept: application/json'


  POST
/webhook
Configurar Webhook da Instância
Gerencia a configuração de webhooks para receber eventos em tempo real da instância. Permite gerenciar múltiplos webhooks por instância através do campo ID e action.

🚀 Modo Simples (Recomendado)
Uso mais fácil - sem complexidade de IDs:

Não inclua action nem id no payload
Gerencia automaticamente um único webhook por instância
Cria novo ou atualiza o existente automaticamente
Recomendado: Sempre use "excludeMessages": ["wasSentByApi"] para evitar loops
Exemplo: {"url": "https://meusite.com/webhook", "events": ["messages"], "excludeMessages": ["wasSentByApi"]}
🧪 Sites para Testes (ordenados por qualidade)
Para testar webhooks durante desenvolvimento:

https://webhook.cool/ - ⭐ Melhor opção (sem rate limit, interface limpa)
https://rbaskets.in/ - ⭐ Boa alternativa (confiável, baixo rate limit)
https://webhook.site/ - ⚠️ Evitar se possível (rate limit agressivo)
⚙️ Modo Avançado (Para múltiplos webhooks)
Para usuários que precisam de múltiplos webhooks por instância:

💡 Dica: Mesmo precisando de múltiplos webhooks, considere usar addUrlEvents no modo simples. Um único webhook pode receber diferentes tipos de eventos em URLs específicas (ex: /webhook/message, /webhook/connection), eliminando a necessidade de múltiplos webhooks.

Criar Novo Webhook:

Use action: "add"
Não inclua id no payload
O sistema gera ID automaticamente
Atualizar Webhook Existente:

Use action: "update"
Inclua o id do webhook no payload
Todos os campos serão atualizados
Remover Webhook:

Use action: "delete"
Inclua apenas o id do webhook
Outros campos são ignorados
Eventos Disponíveis
connection: Alterações no estado da conexão
history: Recebimento de histórico de mensagens
messages: Novas mensagens recebidas
messages_update: Atualizações em mensagens existentes
call: Eventos de chamadas VoIP
contacts: Atualizações na agenda de contatos
presence: Alterações no status de presença
groups: Modificações em grupos
labels: Gerenciamento de etiquetas
chats: Eventos de conversas
chat_labels: Alterações em etiquetas de conversas
blocks: Bloqueios/desbloqueios
leads: Atualizações de leads
sender: Atualizações de campanhas, quando inicia, e quando completa
Remover mensagens com base nos filtros:

wasSentByApi: Mensagens originadas pela API ⚠️ IMPORTANTE: Use sempre este filtro para evitar loops em automações
wasNotSentByApi: Mensagens não originadas pela API
fromMeYes: Mensagens enviadas pelo usuário
fromMeNo: Mensagens recebidas de terceiros
isGroupYes: Mensagens em grupos
isGroupNo: Mensagens em conversas individuais
💡 Prevenção de Loops: Se você tem automações que enviam mensagens via API, sempre inclua "excludeMessages": ["wasSentByApi"] no seu webhook. Caso prefira receber esses eventos, certifique-se de que sua automação detecta mensagens enviadas pela própria API para não criar loops infinitos.

Ações Suportadas:

add: Registrar novo webhook
delete: Remover webhook existente
Parâmetros de URL:

addUrlEvents (boolean): Quando ativo, adiciona o tipo do evento como path parameter na URL. Exemplo: https://api.example.com/webhook/{evento}
addUrlTypesMessages (boolean): Quando ativo, adiciona o tipo da mensagem como path parameter na URL. Exemplo: https://api.example.com/webhook/{tipo_mensagem}
Combinações de Parâmetros:

Ambos ativos: https://api.example.com/webhook/{evento}/{tipo_mensagem} Exemplo real: https://api.example.com/webhook/message/conversation
Apenas eventos: https://api.example.com/webhook/message
Apenas tipos: https://api.example.com/webhook/conversation
Notas Técnicas:

Os parâmetros são adicionados na ordem: evento → tipo mensagem
A URL deve ser configurada para aceitar esses parâmetros dinâmicos
Funciona com qualquer combinação de eventos/mensagens
Request
Body
id
string
ID único do webhook (necessário para update/delete)

Example: "123e4567-e89b-12d3-a456-426614174000"

enabled
boolean
Habilita/desabilita o webhook

Example: true

url
string
required
URL para receber os eventos

Example: "https://example.com/webhook"

events
array
Lista de eventos monitorados

excludeMessages
array
Filtros para excluir tipos de mensagens

addUrlEvents
boolean
Adiciona o tipo do evento como parâmetro na URL.

false (padrão): URL normal
true: Adiciona evento na URL (ex: /webhook/message)
addUrlTypesMessages
boolean
Adiciona o tipo da mensagem como parâmetro na URL.

false (padrão): URL normal
true: Adiciona tipo da mensagem (ex: /webhook/conversation)
action
string
Ação a ser executada:

add: criar novo webhook
update: atualizar webhook existente (requer id)
delete: remover webhook (requer apenas id) Se não informado, opera no modo simples (único webhook)
Responses

200
Webhook configurado ou atualizado com sucesso

400
Requisição inválida

401
Token inválido ou não fornecido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/webhook
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/webhook \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "url": "https://webhook.cool/example",
  "events": [
    "messages",
    "connection"
  ]
}'


GET
/sse
Server-Sent Events (SSE)
Receber eventos em tempo real via Server-Sent Events (SSE)

Funcionalidades Principais:
Configuração de URL para recebimento de eventos
Seleção granular de tipos de eventos
Filtragem avançada de mensagens
Parâmetros adicionais na URL
Gerenciamento múltiplo de webhooks
Eventos Disponíveis:

connection: Alterações no estado da conexão
history: Recebimento de histórico de mensagens
messages: Novas mensagens recebidas
messages_update: Atualizações em mensagens existentes
call: Eventos de chamadas VoIP
contacts: Atualizações na agenda de contatos
presence: Alterações no status de presença
groups: Modificações em grupos
labels: Gerenciamento de etiquetas
chats: Eventos de conversas
chat_labels: Alterações em etiquetas de conversas
blocks: Bloqueios/desbloqueios
leads: Atualizações de leads
Estabelece uma conexão persistente para receber eventos em tempo real. Este endpoint:

Requer autenticação via token

Mantém uma conexão HTTP aberta com o cliente

Envia eventos conforme ocorrem no servidor

Suporta diferentes tipos de eventos

Exemplo de uso:


const eventSource = new
EventSource('/sse?token=SEU_TOKEN&events=chats,messages');


eventSource.onmessage = function(event) {
  const data = JSON.parse(event.data);
  console.log('Novo evento:', data);
};


eventSource.onerror = function(error) {
  console.error('Erro na conexão SSE:', error);
};

Estrutura de um evento:


{
  "type": "message",
  "data": {
    "id": "3EB0538DA65A59F6D8A251",
    "from": "5511999999999@s.whatsapp.net",
    "to": "5511888888888@s.whatsapp.net",
    "text": "Olá!",
    "timestamp": 1672531200000
  }
}

Parameters
Query Parameters
token
string
required
Token de autenticação da instância

events
string
required
Tipos de eventos a serem recebidos (separados por vírgula)

Try It
Code
GET
https://growhatshomolog.uazapi.com/sse
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/sse \
  --header 'Accept: application/json'