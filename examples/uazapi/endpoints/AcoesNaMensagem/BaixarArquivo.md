POST
/message/download
Baixar arquivo de uma mensagem
Baixa o arquivo associado a uma mensagem de mídia (imagem, vídeo, áudio, documento ou sticker).

Parâmetros
id (string, obrigatório): ID da mensagem
return_base64 (boolean, default: false): Retorna arquivo em base64
generate_mp3 (boolean, default: true): Para áudios, define formato de retorno
true: Retorna MP3
false: Retorna OGG
return_link (boolean, default: true): Retorna URL pública do arquivo
transcribe (boolean, default: false): Transcreve áudios para texto
openai_apikey (string, opcional): Chave OpenAI para transcrição
Se não informada, usa a chave salva na instância
Se informada, atualiza e salva na instância para próximas chamadas
download_quoted (boolean, default: false): Baixa mídia da mensagem citada
Útil para baixar conteúdo original de status do WhatsApp
Quando uma mensagem é resposta a um status, permite baixar a mídia do status original
Contextualização: Ao baixar a mídia citada, você identifica o contexto da conversa
Exemplo: Se alguém responde a uma promoção, baixando a mídia você saberá que a pergunta é sobre aquela promoção específica
Exemplos
Baixar áudio como MP3:
{
  "id": "7EB0F01D7244B421048F0706368376E0",
  "generate_mp3": true
}
Transcrever áudio:
{
  "id": "7EB0F01D7244B421048F0706368376E0",
  "transcribe": true
}
Apenas base64 (sem salvar):
{
  "id": "7EB0F01D7244B421048F0706368376E0",
  "return_base64": true,
  "return_link": false
}
Baixar mídia de status (mensagem citada):
{
  "id": "7EB0F01D7244B421048F0706368376E0",
  "download_quoted": true
}
Útil quando o cliente responde a uma promoção/status - você baixa a mídia original para entender sobre qual produto/oferta ele está perguntando.

Resposta
{
  "fileURL": "https://api.exemplo.com/files/arquivo.mp3",
  "mimetype": "audio/mpeg",
  "base64Data": "UklGRkj...",
  "transcription": "Texto transcrito"
}
Nota:

Por padrão, se não definido o contrário:
áudios são retornados como MP3.
E todos os pedidos de download são retornados com URL pública.
Transcrição requer chave OpenAI válida. A chave pode ser configurada uma vez na instância e será reutilizada automaticamente.
Request
Body
id
string
required
ID da mensagem contendo o arquivo

Example: "7EB0F01D7244B421048F0706368376E0"

return_base64
boolean
Se verdadeiro, retorna o conteúdo em base64

generate_mp3
boolean
Para áudios, define formato de retorno (true=MP3, false=OGG)

return_link
boolean
Salva e retorna URL pública do arquivo

transcribe
boolean
Se verdadeiro, transcreve áudios para texto

openai_apikey
string
Chave da API OpenAI para transcrição (opcional)

Example: "sk-..."

download_quoted
boolean
Se verdadeiro, baixa mídia da mensagem citada ao invés da mensagem principal

Responses

200
Successful file download

400
Bad Request

401
Unauthorized

404
Not Found

500
Internal Server Error
Try It
Code
POST
https://growhatshomolog.uazapi.com/message/download
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/message/download \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "id": "7EB0F01D7244B421048F0706368376E0",
  "return_base64": false,
  "generate_mp3": false,
  "return_link": false,
  "transcribe": false,
  "openai_apikey": "sk-...",
  "download_quoted": false
}'