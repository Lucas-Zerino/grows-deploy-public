POST
/message/markread
Marcar mensagens como lidas
Marca uma ou mais mensagens como lidas. Este endpoint permite:

Marcar múltiplas mensagens como lidas de uma vez
Atualizar o status de leitura no WhatsApp
Sincronizar o status de leitura entre dispositivos
Exemplo de requisição básica:

{
  "id": [
    "62AD1AD844E518180227BF68DA7ED710",
    "ECB9DE48EB41F77BFA8491BFA8D6EF9B"  
  ]
}
Exemplo de resposta:

{
  "success": true,
  "message": "Messages marked as read",
  "markedMessages": [
    {
      "id": "62AD1AD844E518180227BF68DA7ED710",
      "timestamp": 1672531200000
    },
    {
      "id": "ECB9DE48EB41F77BFA8491BFA8D6EF9B",
      "timestamp": 1672531300000
    }
  ]
}
Parâmetros disponíveis:

id: Lista de IDs das mensagens a serem marcadas como lidas
Erros comuns:

401: Token inválido ou expirado
400: Lista de IDs vazia ou inválida
404: Uma ou mais mensagens não encontradas
500: Erro ao marcar mensagens como lidas
Request
Body
id
array
required
Lista de IDs das mensagens a serem marcadas como lidas

Example: ["62AD1AD844E518180227BF68DA7ED710","ECB9DE48EB41F77BFA8491BFA8D6EF9B"]

Responses

200
Messages successfully marked as read

400
Invalid request payload or missing required fields

401
Unauthorized - invalid or missing token

500
Server error while processing the request
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/markread
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/markread \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "id": [
    "62AD1AD844E518180227BF68DA7ED710",
    "ECB9DE48EB41F77BFA8491BFA8D6EF9B"
  ]
}'