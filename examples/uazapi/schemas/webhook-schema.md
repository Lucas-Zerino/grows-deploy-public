SCHEMA
Webhook
Configuração completa de webhook com filtros e opções avançadas

Properties
id
string
ID único gerado automaticamente

instance_id
string
ID da instância associada

enabled
boolean
Webhook ativo/inativo

url
string
required
URL de destino dos eventos

events
array
required
Tipos de eventos monitorados

AddUrlTypesMessages
boolean
Incluir na URLs o tipo de mensagem

addUrlEvents
boolean
Incluir na URL o nome do evento

excludeMessages
array
Filtros para excluir tipos de mensagens

created
string
Data de criação (automática)

updated
string
Data da última atualização (automática)

Example
{
  "id": "wh_9a8b7c6d5e",
  "instance_id": "inst_12345",
  "enabled": true,
  "url": "https://webhook.cool/example",
  "events": [
    "messages",
    "connection"
  ],
  "AddUrlTypesMessages": false,
  "addUrlEvents": false,
  "excludeMessages": [],
  "created": "2025-01-24T16:20:00Z",
  "updated": "2025-01-24T16:25:00Z"
}