POST
/instance/init
Criar Instancia
Cria uma nova instância do WhatsApp. Para criar uma instância você precisa:

Ter um admintoken válido
Enviar pelo menos o nome da instância
A instância será criada desconectada
Será gerado um token único para autenticação
Após criar a instância, guarde o token retornado pois ele será necessário para todas as outras operações.

Estados possíveis da instância:

disconnected: Desconectado do WhatsApp
connecting: Em processo de conexão
connected: Conectado e autenticado
Campos administrativos (adminField01/adminField02) são opcionais e podem ser usados para armazenar metadados personalizados. OS valores desses campos são vísiveis para o dono da instancia via token, porém apenas o administrador da api (via admin token) pode editá-los.

Request
Body
name
string
required
Nome da instância

Example: "minha-instancia"

systemName
string
Nome do sistema (opcional, padrão 'uazapiGO' se não informado)

Example: "apilocal"

adminField01
string
Campo administrativo 1 para metadados personalizados (opcional)

Example: "custom-metadata-1"

adminField02
string
Campo administrativo 2 para metadados personalizados (opcional)

Example: "custom-metadata-2"

Responses

200
Sucesso

401
Token inválido/expirado

404
Instância não encontrada

500
Erro interno
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/init
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/init \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'admintoken: tbTmT1ashp1lSso3xGQxmWfSluH4EG5RXHQvnyq0sFxmImQXRh' \
  --data '{
  "name": "minha-instancia",
  "systemName": "apilocal",
  "adminField01": "custom-metadata-1",
  "adminField02": "custom-metadata-2"
}'



GET
/instance/all
Listar todas as instâncias
Retorna uma lista completa de todas as instâncias do sistema, incluindo:

ID e nome de cada instância
Status atual (disconnected, connecting, connected)
Data de criação
Última desconexão e motivo
Informações de perfil (se conectado)
Requer permissões de administrador.

Responses

200
Lista de instâncias retornada com sucesso

401
Token inválido ou expirado

403
Token de administrador inválido

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/instance/all
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/instance/all \
  --header 'Accept: application/json' \
  --header 'admintoken: tbTmT1ashp1lSso3xGQxmWfSluH4EG5RXHQvnyq0sFxmImQXRh'

  POST
/instance/updateAdminFields
Atualizar campos administrativos
Atualiza os campos administrativos (adminField01/adminField02) de uma instância.

Campos administrativos são opcionais e podem ser usados para armazenar metadados personalizados. Estes campos são persistidos no banco de dados e podem ser utilizados para integrações com outros sistemas ou para armazenamento de informações internas. OS valores desses campos são vísiveis para o dono da instancia via token, porém apenas o administrador da api (via admin token) pode editá-los.

Request
Body
id
string
required
ID da instância

Example: "inst_123456"

adminField01
string
Campo administrativo 1

Example: "clientId_456"

adminField02
string
Campo administrativo 2

Example: "integration_xyz"

Responses

200
Campos atualizados com sucesso

401
Token de administrador inválido

404
Instância não encontrada

500
Erro interno
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/updateAdminFields
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/updateAdminFields \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'admintoken: tbTmT1ashp1lSso3xGQxmWfSluH4EG5RXHQvnyq0sFxmImQXRh' \
  --data '{
  "id": "inst_123456",
  "adminField01": "clientId_456",
  "adminField02": "integration_xyz"
}'



GET
/globalwebhook
Ver Webhook Global
Retorna a configuração atual do webhook global, incluindo:

URL configurada
Eventos ativos
Filtros aplicados
Configurações adicionais
Exemplo de resposta:

{
  "enabled": true,
  "url": "https://example.com/webhook",
  "events": ["messages", "messages_update"],
  "excludeMessages": ["wasSentByApi", "isGroupNo"],
  "addUrlEvents": true,
  "addUrlTypesMessages": true
}
Responses

200
Configuração atual do webhook global

401
Token de administrador não fornecido

403
Token de administrador inválido ou servidor demo

404
Webhook global não encontrado
Try It
Code
GET
https://growhatshomolog.uazapi.com/globalwebhook
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/globalwebhook \
  --header 'Accept: application/json' \
  --header 'admintoken: tbTmT1ashp1lSso3xGQxmWfSluH4EG5RXHQvnyq0sFxmImQXRh'


  🚀 Configuração Simples (Recomendada)
Para a maioria dos casos de uso:

Configure apenas URL e eventos desejados
Modo simples por padrão (sem complexidade)
Recomendado: Sempre use "excludeMessages": ["wasSentByApi"] para evitar loops
Exemplo: {"url": "https://webhook.cool/global", "events": ["messages", "connection"], "excludeMessages": ["wasSentByApi"]}
🧪 Sites para Testes (ordenados por qualidade)
Para testar webhooks durante desenvolvimento:

https://webhook.cool/ - ⭐ Melhor opção (sem rate limit, interface limpa)
https://rbaskets.in/ - ⭐ Boa alternativa (confiável, baixo rate limit)
https://webhook.site/ - ⚠️ Evitar se possível (rate limit agressivo)
Funcionalidades Principais:
Configuração de URL para recebimento de eventos
Seleção granular de tipos de eventos
Filtragem avançada de mensagens
Parâmetros adicionais na URL
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
sender: Atualizações de campanhas, quando inicia, e quando completa
Remover mensagens com base nos filtros:

wasSentByApi: Mensagens originadas pela API ⚠️ IMPORTANTE: Use sempre este filtro para evitar loops em automações
wasNotSentByApi: Mensagens não originadas pela API
fromMeYes: Mensagens enviadas pelo usuário
fromMeNo: Mensagens recebidas de terceiros
isGroupYes: Mensagens em grupos
isGroupNo: Mensagens em conversas individuais
💡 Prevenção de Loops Globais: O webhook global recebe eventos de TODAS as instâncias. Se você tem automações que enviam mensagens via API, sempre inclua "excludeMessages": ["wasSentByApi"]. Caso prefira receber esses eventos, certifique-se de que sua automação detecta mensagens enviadas pela própria API para não criar loops infinitos em múltiplas instâncias.

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
url
string
required
URL para receber os eventos

Example: "https://webhook.cool/global"

events
array
required
Lista de eventos monitorados

Example: ["messages","connection"]

excludeMessages
array
Filtros para excluir tipos de mensagens

Example: ["wasSentByApi"]

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
Responses

200
Webhook global configurado com sucesso

400
Payload inválido

401
Token de administrador não fornecido

403
Token de administrador inválido ou servidor demo

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/globalwebhook
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/globalwebhook \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'admintoken: tbTmT1ashp1lSso3xGQxmWfSluH4EG5RXHQvnyq0sFxmImQXRh' \
  --data '{
  "url": "https://webhook.cool/global",
  "events": [
    "messages",
    "connection"
  ],
  "excludeMessages": [
    "wasSentByApi"
  ]
}'