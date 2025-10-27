Properties
id
string
ID único gerado automaticamente

token
string
Token de autenticação da instância

status
string
Status atual da conexão

paircode
string
Código de pareamento

qrcode
string
QR Code em base64 para autenticação

name
string
Nome da instância

profileName
string
Nome do perfil WhatsApp

profilePicUrl
string
URL da foto do perfil

isBusiness
boolean
Indica se é uma conta business

plataform
string
Plataforma de origem (iOS/Android/Web)

systemName
string
Nome do sistema operacional

owner
string
Proprietário da instância

lastDisconnect
string
Data/hora da última desconexão

lastDisconnectReason
string
Motivo da última desconexão

adminField01
string
Campo administrativo 01

adminField02
string
Campo administrativo 02

openai_apikey
string
Chave da API OpenAI

chatbot_enabled
boolean
Habilitar chatbot automático

chatbot_ignoreGroups
boolean
Ignorar mensagens de grupos

chatbot_stopConversation
string
Palavra-chave para parar conversa

chatbot_stopMinutes
integer
Por quanto tempo ficará pausado o chatbot ao usar stop conversation

chatbot_stopWhenYouSendMsg
integer
Por quanto tempo ficará pausada a conversa quando você enviar mensagem manualmente

created
string
Data de criação da instância

updated
string
Data da última atualização

msg_delay_min
integer
Delay mínimo em segundos entre mensagens diretas

msg_delay_max
integer
Delay máximo em segundos entre mensagens diretas (deve ser maior que delayMin)

Example
{
  "id": "i91011ijkl",
  "token": "abc123xyz",
  "status": "connected",
  "paircode": "1234-5678",
  "qrcode": "data:image/png;base64,iVBORw0KGg...",
  "name": "Instância Principal",
  "profileName": "Loja ABC",
  "profilePicUrl": "https://example.com/profile.jpg",
  "isBusiness": true,
  "plataform": "Android",
  "systemName": "uazapi",
  "owner": "user@example.com",
  "lastDisconnect": "2025-01-24T14:00:00Z",
  "lastDisconnectReason": "Network error",
  "adminField01": "custom_data",
  "openai_apikey": "sk-...xyz",
  "chatbot_enabled": true,
  "chatbot_ignoreGroups": true,
  "chatbot_stopConversation": "parar",
  "chatbot_stopMinutes": 60,
  "created": "2025-01-24T14:00:00Z",
  "updated": "2025-01-24T14:30:00Z",
  "delayMin": 2,
  "delayMax": 4
}