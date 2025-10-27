POST
/chat/delete
Deleta chat
Deleta um chat e/ou suas mensagens do WhatsApp e/ou banco de dados. Você pode escolher deletar:

Apenas do WhatsApp
Apenas do banco de dados
Apenas as mensagens do banco de dados
Qualquer combinação das opções acima
Request
Body
number
string
required
Número do chat no formato internacional. Para grupos use o ID completo do grupo.

Example: "5511999999999"

deleteChatDB
boolean
Se true, deleta o chat do banco de dados

Example: true

deleteMessagesDB
boolean
Se true, deleta todas as mensagens do chat do banco de dados

Example: true

deleteChatWhatsApp
boolean
Se true, deleta o chat do WhatsApp

Example: true

Responses

200
Operação realizada com sucesso

400
Erro nos parâmetros da requisição

401
Token inválido ou não fornecido

404
Chat não encontrado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/delete
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/delete \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "deleteChatDB": true,
  "deleteMessagesDB": true,
  "deleteChatWhatsApp": true
}'


POST
/chat/archive
Arquivar/desarquivar chat
Altera o estado de arquivamento de um chat do WhatsApp.

Quando arquivado, o chat é movido para a seção de arquivados no WhatsApp
A ação é sincronizada entre todos os dispositivos conectados
Não afeta as mensagens ou o conteúdo do chat
Request
Body
number
string
required
Número do telefone (formato E.164) ou ID do grupo

Example: "5511999999999"

archive
boolean
required
true para arquivar, false para desarquivar

Example: true

Responses

200
Chat arquivado/desarquivado com sucesso

400
Dados da requisição inválidos

401
Token de autenticação ausente ou inválido

500
Erro ao executar a operação
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/archive
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/archive \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "archive": true
}'


POST
/chat/read
Marcar chat como lido/não lido
Atualiza o status de leitura de um chat no WhatsApp.

Quando um chat é marcado como lido:

O contador de mensagens não lidas é zerado
O indicador visual de mensagens não lidas é removido
O remetente recebe confirmação de leitura (se ativado)
Quando marcado como não lido:

O chat aparece como pendente de leitura
Não afeta as confirmações de leitura já enviadas
Request
Body
number
string
required
Identificador do chat no formato:

Para usuários: [número]@s.whatsapp.net (ex: 5511999999999@s.whatsapp.net)
Para grupos: [id-grupo]@g.us (ex: 123456789-987654321@g.us)
Example: "5511999999999@s.whatsapp.net"

read
boolean
required
true: marca o chat como lido
false: marca o chat como não lido
Responses

200
Status de leitura atualizado com sucesso

401
Token de autenticação ausente ou inválido

404
Chat não encontrado

500
Erro ao atualizar status de leitura
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/read
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/read \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999@s.whatsapp.net",
  "read": false
}'


Request
Body
number
string
required
ID do chat no formato 123456789@s.whatsapp.net ou 123456789-123456@g.us

Example: "5511999999999@s.whatsapp.net"

muteEndTime
integer
required
Duração do silenciamento:

0 = Remove silenciamento
8 = Silencia por 8 horas
168 = Silencia por 1 semana
-1 = Silencia permanentemente
Example: 8

Responses

200
Chat silenciado com sucesso

400
Duração inválida ou formato de número incorreto

401
Token inválido ou ausente

404
Chat não encontrado
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/mute
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/mute \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999@s.whatsapp.net",
  "muteEndTime": 8
}'


Fixar/desafixar chat
Fixa ou desafixa um chat no topo da lista de conversas. Chats fixados permanecem no topo mesmo quando novas mensagens são recebidas em outros chats.

Request
Body
number
string
required
Número do chat no formato internacional completo (ex: "5511999999999") ou ID do grupo (ex: "123456789-123456@g.us")

Example: "5511999999999"

pin
boolean
required
Define se o chat deve ser fixado (true) ou desafixado (false)

Example: true

Responses

200
Chat fixado/desafixado com sucesso

400
Erro na requisição

401
Não autorizado
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/pin
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/pin \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "pin": true
}'


POST
/chat/find
Busca chats com filtros
Busca chats com diversos filtros e ordenação. Suporta filtros em todos os campos do chat, paginação e ordenação customizada.

Operadores de filtro:

~ : LIKE (contém)
!~ : NOT LIKE (não contém)
!= : diferente
>= : maior ou igual
> : maior que
<= : menor ou igual
< : menor que
Sem operador: LIKE (contém)
Request
Body
operator
string
Operador lógico entre os filtros

sort
string
Campo para ordenação (+/-campo). Ex -wa_lastMsgTimestamp

limit
integer
Limite de resultados por página

offset
integer
Offset para paginação

wa_fastid
string
wa_chatid
string
wa_archived
boolean
wa_contactName
string
wa_name
string
name
string
wa_isBlocked
boolean
wa_isGroup
boolean
wa_isGroup_admin
boolean
wa_isGroup_announce
boolean
wa_isGroup_member
boolean
wa_isPinned
boolean
wa_label
string
lead_tags
string
lead_isTicketOpen
boolean
lead_assignedAttendant_id
string
lead_status
string
Responses

200
Lista de chats encontrados
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/find
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/find \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "operator": "AND",
  "sort": "-wa_lastMsgTimestamp",
  "limit": 50,
  "offset": 0,
  "wa_isGroup": true,
  "lead_status": "~novo",
  "wa_label": "~importante"
}'


GET
/chat/count
Retorna contadores de chats
Retorna estatísticas e contadores agregados dos chats, incluindo:

Total de chats
Chats não lidos
Chats arquivados
Chats fixados
Chats bloqueados
Grupos e status de grupos
Responses

200
Contadores retornados com sucesso

401
Não autorizado - token inválido

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/chat/count
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/chat/count \
  --header 'Accept: application/json'