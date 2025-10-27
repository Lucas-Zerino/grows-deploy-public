POST
/send/contact
Enviar cartão de contato (vCard)
Envia um cartão de contato (vCard) para um contato ou grupo.

Recursos Específicos
vCard completo com nome, telefones, organização, email e URL
Múltiplos números de telefone (separados por vírgula)
Cartão clicável no WhatsApp para salvar na agenda
Informações profissionais (organização/empresa)
Campos Comuns
Este endpoint suporta todos os campos opcionais comuns documentados na tag "Enviar Mensagem", incluindo: delay, readchat, readmessages, replyid, mentions, forward, track_source, track_id, placeholders e envio para grupos.

Exemplo Básico
{
  "number": "5511999999999",
  "fullName": "João Silva",
  "phoneNumber": "5511999999999,5511888888888",
  "organization": "Empresa XYZ",
  "email": "joao.silva@empresa.com",
  "url": "https://empresa.com/joao"
}
Request
Body
number
string
required
Número do destinatário (formato internacional)

Example: "5511999999999"

fullName
string
required
Nome completo do contato

Example: "João Silva"

phoneNumber
string
required
Números de telefone (separados por vírgula)

Example: "5511999999999,5511888888888"

organization
string
Nome da organização/empresa

Example: "Empresa XYZ"

email
string
Endereço de email

Example: "joao@empresa.com"

url
string
URL pessoal ou da empresa

Example: "https://empresa.com/joao"

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
Cartão de contato enviado com sucesso

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
https://growhatshomolog.uazapi.com/send/contact
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/contact \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "fullName": "João Silva",
  "phoneNumber": "5511999999999,5511888888888",
  "organization": "Empresa XYZ",
  "email": "joao@empresa.com",
  "url": "https://empresa.com/joao",
  "replyid": "3EB0538DA65A59F6D8A251",
  "mentions": "5511999999999,5511888888888",
  "readchat": true,
  "readmessages": true,
  "delay": 1000,
  "forward": true,
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'