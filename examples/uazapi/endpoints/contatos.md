GET
/contacts
Retorna lista de contatos do WhatsApp
Retorna a lista de contatos salvos na agenda do celular e que estão no WhatsApp.

O endpoint realiza:

Busca todos os contatos armazenados
Retorna dados formatados incluindo JID e informações de nome
Responses

200
Lista de contatos retornada com sucesso

401
Sem sessão ativa

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/contacts
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/contacts \
  --header 'Accept: application/json'


  POST
/contact/add
Adiciona um contato à agenda
Adiciona um novo contato à agenda do celular.

O endpoint realiza:

Adiciona o contato à agenda usando o WhatsApp
Usa o campo 'name' tanto para o nome completo quanto para o primeiro nome
Salva as informações do contato na agenda do WhatsApp
Retorna informações do contato adicionado
Request
Body
phone
string
required
Número de telefone no formato internacional com código do país obrigatório. Para Brasil, deve começar com 55. Aceita variações com/sem símbolo +, com/sem parênteses, com/sem hífen e com/sem espaços. Também aceita formato JID do WhatsApp (@s.whatsapp.net). Não aceita contatos comerciais (@lid) nem grupos (@g.us).

name
string
required
Nome completo do contato (será usado como primeiro nome e nome completo)

Example: "João Silva"

Responses

200
Contato adicionado com sucesso

400
Dados inválidos na requisição

401
Sem sessão ativa

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/contact/add
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/contact/add \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "phone": "string",
  "name": "João Silva"
}'


POST
/contact/remove
Remove um contato da agenda
Remove um contato da agenda do celular.

O endpoint realiza:

Remove o contato da agenda usando o WhatsApp AppState
Atualiza a lista de contatos sincronizada
Retorna confirmação da remoção
Request
Body
phone
string
required
Número de telefone no formato internacional com código do país obrigatório. Para Brasil, deve começar com 55. Aceita variações com/sem símbolo +, com/sem parênteses, com/sem hífen e com/sem espaços. Também aceita formato JID do WhatsApp (@s.whatsapp.net). Não aceita contatos comerciais (@lid) nem grupos (@g.us).

Responses

200
Contato removido com sucesso

400
Dados inválidos na requisição

401
Sem sessão ativa

404
Contato não encontrado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/contact/remove
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/contact/remove \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "phone": "string"
}'


POST
/chat/details
Obter Detalhes Completos
Retorna informações completas sobre um contato ou chat, incluindo todos os campos disponíveis do modelo Chat.

Funcionalidades:
Retorna chat completo: Todos os campos do modelo Chat (mais de 60 campos)
Busca informações para contatos individuais e grupos
URLs de imagem em dois tamanhos: preview (menor) ou full (original)
Combina informações de diferentes fontes: WhatsApp, contatos salvos, leads
Atualiza automaticamente dados desatualizados no banco
Campos Retornados:
Informações básicas: id, wa_fastid, wa_chatid, owner, name, phone
Dados do WhatsApp: wa_name, wa_contactName, wa_archived, wa_isBlocked, etc.
Dados de lead/CRM: lead_name, lead_email, lead_status, lead_field01-20, etc.
Informações de grupo: wa_isGroup, wa_isGroup_admin, wa_isGroup_announce, etc.
Chatbot: chatbot_summary, chatbot_lastTrigger_id, chatbot_disableUntil, etc.
Configurações: wa_muteEndTime, wa_isPinned, wa_unreadCount, etc.
Comportamento:

Para contatos individuais:
Busca nome verificado do WhatsApp
Verifica nome salvo nos contatos
Formata número internacional
Calcula grupos em comum
Para grupos:
Busca nome do grupo
Verifica status de comunidade
Request
Body
number
string
required
Número do telefone ou ID do grupo

Example: "5511999999999"

preview
boolean
Controla o tamanho da imagem de perfil retornada:

true: Retorna imagem em tamanho preview (menor, otimizada para listagens)
false (padrão): Retorna imagem em tamanho full (resolução original, maior qualidade)
Responses

200
Informações completas do chat retornadas com sucesso

400
Payload inválido ou número inválido

401
Token não fornecido

500
Erro interno do servidor ou sessão não iniciada
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/details
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/details \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "preview": false
}'


POST
/chat/check
Verificar Números no WhatsApp
Verifica se números fornecidos estão registrados no WhatsApp e retorna informações detalhadas.

Funcionalidades:
Verifica múltiplos números simultaneamente
Suporta números individuais e IDs de grupo
Retorna nome verificado quando disponível
Identifica grupos e comunidades
Verifica subgrupos de comunidades
Comportamento específico:

Para números individuais:
Verifica registro no WhatsApp
Retorna nome verificado se disponível
Normaliza formato do número
Para grupos:
Verifica existência
Retorna nome do grupo
Retorna id do grupo de anúncios se buscado por id de comunidade
Request
Body
numbers
array
Lista de números ou IDs de grupo para verificar

Example: ["5511999999999","123456789@g.us"]

Responses

200
Resultado da verificação

400
Payload inválido ou sem números

401
Sem sessão ativa

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/chat/check
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/chat/check \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "numbers": [
    "5511999999999",
    "123456789@g.us"
  ]
}'