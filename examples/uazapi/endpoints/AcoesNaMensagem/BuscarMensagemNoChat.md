POST
/message/find
Buscar mensagens em um chat
Busca mensagens com múltiplos filtros disponíveis. Este endpoint permite:

Busca por ID específico: Use id para encontrar uma mensagem exata
Filtrar por chat: Use chatid para mensagens de uma conversa específica
Filtrar por rastreamento: Use track_source e track_id para mensagens com dados de tracking
Limitar resultados: Use limit para controlar quantas mensagens retornar
Ordenação: Resultados ordenados por data (mais recentes primeiro)
Request
Body
id
string
ID específico da mensagem para busca exata

Example: "user123:r3EB0538"

chatid
string
ID do chat no formato internacional

Example: "5511999999999@s.whatsapp.net"

track_source
string
Origem do rastreamento para filtrar mensagens

Example: "chatwoot"

track_id
string
ID de rastreamento para filtrar mensagens

Example: "msg_123456789"

limit
integer
Número máximo de mensagens a retornar

Example: 10

Responses

200
Lista de mensagens encontradas

400
Parâmetros inválidos

401
Token inválido ou expirado

404
Chat não encontrado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/find
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/find \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "chatid": "5511999999999@s.whatsapp.net",
  "limit": 10
}'