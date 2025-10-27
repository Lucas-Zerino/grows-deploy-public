SCHEMA
MessageQueueFolder
Pasta para organização de campanhas de mensagens em massa

Properties
id
string
Identificador único

info
string
Informações adicionais sobre a pasta

status
string
Status atual da pasta

"ativo"
scheduled_for
integer
Timestamp Unix para execução agendada

delayMax
integer
Atraso máximo entre mensagens em milissegundos

delayMin
integer
Atraso mínimo entre mensagens em milissegundos

log_delivered
integer
Contagem de mensagens entregues

log_failed
integer
Contagem de mensagens com falha

log_played
integer
Contagem de mensagens reproduzidas (para áudio/vídeo)

log_read
integer
Contagem de mensagens lidas

log_sucess
integer
Contagem de mensagens enviadas com sucesso

log_total
integer
Contagem total de mensagens

owner
string
Identificador do proprietário da instância

created
string
Data e hora de criação

updated
string
Data e hora da última atualização