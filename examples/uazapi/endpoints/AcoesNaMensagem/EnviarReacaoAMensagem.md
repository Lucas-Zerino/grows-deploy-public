Exemplo de resposta:


{
  "success": true,
  "message": "Reaction sent",
  "reaction": {
    "id": "3EB0538DA65A59F6D8A251",
    "emoji": "👍",
    "timestamp": 1672531200000,
    "status": "sent"
  }
}

Exemplo de resposta ao remover reação:


{
  "success": true,
  "message": "Reaction removed",
  "reaction": {
    "id": "3EB0538DA65A59F6D8A251",
    "emoji": null,
    "timestamp": 1672531200000,
    "status": "removed"
  }
}

Parâmetros disponíveis:

number: Número do chat no formato internacional (ex: 5511999999999@s.whatsapp.net)

text: Emoji Unicode da reação (ou string vazia para remover reação)

id: ID da mensagem que receberá a reação

Erros comuns:

401: Token inválido ou expirado

400: Número inválido ou emoji não suportado

404: Mensagem não encontrada

500: Erro ao enviar reação

Limitações:

Só é possível reagir a mensagens enviadas por outros usuários

Não é possível reagir a mensagens antigas (mais de 7 dias)

O mesmo usuário só pode ter uma reação ativa por mensagem

Request
Body
number
string
required
Número do chat no formato internacional

Example: "5511999999999@s.whatsapp.net"

text
string
required
Emoji Unicode da reação (ou string vazia para remover reação)

Example: "👍"

id
string
required
ID da mensagem que receberá a reação

Example: "3EB0538DA65A59F6D8A251"

Responses

200
Reação enviada com sucesso

400
Erro nos dados da requisição

401
Não autorizado

404
Mensagem não encontrada

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/react
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/react \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999@s.whatsapp.net",
  "text": "👍",
  "id": "3EB0538DA65A59F6D8A251"
}'