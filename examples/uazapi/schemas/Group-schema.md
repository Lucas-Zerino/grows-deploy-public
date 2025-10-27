SCHEMA
Group
Representa um grupo/conversa coletiva

Properties
JID
string
Identificador único do grupo

"jid8@g.us"
OwnerJID
string
JID do proprietário do grupo

"1232@s.whatsapp.net"
Name
string
Nome do grupo

"Grupo de Suporte"
NameSetAt
string
Data da última alteração do nome

NameSetBy
string
JID do usuário que definiu o nome

Topic
string
Descrição do grupo

IsLocked
boolean
Indica se apenas administradores podem editar informações do grupo

true = apenas admins podem editar
false = todos podem editar
true
IsAnnounce
boolean
Indica se apenas administradores podem enviar mensagens

AnnounceVersionID
string
Versão da configuração de anúncios

IsEphemeral
boolean
Indica se as mensagens são temporárias

DisappearingTimer
integer
Tempo em segundos para desaparecimento de mensagens

IsIncognito
boolean
Indica se o grupo é incognito

IsParent
boolean
Indica se é um grupo pai (comunidade)

IsJoinApprovalRequired
boolean
Indica se requer aprovação para novos membros

LinkedParentJID
string
JID da comunidade vinculada

IsDefaultSubGroup
boolean
Indica se é um subgrupo padrão da comunidade

GroupCreated
string
Data de criação do grupo

ParticipantVersionID
string
Versão da lista de participantes

Participants
array
Lista de participantes do grupo

MemberAddMode
string
Modo de adição de novos membros

OwnerCanSendMessage
boolean
Verifica se é possível você enviar mensagens

OwnerIsAdmin
boolean
Verifica se você adminstrador do grupo

DefaultSubGroupId
string
Se o grupo atual for uma comunidade, nesse campo mostrará o ID do subgrupo de avisos

invite_link
string
Link de convite para entrar no grupo

request_participants
string
Lista de solicitações de entrada, separados por vírgula