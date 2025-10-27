POST
/send/location-button
Solicitar localização do usuário
Este endpoint envia uma mensagem com um botão que solicita a localização do usuário. Quando o usuário clica no botão, o WhatsApp abre a interface para compartilhar a localização atual.

Campos Comuns
Este endpoint suporta todos os campos opcionais comuns documentados na tag "Enviar Mensagem", incluindo: delay, readchat, readmessages, replyid, mentions, forward, track_source, track_id, placeholders e envio para grupos.

Estrutura do Payload
{
  "number": "5511999999999",
  "text": "Por favor, compartilhe sua localização",
  "delay": 0,
  "readchat": true
}
Exemplo de Uso
{
  "number": "5511999999999",
  "text": "Para continuar o atendimento, clique no botão abaixo e compartilhe sua localização"
}
Nota: O botão de localização é adicionado automaticamente à mensagem

Request
Body
number
string
required
Número do destinatário (formato internacional)

Example: "5511999999999"

text
string
required
Texto da mensagem que será exibida

Example: "Por favor, compartilhe sua localização"

delay
integer
Atraso em milissegundos antes do envio

0
readchat
boolean
Se deve marcar a conversa como lida após envio

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

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/location-button
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/location-button \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "text": "Por favor, compartilhe sua localização",
  "delay": 0,
  "readchat": true,
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'