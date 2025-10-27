POST
/message/presence
Enviar atualização de presença
Envia uma atualização de presença para um contato ou grupo de forma assíncrona.

🔄 Comportamento Assíncrono:
Execução independente: A presença é gerenciada em background, não bloqueia o retorno da API
Limite máximo: 5 minutos de duração (300 segundos)
Tick de atualização: Reenvia a presença a cada 10 segundos
Cancelamento automático: Presença é cancelada automaticamente ao enviar uma mensagem para o mesmo chat
📱 Tipos de presença suportados:
composing: Indica que você está digitando uma mensagem
recording: Indica que você está gravando um áudio
paused: Remove/cancela a indicação de presença atual
⏱️ Controle de duração:
Sem delay: Usa limite padrão de 5 minutos
Com delay: Usa o valor especificado (máximo 5 minutos)
Cancelamento: Envio de mensagem cancela presença automaticamente
📋 Exemplos de uso:
Digitar por 30 segundos:
{
  "number": "5511999999999",
  "presence": "composing",
  "delay": 30000
}
Gravar áudio por 1 minuto:
{
  "number": "5511999999999",
  "presence": "recording",
  "delay": 60000
}
Cancelar presença atual:
{
  "number": "5511999999999",
  "presence": "paused"
}
Usar limite máximo (5 minutos):
{
  "number": "5511999999999",
  "presence": "composing"
}
Request
Body
number
string
required
Número do destinatário no formato internacional (ex: 5511999999999)

Example: "5511999999999"

presence
string
required
Tipo de presença a ser enviada

Example: "composing"

delay
integer
Duração em milissegundos que a presença ficará ativa (máximo 5 minutos = 300000ms). Se não informado ou valor maior que 5 minutos, usa o limite padrão de 5 minutos. A presença é reenviada a cada 10 segundos durante este período.

Example: 30000

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
https://growhatshomolog.uazapi.com/message/presence
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/presence \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "presence": "composing",
  "delay": 30000
}'