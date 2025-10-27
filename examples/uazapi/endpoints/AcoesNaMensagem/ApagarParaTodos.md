POST
/message/delete
Apagar Mensagem Para Todos
Apaga uma mensagem para todos os participantes da conversa.

Funcionalidades:
Apaga mensagens em conversas individuais ou grupos
Funciona com mensagens enviadas pelo usuário ou recebidas
Atualiza o status no banco de dados
Envia webhook de atualização
Notas Técnicas:

O ID da mensagem pode ser fornecido em dois formatos:
ID completo (contém ":"): usado diretamente
ID curto: concatenado com o owner para busca
Gera evento webhook do tipo "messages_update"
Atualiza o status da mensagem para "Deleted"
Request
Body
id
string
required
ID da mensagem a ser apagada

Responses

200
Mensagem apagada com sucesso

400
Payload inválido ou ID de chat/sender inválido

401
Token não fornecido

404
Mensagem não encontrada

500
Erro interno do servidor ou sessão não iniciada
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/delete
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/delete \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "id": "string"
}'