POST
/send/menu
Enviar menu interativo (botões, carrosel, lista ou enquete)
Este endpoint oferece uma interface unificada para envio de quatro tipos principais de mensagens interativas:

Botões: Para ações rápidas e diretas
Carrosel de Botões: Para uma lista horizontal de botões com imagens
Listas: Para menus organizados em seções
Enquetes: Para coleta de opiniões e votações
Suporte a campos de rastreamento: Este endpoint também suporta track_source e track_id documentados na tag "Enviar Mensagem".

Estrutura Base do Payload
Todas as requisições seguem esta estrutura base:

{
  "number": "5511999999999",
  "type": "button|list|poll|carousel",
  "text": "Texto principal da mensagem",
  "choices": ["opções baseadas no tipo escolhido"],
  "footerText": "Texto do rodapé (opcional para botões e listas)",
  "listButton": "Texto do botão (para listas)",
  "selectableCount": "Número de opções selecionáveis (apenas para enquetes)"
}
Tipos de Mensagens Interativas
1. Botões (type: "button")
Cria botões interativos com diferentes funcionalidades de ação.

Campos Específicos
footerText: Texto opcional exibido abaixo da mensagem principal
choices: Array de opções que serão convertidas em botões
Formatos de Botões
Cada botão pode ser configurado usando | (pipe) ou \n (quebra de linha) como separadores:

Botão de Resposta:

"texto|id" ou
"texto\nid" ou
"texto" (ID será igual ao texto)
Botão de Cópia:

"texto|copy:código" ou
"texto\ncopy:código"
Botão de Chamada:

"texto|call:+5511999999999" ou
"texto\ncall:+5511999999999"
Botão de URL:

"texto|https://exemplo.com" ou
"texto|url:https://exemplo.com"
Botões com Imagem
Para adicionar uma imagem aos botões, use o campo imageButton no payload:

Exemplo com Imagem
{
  "number": "5511999999999",
  "type": "button",
  "text": "Escolha um produto:",
  "imageButton": "https://exemplo.com/produto1.jpg",
  "choices": [
    "Produto A|prod_a",
    "Mais Info|https://exemplo.com/produto-a",
    "Produto B|prod_b",
    "Ligar|call:+5511999999999"
  ],
  "footerText": "Produtos em destaque"
}
Suporte: O campo imageButton aceita URLs ou imagens em base64.

Exemplo Completo
{
  "number": "5511999999999",
  "type": "button",
  "text": "Como podemos ajudar?",
  "choices": [
    "Suporte Técnico|suporte",
    "Fazer Pedido|pedido",
    "Nosso Site|https://exemplo.com",
    "Falar Conosco|call:+5511999999999"
  ],
  "footerText": "Escolha uma das opções abaixo"
}
Limitações e Compatibilidade
Importante: Ao combinar botões de resposta com outros tipos (call, url, copy) na mesma mensagem, será exibido o aviso: "Não é possível exibir esta mensagem no WhatsApp Web. Abra o WhatsApp no seu celular para visualizá-la."

2. Listas (type: "list")
Cria menus organizados em seções com itens selecionáveis.

Campos Específicos
listButton: Texto do botão que abre a lista
footerText: Texto opcional do rodapé
choices: Array com seções e itens da lista
Formato das Choices
"[Título da Seção]": Inicia uma nova seção
"texto|id|descrição": Item da lista com:
texto: Label do item
id: Identificador único, opcional
descrição: Texto descritivo adicional e opcional
Exemplo Completo
{
  "number": "5511999999999",
  "type": "list",
  "text": "Catálogo de Produtos",
  "choices": [
    "[Eletrônicos]",
    "Smartphones|phones|Últimos lançamentos",
    "Notebooks|notes|Modelos 2024",
    "[Acessórios]",
    "Fones|fones|Bluetooth e com fio",
    "Capas|cases|Proteção para seu device"
  ],
  "listButton": "Ver Catálogo",
  "footerText": "Preços sujeitos a alteração"
}
3. Enquetes (type: "poll")
Cria enquetes interativas para votação.

