POST
/group/create
Criar um novo grupo
Cria um novo grupo no WhatsApp com participantes iniciais.

Detalhes
Requer autenticação via token da instância
Os números devem ser fornecidos sem formatação (apenas dígitos)
Limitações
Mínimo de 1 participante além do criador
Comportamento
Retorna informações detalhadas do grupo criado
Inclui lista de participantes adicionados com sucesso/falha
Request
Body
name
string
required
Nome do grupo

Example: "uazapiGO grupo"

participants
array
required
Lista de números de telefone dos participantes iniciais

Example: ["5521987905995","5511912345678"]

Responses

200
Grupo criado com sucesso

400
Erro de payload inválido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/create
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/create \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "name": "Meu Novo Grupo",
  "participants": [
    "5521987905995"
  ]
}'


Request
Body
groupjid
string
required
JID (ID) do grupo no formato xxxxx@g.us

Example: "120363339858396166@g.us"

description
string
required
Nova descrição/tópico do grupo

Example: "Grupo oficial de suporte"

Responses

200
Descrição atualizada com sucesso

401
Token inválido ou ausente

403
Usuário não é administrador do grupo

404
Grupo não encontrado

413
Descrição excede o limite máximo permitido
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateDescription
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateDescription \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363339858396166@g.us",
  "description": "Grupo oficial de suporte"
}'

POST
/group/updateImage
Atualizar imagem do grupo
Altera a imagem do grupo especificado. A imagem pode ser enviada como URL ou como string base64.

Requisitos da imagem:

Formato: JPEG
Resolução máxima: 640x640 pixels
Imagens maiores ou diferente de JPEG não são aceitas pelo WhatsApp
Para remover a imagem atual, envie "remove" ou "delete" no campo image.

Request
Body
groupjid
string
required
JID do grupo

Example: "120363308883996631@g.us"

image
string
required
URL da imagem, string base64 ou "remove"/"delete" para remover. A imagem deve estar em formato JPEG e ter resolução máxima de 640x640.

Responses

200
Imagem atualizada com sucesso

400
Erro nos parâmetros da requisição

401
Token inválido ou expirado

403
Usuário não é administrador do grupo

413
Imagem muito grande

415
Formato de imagem inválido
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateImage
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateImage \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363308883996631@g.us",
  "image": "string"
}'


POST
/group/updateLocked
Configurar permissão de edição do grupo
Define se apenas administradores podem editar as informações do grupo. Quando bloqueado (locked=true), apenas administradores podem alterar nome, descrição, imagem e outras configurações do grupo. Quando desbloqueado (locked=false), qualquer participante pode editar as informações.

Importante:

Requer que o usuário seja administrador do grupo
Afeta edições de nome, descrição, imagem e outras informações do grupo
Não controla permissões de adição de membros
Request
Body
groupjid
string
required
Identificador único do grupo (JID)

Example: "120363308883996631@g.us"

locked
boolean
required
Define permissões de edição:

true = apenas admins podem editar infos do grupo
false = qualquer participante pode editar infos do grupo
Example: true

Responses

200
Operação realizada com sucesso

403
Usuário não é administrador do grupo

404
Grupo não encontrado
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateLocked
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateLocked \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363308883996631@g.us",
  "locked": true
}'


POST
/group/updateName
Atualizar nome do grupo
Altera o nome de um grupo do WhatsApp. Apenas administradores do grupo podem realizar esta operação. O nome do grupo deve seguir as diretrizes do WhatsApp e ter entre 1 e 25 caracteres.

Request
Body
groupjid
string
required
Identificador único do grupo no formato JID

Example: "120363339858396166@g.us"

name
string
required
Novo nome para o grupo

Example: "Grupo de Suporte"

Responses

200
Nome do grupo atualizado com sucesso

400
Erro de validação na requisição

401
Token de autenticação ausente ou inválido

403
Usuário não é administrador do grupo

404
Grupo não encontrado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateName
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateName \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363339858396166@g.us",
  "name": "Grupo de Suporte"
}'


POST
/group/updateParticipants
Gerenciar participantes do grupo
Gerencia participantes do grupo através de diferentes ações:

Adicionar ou remover participantes
Promover ou rebaixar administradores
Aprovar ou rejeitar solicitações pendentes
Requer que o usuário seja administrador do grupo para executar as ações.

Request
Body
groupjid
string
required
JID (identificador) do grupo

Example: "120363308883996631@g.us"

action
string
required
Ação a ser executada:

add: Adicionar participantes ao grupo
remove: Remover participantes do grupo
promote: Promover participantes a administradores
demote: Remover privilégios de administrador
approve: Aprovar solicitações pendentes de entrada
reject: Rejeitar solicitações pendentes de entrada
Example: "promote"

participants
array
required
Lista de números de telefone ou JIDs dos participantes. Para números de telefone, use formato internacional sem '+' ou espaços.

Example: ["5521987654321","5511999887766"]

Responses

200
Sucesso na operação

400
Erro nos parâmetros da requisição

403
Usuário não é administrador do grupo

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateParticipants
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateParticipants \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363308883996631@g.us",
  "action": "promote",
  "participants": [
    "5521987654321",
    "5511999887766"
  ]
}'


POST
/community/create
Criar uma comunidade
Cria uma nova comunidade no WhatsApp. Uma comunidade é uma estrutura que permite agrupar múltiplos grupos relacionados sob uma única administração.

A comunidade criada inicialmente terá apenas o grupo principal (announcements), e grupos adicionais podem ser vinculados posteriormente usando o endpoint /community/updategroups.

Observações importantes:

O número que cria a comunidade torna-se automaticamente o administrador
A comunidade terá um grupo principal de anúncios criado automaticamente
Request
Body
name
string
required
Nome da comunidade

Example: "Comunidade do Bairro"

Responses

200
Comunidade criada com sucesso

400
Erro na requisição

401
Token inválido ou não fornecido

403
Sem permissão para criar comunidades

429
Limite de criação de comunidades atingido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/community/create
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/community/create \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "name": "Comunidade do Bairro"
}'


POST
/community/editgroups
Gerenciar grupos em uma comunidade
Adiciona ou remove grupos de uma comunidade do WhatsApp. Apenas administradores da comunidade podem executar estas operações.

Funcionalidades
Adicionar múltiplos grupos simultaneamente a uma comunidade
Remover grupos de uma comunidade existente
Suporta operações em lote
Limitações
Os grupos devem existir previamente
A comunidade deve existir e o usuário deve ser administrador
Grupos já vinculados não podem ser adicionados novamente
Grupos não vinculados não podem ser removidos
Ações Disponíveis
add: Adiciona os grupos especificados à comunidade
remove: Remove os grupos especificados da comunidade
Request
Body
community
string
required
JID (identificador único) da comunidade

Example: "120363153742561022@g.us"

action
string
required
Tipo de operação a ser realizada:

add - Adiciona grupos à comunidade
remove - Remove grupos da comunidade
groupjids
array
required
Lista de JIDs dos grupos para adicionar ou remover

Example: ["120363324255083289@g.us","120363308883996631@g.us"]

Responses

200
Operação realizada com sucesso

400
Requisição inválida

401
Não autorizado

403
Usuário não é administrador da comunidade
Try It
Code
POST
https://growhatshomolog.uazapi.com/community/editgroups
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/community/editgroups \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "community": "120363153742561022@g.us",
  "action": "add",
  "groupjids": [
    "120363324255083289@g.us",
    "120363308883996631@g.us"
  ]
}'