POST
/profile/name
Altera o nome do perfil do WhatsApp
Altera o nome de exibição do perfil da instância do WhatsApp.

O endpoint realiza:

Atualiza o nome do perfil usando o WhatsApp AppState
Sincroniza a mudança com o servidor do WhatsApp
Retorna confirmação da alteração
Importante:

A instância deve estar conectada ao WhatsApp
O nome será visível para todos os contatos
Pode haver um limite de alterações por período (conforme WhatsApp)
Request
Body
name
string
required
Novo nome do perfil do WhatsApp

Example: "Minha Empresa - Atendimento"

Responses

200
Nome do perfil alterado com sucesso

400
Dados inválidos na requisição

401
Sem sessão ativa

403
Ação não permitida

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/profile/name
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/profile/name \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "name": "Minha Empresa - Atendimento"
}'



POST
/profile/image
Altera a imagem do perfil do WhatsApp
Altera a imagem de perfil da instância do WhatsApp.

O endpoint realiza:

Atualiza a imagem do perfil usando
Processa a imagem (URL, base64 ou comando de remoção)
Sincroniza a mudança com o servidor do WhatsApp
Retorna confirmação da alteração
Importante:

A instância deve estar conectada ao WhatsApp
A imagem será visível para todos os contatos
A imagem deve estar em formato JPEG e tamanho 640x640 pixels
Request
Body
image
string
required
Imagem do perfil. Pode ser:

URL da imagem (http/https)
String base64 da imagem
"remove" ou "delete" para remover a imagem atual
Example: "https://picsum.photos/640/640.jpg"

Responses

200
Imagem do perfil alterada com sucesso

400
Dados inválidos na requisição

401
Sem sessão ativa

403
Ação não permitida

413
Imagem muito grande

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/profile/image
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/profile/image \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "image": "https://picsum.photos/640/640.jpg"
}'