Campos Específicos
selectableCount: Número de opções que podem ser selecionadas (padrão: 1)
choices: Array simples com as opções de voto
Exemplo Completo
{
  "number": "5511999999999",
  "type": "poll",
  "text": "Qual horário prefere para atendimento?",
  "choices": [
    "Manhã (8h-12h)",
    "Tarde (13h-17h)",
    "Noite (18h-22h)"
  ],
  "selectableCount": 1
}
4. Carousel (type: "carousel")
Cria um carrossel de cartões com imagens e botões interativos.

Campos Específicos
choices: Array com elementos do carrossel na seguinte ordem:
[Texto do cartão]: Texto do cartão entre colchetes
{URL ou base64 da imagem}: Imagem entre chaves
Botões do cartão (um por linha):
"texto|copy:código" para botão de copiar
"texto|https://url" para botão de link
"texto|call:+número" para botão de ligação
Exemplo Completo
{
  "number": "5511999999999",
  "type": "carousel",
  "text": "Conheça nossos produtos",
  "choices": [
    "[Smartphone XYZ\nO mais avançado smartphone da linha]",
    "{https://exemplo.com/produto1.jpg}",
    "Copiar Código|copy:PROD123",
    "Ver no Site|https://exemplo.com/xyz",
    "Fale Conosco|call:+5511999999999",
    "[Notebook ABC\nO notebook ideal para profissionais]",
    "{https://exemplo.com/produto2.jpg}",
    "Copiar Código|copy:NOTE456",
    "Comprar Online|https://exemplo.com/abc",
    "Suporte|call:+5511988888888"
  ]
}
Nota: Criamos outro endpoint para carrossel: /send/carousel, funciona da mesma forma, mas com outro formato de payload. Veja o que é mais fácil para você.

Termos de uso
Os recursos de botões interativos e listas podem ser descontinuados a qualquer momento sem aviso prévio. Não nos responsabilizamos por quaisquer alterações ou indisponibilidade destes recursos.

Alternativas e Compatibilidade
Considerando a natureza dinâmica destes recursos, nosso endpoint foi projetado para facilitar a migração entre diferentes tipos de mensagens (botões, listas e enquetes).

Recomendamos criar seus fluxos de forma flexível, preparados para alternar entre os diferentes tipos.

Em caso de descontinuidade de algum recurso, você poderá facilmente migrar para outro tipo de mensagem apenas alterando o campo "type" no payload, mantendo a mesma estrutura de choices.

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
Tipo do menu (button, list, poll, carousel)

Example: "list"

text
string
required
Texto principal (aceita placeholders)

Example: "Escolha uma opção:"

footerText
string
Texto do rodapé (opcional)

Example: "Menu de serviços"

listButton
string
Texto do botão principal

Example: "Ver opções"

selectableCount
integer
Número máximo de opções selecionáveis (para enquetes)

Example: 1

choices
array
required
Lista de opções. Use [Título] para seções em listas

Example: ["[Eletrônicos]","Smartphones|phones|Últimos lançamentos","Notebooks|notes|Modelos 2024","[Acessórios]","Fones|fones|Bluetooth e com fio","Capas|cases|Proteção para seu device"]

imageButton
string
URL da imagem para botões (recomendado para type: button)

Example: "https://exemplo.com/imagem-botao.jpg"

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
Atraso em milissegundos antes do envio, durante o atraso apacerá 'Digitando...'

Example: 1000

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
Menu enviado com sucesso

400
Requisição inválida

401
Não autorizado

429
Limite de requisições excedido

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/menu
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/menu \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "type": "list",
  "text": "Escolha uma opção:",
  "footerText": "Menu de serviços",
  "listButton": "Ver opções",
  "selectableCount": 1,
  "choices": [
    "[Eletrônicos]",
    "Smartphones|phones|Últimos lançamentos",
    "Notebooks|notes|Modelos 2024",
    "[Acessórios]",
    "Fones|fones|Bluetooth e com fio",
    "Capas|cases|Proteção para seu device"
  ],
  "imageButton": "https://exemplo.com/imagem-botao.jpg",
  "replyid": "3EB0538DA65A59F6D8A251",
  "mentions": "5511999999999,5511888888888",
  "readchat": true,
  "readmessages": true,
  "delay": 1000,
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'