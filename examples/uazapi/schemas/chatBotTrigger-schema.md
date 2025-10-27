SCHEMA
ChatbotTrigger
Properties
id
string
Identificador único do trigger. Se definido, você irá editar ou deletar o trigger. Se vazio, um novo trigger será criado.

active
boolean
Define se o trigger está ativo e disponível para uso. Triggers inativos não serão executados pelo sistema.

type
string
required
Tipo do trigger:

agent - aciona um agente de IA
quickreply - aciona respostas rápidas predefinidas
agent_id
string
required
ID do agente de IA. Obrigatório quando type='agent'

quickReply_id
string
ID da resposta rápida. Obrigatório quando type='quickreply'

ignoreGroups
boolean
Define se o trigger deve ignorar mensagens de grupos

lead_field
string
Campo do lead usado para condição do trigger

lead_operator
string
Operador de comparação para condição do lead:

equals - igual a
not_equals - diferente de
contains - contém
not_contains - não contém
greater - maior que
less - menor que
empty - vazio
not_empty - não vazio
lead_value
string
Valor para comparação com o campo do lead. Usado em conjunto com lead_field e lead_operator

priority
integer
Prioridade do trigger. Quando existem múltiplos triggers que poderiam ser acionados, APENAS o trigger com maior prioridade será executado. Se houver múltiplos triggers com a mesma prioridade mais alta, um será escolhido aleatoriamente.

wordsToStart
string
Palavras-chave ou frases que ativam o trigger. Múltiplas entradas separadas por pipe (|). Exemplo: olá|bom dia|qual seu nome

responseDelay_seconds
integer
Tempo de espera em segundos antes de executar o trigger

owner
string
Identificador do proprietário do trigger

created
string
Data e hora de criação

updated
string
Data e hora da última atualização