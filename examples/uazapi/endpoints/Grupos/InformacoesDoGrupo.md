POST
/group/info
Obter informações detalhadas de um grupo
Recupera informações completas de um grupo do WhatsApp, incluindo:

Detalhes do grupo
Participantes
Configurações
Link de convite (opcional)
Request
Body
groupjid
string
required
Identificador único do grupo (JID)

Example: "120363153742561022@g.us"

getInviteLink
boolean
Recuperar link de convite do grupo

Example: true

getRequestsParticipants
boolean
Recuperar lista de solicitações pendentes de participação

force
boolean
Forçar atualização, ignorando cache

Responses

200
Informações do grupo obtidas com sucesso

400
Código de convite inválido ou mal formatado

404
Grupo não encontrado ou link de convite expirado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/info
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/info \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363153742561022@g.us",
  "getInviteLink": true,
  "getRequestsParticipants": false,
  "force": false
}'



POST
/group/inviteInfo
Obter informações de um grupo pelo código de convite
Retorna informações detalhadas de um grupo usando um código de convite ou URL completo do WhatsApp.

Esta rota permite:

Recuperar informações básicas sobre um grupo antes de entrar
Validar um link de convite
Obter detalhes como nome do grupo, número de participantes e restrições de entrada
Request
Body
inviteCode
string
required
Código de convite ou URL completo do grupo. Pode ser um código curto ou a URL completa do WhatsApp.

Responses

200
Informações do grupo obtidas com sucesso

400
Código de convite inválido ou mal formatado

404
Grupo não encontrado ou link de convite expirado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/inviteInfo
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/inviteInfo \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "inviteCode": "string"
}'


GET
/group/invitelink/:groupJID
Gerar link de convite para um grupo
Retorna o link de convite para o grupo especificado. Esta operação requer que o usuário seja um administrador do grupo.

Parameters
Path Parameters
groupJID
string
required
Responses

200
Link de convite gerado com sucesso

400
Erro ao processar a solicitação

403
Usuário não tem permissão para gerar link

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/group/invitelink/:groupJID
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/group/invitelink/:groupJID \
  --header 'Accept: application/json'


  POST
/group/join
Entrar em um grupo usando código de convite
Permite entrar em um grupo do WhatsApp usando um código de convite ou URL completo.

Características:

Suporta código de convite ou URL completo
Valida o código antes de tentar entrar no grupo
Retorna informações básicas do grupo após entrada bem-sucedida
Trata possíveis erros como convite inválido ou expirado
Request
Body
inviteCode
string
required
Código de convite ou URL completo do grupo. Formatos aceitos:

Código completo: "IYnl5Zg9bUcJD32rJrDzO7"
URL completa: "https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7"
Example: "https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7"

Responses

200
Entrada no grupo realizada com sucesso

400
Código de convite inválido

403
Usuário já está no grupo ou não tem permissão para entrar

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/join
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/join \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "inviteCode": "https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7"
}'


POST
/group/leave
Sair de um grupo
Remove o usuário atual de um grupo específico do WhatsApp.

Requisitos:

O usuário deve estar conectado a uma instância válida
O usuário deve ser um membro do grupo
Comportamentos:

Se o usuário for o último administrador, o grupo será dissolvido
Se o usuário for um membro comum, será removido do grupo
Request
Body
groupjid
string
required
Identificador único do grupo (JID)

Formato: número@g.us
Exemplo válido: 120363324255083289@g.us
Example: "120363324255083289@g.us"

Responses

200
Saída do grupo realizada com sucesso

400
Erro de payload inválido

500
Erro interno do servidor ou falha na conexão
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/leave
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/leave \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363324255083289@g.us"
}'


GET
/group/list
Listar todos os grupos
Retorna uma lista com todos os grupos disponíveis para a instância atual do WhatsApp.

Recursos adicionais:

Suporta atualização forçada do cache de grupos
Recupera informações detalhadas de grupos conectados
Parameters
Query Parameters
force
boolean
Se definido como true, força a atualização do cache de grupos. Útil para garantir que as informações mais recentes sejam recuperadas.

Comportamentos:

false (padrão): Usa informações em cache
true: Busca dados atualizados diretamente do WhatsApp
noparticipants
boolean
Se definido como true, retorna a lista de grupos sem incluir os participantes. Útil para otimizar a resposta quando não há necessidade dos dados dos participantes.

Comportamentos:

false (padrão): Retorna grupos com lista completa de participantes
true: Retorna grupos sem incluir os participantes
Responses

200
Lista de grupos recuperada com sucesso

500
Erro interno do servidor ao recuperar grupos
Try It
Code
GET
https://growhatshomolog.uazapi.com/group/list
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/group/list \
  --header 'Accept: application/json'


  POST
/group/resetInviteCode
Resetar código de convite do grupo
Gera um novo código de convite para o grupo, invalidando o código de convite anterior. Somente administradores do grupo podem realizar esta ação.

Principais características:

Invalida o link de convite antigo
Cria um novo link único
Retorna as informações atualizadas do grupo
Request
Body
groupjid
string
required
Identificador único do grupo (JID)

Example: "120363308883996631@g.us"

Responses

200
Código de convite resetado com sucesso

400
Erro de validação

403
Usuário sem permissão

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/resetInviteCode
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/resetInviteCode \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363308883996631@g.us"
}'


POST
/group/updateAnnounce
Configurar permissões de envio de mensagens no grupo
Define as permissões de envio de mensagens no grupo, permitindo restringir o envio apenas para administradores.

Quando ativado (announce=true):

Apenas administradores podem enviar mensagens
Outros participantes podem apenas ler
Útil para anúncios importantes ou controle de spam
Quando desativado (announce=false):

Todos os participantes podem enviar mensagens
Configuração padrão para grupos normais
Requer que o usuário seja administrador do grupo para fazer alterações.

Request
Body
groupjid
string
required
Identificador único do grupo no formato xxxx@g.us

Example: "120363339858396166@g.us"

announce
boolean
required
Controla quem pode enviar mensagens no grupo

Example: true

Responses

200
Configuração atualizada com sucesso

401
Token de autenticação ausente ou inválido

403
Usuário não é administrador do grupo

404
Grupo não encontrado

500
Erro interno do servidor ou falha na API do WhatsApp
Try It
Code
POST
https://growhatshomolog.uazapi.com/group/updateAnnounce
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/group/updateAnnounce \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupjid": "120363339858396166@g.us",
  "announce": true
}'