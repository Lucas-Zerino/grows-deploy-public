POST
/instance/connect
Conectar instância ao WhatsApp
Inicia o processo de conexão de uma instância ao WhatsApp. Este endpoint:

Requer o token de autenticação da instância
Recebe o número de telefone associado à conta WhatsApp
Gera um QR code caso não passe o campo phone
Ou Gera código de pareamento se passar o o campo phone
Atualiza o status da instância para "connecting"
O processo de conexão permanece pendente até que:

O QR code seja escaneado no WhatsApp do celular, ou
O código de pareamento seja usado no WhatsApp
Timeout de 2 minutos para QRCode seja atingido ou 5 minutos para o código de pareamento
Use o endpoint /instance/status para monitorar o progresso da conexão.

Estados possíveis da instância:

disconnected: Desconectado do WhatsApp
connecting: Em processo de conexão
connected: Conectado e autenticado
Exemplo de requisição:

{
  "phone": "5511999999999"
}
Request
Body
phone
string
required
Número de telefone no formato internacional (ex: 5511999999999)

Example: "5511999999999"

Responses

200
Sucesso

401
Token inválido/expirado

404
Instância não encontrada

429
Limite de conexões simultâneas atingido

500
Erro interno
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/connect
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/connect \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "phone": "5511999999999"
}'


POST
/instance/disconnect
Desconectar instância
Desconecta a instância do WhatsApp, encerrando a sessão atual. Esta operação:

Encerra a conexão ativa

Requer novo QR code para reconectar

Diferenças entre desconectar e hibernar:

Desconectar: Encerra completamente a sessão, exigindo novo login

Hibernar: Mantém a sessão ativa, apenas pausa a conexão

Use este endpoint para:

Encerrar completamente uma sessão

Forçar uma nova autenticação

Limpar credenciais de uma instância

Reiniciar o processo de conexão

Estados possíveis após desconectar:

disconnected: Desconectado do WhatsApp

connecting: Em processo de reconexão (após usar /instance/connect)

Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/disconnect
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/disconnect \
  --header 'Accept: application/json'


  GET
/instance/status
Verificar status da instância
Retorna o status atual de uma instância, incluindo:

Estado da conexão (disconnected, connecting, connected)
QR code atualizado (se em processo de conexão)
Código de pareamento (se disponível)
Informações da última desconexão
Detalhes completos da instância
Este endpoint é particularmente útil para:

Monitorar o progresso da conexão
Obter QR codes atualizados durante o processo de conexão
Verificar o estado atual da instância
Identificar problemas de conexão
Estados possíveis:

disconnected: Desconectado do WhatsApp
connecting: Em processo de conexão (aguardando QR code ou código de pareamento)
connected: Conectado e autenticado com sucesso
Responses

200
Sucesso

401
Token inválido/expirado

404
Instância não encontrada

500
Erro interno
Try It
Code
GET
https://growhatshomolog.uazapi.com/instance/status
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/instance/status \
  --header 'Accept: application/json'


  POST
/instance/updateInstanceName
Atualizar nome da instância
Atualiza o nome de uma instância WhatsApp existente. O nome não precisa ser único.

Request
Body
name
string
required
Novo nome para a instância

Example: "Minha Nova Instância 2024!@#"

Responses

200
Sucesso

401
Token inválido/expirado

404
Instância não encontrada

500
Erro interno
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/updateInstanceName
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/updateInstanceName \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "name": "Minha Nova Instância 2024!@#"
}'


DELETE
/instance
Deletar instância
Remove a instância do sistema.

Responses

200
Instância deletada com sucesso

401
Falha na autenticação

404
Instância não encontrada

500
Erro interno do servidor
Try It
Code
DELETE
https://growhatshomolog.uazapi.com/instance
Language

cURL

curl --request DELETE \
  --url https://growhatshomolog.uazapi.com/instance \
  --header 'Accept: application/json'

  GET
/instance/privacy
Buscar configurações de privacidade
Busca as configurações de privacidade atuais da instância do WhatsApp.

Importante - Diferença entre Status e Broadcast:

Status: Refere-se ao recado personalizado que aparece embaixo do nome do usuário (ex: "Disponível", "Ocupado", texto personalizado)
Broadcast: Refere-se ao envio de "stories/reels" (fotos/vídeos temporários)
Limitação: As configurações de privacidade do broadcast (stories/reels) não estão disponíveis para alteração via API.

Retorna todas as configurações de privacidade como quem pode:

