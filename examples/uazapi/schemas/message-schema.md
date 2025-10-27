SCHEMA
Message
Representa uma mensagem trocada no sistema

Properties
id
string
ID único interno da mensagem (formato r + 7 caracteres hex aleatórios)

messageid
string
ID original da mensagem no provedor

chatid
string
ID da conversa relacionada

fromMe
boolean
Indica se a mensagem foi enviada pelo usuário

isGroup
boolean
Indica se é uma mensagem de grupo

messageType
string
Tipo de conteúdo da mensagem

messageTimestamp
integer
Timestamp original da mensagem em milissegundos

edited
string
Histórico de edições da mensagem

quoted
string
ID da mensagem citada/respondida

reaction
string
ID da mensagem reagida

sender
string
ID do remetente da mensagem

senderName
string
Nome exibido do remetente

source
string
Plataforma de origem da mensagem

status
string
Status do ciclo de vida da mensagem

text
string
Texto original da mensagem

vote
string
Dados de votação de enquete e listas

buttonOrListid
string
ID do botão ou item de lista selecionado

convertOptions
string
Conversão de opções de da mensagem, lista, enquete e botões

fileURL
string
URL para download de arquivos de mídia

content
string
Conteúdo completo da mensagem em formato JSON

owner
string
Dono da mensagem

track_source
string
Origem do rastreamento da mensagem

track_id
string
ID para rastreamento da mensagem (aceita valores duplicados)

created
string
Data de criação no sistema (formato SQLite YYYY-MM-DD HH:MM:SS.FFF)

updated
string
Data da última atualização (formato SQLite YYYY-MM-DD HH:MM:SS.FFF)

ai_metadata
object
Metadados do processamento por IA