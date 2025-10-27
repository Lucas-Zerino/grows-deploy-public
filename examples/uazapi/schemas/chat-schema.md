SCHEMA
Chat
Representa uma conversa/chamado no sistema

Properties
id
string
ID único da conversa (r + 7 bytes aleatórios em hex)

wa_fastid
string
Identificador rápido do WhatsApp

wa_chatid
string
ID completo do chat no WhatsApp

wa_archived
boolean
Indica se o chat está arquivado

wa_contactName
string
Nome do contato no WhatsApp

wa_name
string
Nome do WhatsApp

name
string
Nome exibido do chat

image
string
URL da imagem do chat

imagePreview
string
URL da miniatura da imagem

wa_ephemeralExpiration
integer
Tempo de expiração de mensagens efêmeras

wa_isBlocked
boolean
Indica se o contato está bloqueado

wa_isGroup
boolean
Indica se é um grupo

wa_isGroup_admin
boolean
Indica se o usuário é admin do grupo

wa_isGroup_announce
boolean
Indica se é um grupo somente anúncios

wa_isGroup_community
boolean
Indica se é uma comunidade

wa_isGroup_member
boolean
Indica se é membro do grupo

wa_isPinned
boolean
Indica se o chat está fixado

wa_label
string
Labels do chat em JSON

wa_lastMessageTextVote
string
Texto/voto da última mensagem

wa_lastMessageType
string
Tipo da última mensagem

wa_lastMsgTimestamp
integer
Timestamp da última mensagem

wa_lastMessageSender
string
Remetente da última mensagem

wa_muteEndTime
integer
Timestamp do fim do silenciamento

owner
string
Dono da instância

wa_unreadCount
integer
Contador de mensagens não lidas

phone
string
Número de telefone

wa_common_groups
string
Grupos em comum separados por vírgula, formato: (nome_grupo)id_grupo

"Grupo Família(120363123456789012@g.us),Trabalho(987654321098765432@g.us)"
lead_name
string
Nome do lead

lead_fullName
string
Nome completo do lead

lead_email
string
Email do lead

lead_personalid
string
Documento de identificação

lead_status
string
Status do lead

lead_tags
string
Tags do lead em JSON

lead_notes
string
Anotações sobre o lead

lead_isTicketOpen
boolean
Indica se tem ticket aberto

lead_assignedAttendant_id
string
ID do atendente responsável

lead_kanbanOrder
integer
Ordem no kanban

lead_field01
string
lead_field02
string
lead_field03
string
lead_field04
string
lead_field05
string
lead_field06
string
lead_field07
string
lead_field08
string
lead_field09
string
lead_field10
string
lead_field11
string
lead_field12
string
lead_field13
string
lead_field14
string
lead_field15
string
lead_field16
string
lead_field17
string
lead_field18
string
lead_field19
string
lead_field20
string
chatbot_agentResetMemoryAt
integer
Timestamp do último reset de memória

chatbot_lastTrigger_id
string
ID do último gatilho executado

chatbot_lastTriggerAt
integer
Timestamp do último gatilho

chatbot_disableUntil
integer
Timestamp até quando chatbot está desativado

created
string
Data de criação

updated
string
Data da última atualização