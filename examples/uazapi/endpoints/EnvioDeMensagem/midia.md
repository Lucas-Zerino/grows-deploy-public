POST
/send/media
Enviar mídia (imagem, vídeo, áudio ou documento)
Envia diferentes tipos de mídia para um contato ou grupo. Suporta URLs ou arquivos base64.

Tipos de Mídia Suportados
image: Imagens (JPG preferencialmente)
video: Vídeos (apenas MP4)
document: Documentos (PDF, DOCX, XLSX, etc)
audio: Áudio comum (MP3 ou OGG)
myaudio: Mensagem de voz (alternativa ao PTT)
ptt: Mensagem de voz (Push-to-Talk)
sticker: Figurinha/Sticker
Recursos Específicos
Upload por URL ou base64
Caption/legenda opcional com suporte a placeholders
Nome personalizado para documentos (docName)
Geração automática de thumbnails
Compressão otimizada conforme o tipo
Campos Comuns
Este endpoint suporta todos os campos opcionais comuns documentados na tag "Enviar Mensagem", incluindo: delay, readchat, readmessages, replyid, mentions, forward, track_source, track_id, placeholders e envio para grupos.

Exemplos Básicos
Imagem Simples
{
  "number": "5511999999999",
  "type": "image",
  "file": "https://exemplo.com/foto.jpg"
}
Documento com Nome
{
  "number": "5511999999999",
  "type": "document",
  "file": "https://exemplo.com/contrato.pdf",
  "docName": "Contrato.pdf",
  "text": "Segue o documento solicitado"
}
Request
Body
number
string
required
Número do destinatário (formato internacional)

Example: "5511999999999"

type
string
required
Tipo de mídia (image, video, document, audio, myaudio, ptt, sticker)

Example: "image"

file
string
required
URL ou base64 do arquivo

Example: "https://exemplo.com/imagem.jpg"

text
string
Texto descritivo (caption) - aceita placeholders

Example: "Veja esta foto!"

docName
string
Nome do arquivo (apenas para documents)

Example: "relatorio.pdf"

replyid
string
ID da mensagem para responder

Example: "3EB0538DA65A59F6D8A251"

mentions
string
Números para mencionar (separados por vírgula)

Example: "5511999999999,5511888888888"

readchat
boolean
Marca conversa como lida após envio

Example: true

readmessages
boolean
Marca últimas mensagens recebidas como lidas

Example: true

delay
integer
Atraso em milissegundos antes do envio, durante o atraso apacerá 'Digitando...' ou 'Gravando áudio...'

Example: 1000

forward
boolean
Marca a mensagem como encaminhada no WhatsApp

Example: true

track_source
string
Origem do rastreamento da mensagem

Example: "chatwoot"

track_id
string
ID para rastreamento da mensagem (aceita valores duplicados)

Example: "msg_123456789"

Responses

200
Mídia enviada com sucesso

400
Requisição inválida

401
Não autorizado

413
Arquivo muito grande

415
Formato de mídia não suportado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/media
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/media \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "type": "image",
  "file": "https://exemplo.com/foto.jpg"
}'