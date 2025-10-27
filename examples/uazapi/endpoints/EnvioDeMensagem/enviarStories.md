POST
/send/status
Enviar Stories (Status)
Envia um story (status) com suporte para texto, imagem, vídeo e áudio.

Suporte a campos de rastreamento: Este endpoint também suporta track_source e track_id documentados na tag "Enviar Mensagem".

Tipos de Status
text: Texto com estilo e cor de fundo
image: Imagens com legenda opcional
video: Vídeos com thumbnail e legenda
audio: Áudio normal ou mensagem de voz (PTT)
Cores de Fundo
1-3: Tons de amarelo
4-6: Tons de verde
7-9: Tons de azul
10-12: Tons de lilás
13: Magenta
14-15: Tons de rosa
16: Marrom claro
17-19: Tons de cinza (19 é o padrão)
Fontes (para texto)
0: Padrão
1-8: Estilos alternativos
Limites
Texto: Máximo 656 caracteres
Imagem: JPG, PNG, GIF
Vídeo: MP4, MOV
Áudio: MP3, OGG, WAV (convertido para OGG/OPUS)
Exemplo
{
  "type": "text",
  "text": "Novidades chegando!",
  "background_color": 7,
  "font": 1
}
Request
Body
type
string
required
Tipo do status

Example: "text"

text
string
Texto principal ou legenda

Example: "Novidades chegando!"

background_color
integer
Código da cor de fundo

Example: 7

font
integer
Estilo da fonte (apenas para type=text)

Example: 1

file
string
URL ou Base64 do arquivo de mídia

Example: "https://example.com/video.mp4"

thumbnail
string
URL ou Base64 da miniatura (opcional para vídeos)

Example: "https://example.com/thumb.jpg"

mimetype
string
MIME type do arquivo (opcional)

Example: "video/mp4"

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
Status enviado com sucesso

400
Requisição inválida

401
Não autorizado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/status
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/status \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "type": "text",
  "text": "Novidades chegando!",
  "background_color": 7,
  "font": 1,
  "file": "https://example.com/video.mp4",
  "thumbnail": "https://example.com/thumb.jpg",
  "mimetype": "video/mp4",
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'