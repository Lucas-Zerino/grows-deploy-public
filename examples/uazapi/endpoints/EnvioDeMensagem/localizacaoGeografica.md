POST
/send/location
Enviar localização geográfica
Envia uma localização geográfica para um contato ou grupo.

Recursos Específicos
Coordenadas precisas (latitude e longitude obrigatórias)
Nome do local para identificação
Mapa interativo no WhatsApp para navegação
Pin personalizado com nome do local
Campos Comuns
Este endpoint suporta todos os campos opcionais comuns documentados na tag "Enviar Mensagem", incluindo: delay, readchat, readmessages, replyid, mentions, forward, track_source, track_id, placeholders e envio para grupos.

Exemplo Básico
{
  "number": "5511999999999",
  "name": "Maracanã",
  "address": "Av. Pres. Castelo Branco, Portão 3 - Maracanã, Rio de Janeiro - RJ, 20271-130",
  "latitude": -22.912982815767986,
  "longitude": -43.23028153499254
}
Request
Body
number
string
required
Número do destinatário (formato internacional)

Example: "5511999999999"

name
string
Nome do local

Example: "MASP"

address
string
Endereço completo do local

Example: "Av. Paulista, 1578 - Bela Vista"

latitude
number
required
Latitude (-90 a 90)

Example: -23.5616

longitude
number
required
Longitude (-180 a 180)

Example: -46.6562

replyid
string
ID da mensagem para responder

Example: "3EB0538DA65A59F6D8A251"

mentions
string
Números para mencionar (separados por vírgula)

Example: "5511999999999,5511888888888"

readchat
boolean
Marca conversa como lida após envio

Example: true

readmessages
boolean
Marca últimas mensagens recebidas como lidas

Example: true

delay
integer
Atraso em milissegundos antes do envio, durante o atraso apacerá 'Digitando...'

Example: 1000

forward
boolean
Marca a mensagem como encaminhada no WhatsApp

Example: true

track_source
string
Origem do rastreamento da mensagem

Example: "chatwoot"

track_id
string
ID para rastreamento da mensagem (aceita valores duplicados)

Example: "msg_123456789"

Responses

200
Localização enviada com sucesso

400
Requisição inválida

401
Não autorizado

429
Limite de requisições excedido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/location
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/location \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "name": "MASP",
  "address": "Av. Paulista, 1578 - Bela Vista",
  "latitude": -23.5616,
  "longitude": -46.6562,
  "replyid": "3EB0538DA65A59F6D8A251",
  "mentions": "5511999999999,5511888888888",
  "readchat": true,
  "readmessages": true,
  "delay": 1000,
  "forward": true,
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'