Adicionar aos grupos
Ver visto por último
Ver status (recado embaixo do nome)
Ver foto de perfil
Receber confirmação de leitura
Ver status online
Fazer chamadas
Responses

200
Configurações de privacidade obtidas com sucesso

401
Token de autenticação inválido

500
Erro interno do servidor
Try It
Code
GET
https://growhatshomolog.uazapi.com/instance/privacy
Language

cURL

curl --request GET \
  --url https://growhatshomolog.uazapi.com/instance/privacy \
  --header 'Accept: application/json'


  POST
/instance/privacy
Alterar configurações de privacidade
Altera uma ou múltiplas configurações de privacidade da instância do WhatsApp de forma otimizada.

Importante - Diferença entre Status e Broadcast:

Status: Refere-se ao recado personalizado que aparece embaixo do nome do usuário (ex: "Disponível", "Ocupado", texto personalizado)
Broadcast: Refere-se ao envio de "stories/reels" (fotos/vídeos temporários)
Limitação: As configurações de privacidade do broadcast (stories/reels) não estão disponíveis para alteração via API.

Características:

✅ Eficiência: Altera apenas configurações que realmente mudaram
✅ Flexibilidade: Pode alterar uma ou múltiplas configurações na mesma requisição
✅ Feedback completo: Retorna todas as configurações atualizadas
Formato de entrada:

{
  "groupadd": "contacts",
  "last": "none",
  "status": "contacts"
}
Tipos de privacidade disponíveis:

groupadd: Quem pode adicionar aos grupos
last: Quem pode ver visto por último
status: Quem pode ver status (recado embaixo do nome)
profile: Quem pode ver foto de perfil
readreceipts: Confirmação de leitura
online: Quem pode ver status online
calladd: Quem pode fazer chamadas
Valores possíveis:

all: Todos
contacts: Apenas contatos
contact_blacklist: Contatos exceto bloqueados
none: Ninguém
match_last_seen: Corresponder ao visto por último (apenas para online)
known: Números conhecidos (apenas para calladd)
Request
Body
groupadd
string
Quem pode adicionar aos grupos. Valores - all, contacts, contact_blacklist, none

last
string
Quem pode ver visto por último. Valores - all, contacts, contact_blacklist, none

status
string
Quem pode ver status (recado embaixo do nome). Valores - all, contacts, contact_blacklist, none

profile
string
Quem pode ver foto de perfil. Valores - all, contacts, contact_blacklist, none

readreceipts
string
Confirmação de leitura. Valores - all, none

online
string
Quem pode ver status online. Valores - all, match_last_seen

calladd
string
Quem pode fazer chamadas. Valores - all, known

Responses

200
Configuração de privacidade alterada com sucesso

400
Dados de entrada inválidos

401
Token de autenticação inválido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/privacy
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/privacy \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "groupadd": "contacts"
}'


POST
/instance/presence
Atualizar status de presença da instância
Atualiza o status de presença global da instância do WhatsApp. Este endpoint permite:

Definir se a instância está disponível (Aparece "online") ou indisponível
Controlar o status de presença para todos os contatos
Salvar o estado atual da presença na instância
Tipos de presença suportados:

available: Marca a instância como disponível/online
unavailable: Marca a instância como indisponível/offline
Atenção:

O status de presença pode ser temporariamente alterado para "available" (online) em algumas situações internas da API, e com isso o visto por último também pode ser atualizado.
Caso isso for um problema, considere alterar suas configurações de privacidade no WhatsApp para não mostrar o visto por último e/ou quem pode ver seu status "online".
⚠️ Importante - Limitação do Presence "unavailable":

Quando a API é o único dispositivo ativo: Confirmações de entrega/leitura (ticks cinzas/azuis) não são enviadas nem recebidas
Impacto: Eventos message_update com status de entrega podem não ser recebidos
Solução: Se precisar das confirmações, mantenha WhatsApp Web ou aplicativo móvel ativo ou use presence "available"
Exemplo de requisição:

{
  "presence": "available"
}
Exemplo de resposta:

{
  "response": "Presence updated successfully"
}
Erros comuns:

401: Token inválido ou expirado
400: Valor de presença inválido
500: Erro ao atualizar presença
Request
Body
presence
string
required
Status de presença da instância

Example: "available"

Responses

200
Presença atualizada com sucesso

400
Requisição inválida

401
Token inválido ou expirado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/instance/presence
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/instance/presence \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "presence": "available"
}'