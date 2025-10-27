SCHEMA
ChatbotAIAgent
Configuração de um agente de IA para atendimento de conversas

Properties
id
string
ID único gerado pelo sistema

name
string
required
Nome de exibição do agente

provider
string
required
Provedor do serviço de IA

model
string
required
Nome do modelo LLM a ser utilizado

apikey
string
required
Chave de API para autenticação no provedor

basePrompt
string
Prompt base para orientar o comportamento do agente

maxTokens
integer
Número máximo de tokens por resposta

temperature
integer
Controle de criatividade (0-100)

diversityLevel
integer
Nível de diversificação das respostas

frequencyPenalty
integer
Penalidade para repetição de frases

presencePenalty
integer
Penalidade para manter foco no tópico

signMessages
boolean
Adiciona identificação do agente nas mensagens

readMessages
boolean
Marca mensagens como lidas automaticamente

maxMessageLength
integer
Tamanho máximo permitido para mensagens (caracteres)

typingDelay_seconds
integer
Atraso simulado de digitação em segundos

contextTimeWindow_hours
integer
Janela temporal para contexto da conversa

contextMaxMessages
integer
Número máximo de mensagens no contexto

contextMinMessages
integer
Número mínimo de mensagens para iniciar contexto

owner
string
Responsável/Proprietário do agente

created
string
Data de criação do registro

updated
string
Data da última atualização