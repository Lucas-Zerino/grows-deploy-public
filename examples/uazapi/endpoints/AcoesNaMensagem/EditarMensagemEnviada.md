POST
/message/edit
Edita uma mensagem enviada
Edita o conteúdo de uma mensagem já enviada usando a funcionalidade nativa do WhatsApp.

O endpoint realiza:

Busca a mensagem original no banco de dados usando o ID fornecido
Edita o conteúdo da mensagem para o novo texto no WhatsApp
Gera um novo ID para a mensagem editada
Retorna objeto de mensagem completo seguindo o padrão da API
Dispara eventos SSE/Webhook automaticamente
Importante:

Só é possível editar mensagens enviadas pela própria instância
A mensagem deve existir no banco de dados
O ID pode ser fornecido no formato completo (owner:messageid) ou apenas messageid
A mensagem deve estar dentro do prazo permitido pelo WhatsApp para edição
Request
Body
id
string
required
ID único da mensagem que será editada (formato owner:messageid ou apenas messageid)

Example: "3A12345678901234567890123456789012"

text
string
required
Novo conteúdo de texto da mensagem

Example: "Texto editado da mensagem"

Responses

200
Mensagem editada com sucesso

400
Dados inválidos na requisição

401
Sem sessão ativa

404
Mensagem não encontrada

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/edit
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/edit \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "id": "3A12345678901234567890123456789012",
  "text": "Texto editado da mensagem"
}'