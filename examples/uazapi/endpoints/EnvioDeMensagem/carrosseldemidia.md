POST
/send/carousel
Enviar carrossel de mídia com botões
Este endpoint permite enviar um carrossel com imagens e botões interativos. Funciona de maneira igual ao endpoint /send/menu com type: carousel, porém usando outro formato de payload.

Campos Comuns
Este endpoint suporta todos os campos opcionais comuns documentados na tag "Enviar Mensagem", incluindo: delay, readchat, readmessages, replyid, mentions, forward, track_source, track_id, placeholders e envio para grupos.

Estrutura do Payload
{
  "number": "5511999999999",
  "text": "Texto principal",
  "carousel": [
    {
      "text": "Texto do cartão",
      "image": "URL da imagem",
      "buttons": [
        {
          "id": "resposta1",
          "text": "Texto do botão",
          "type": "REPLY"
        }
      ]
    }
  ],
  "delay": 1000,
  "readchat": true
}
Tipos de Botões
REPLY: Botão de resposta rápida

Quando clicado, envia o valor do id como resposta ao chat
O id será o texto enviado como resposta
URL: Botão com link

Quando clicado, abre a URL especificada
O id deve conter a URL completa (ex: https://exemplo.com)
COPY: Botão para copiar texto

Quando clicado, copia o texto para a área de transferência
O id será o texto que será copiado
CALL: Botão para realizar chamada

Quando clicado, inicia uma chamada telefônica
O id deve conter o número de telefone
Exemplo de Botões
{
  "buttons": [
    {
      "id": "Sim, quero comprar!",
      "text": "Confirmar Compra",
      "type": "REPLY"
    },
    {
      "id": "https://exemplo.com/produto",
      "text": "Ver Produto",
      "type": "URL"
    },
    {
      "id": "CUPOM20",
      "text": "Copiar Cupom",
      "type": "COPY"
    },
    {
      "id": "5511999999999",
      "text": "Falar com Vendedor",
      "type": "CALL"
    }
  ]
}
Exemplo Completo de Carrossel
{
  "number": "5511999999999",
  "text": "Nossos Produtos em Destaque",
  "carousel": [
    {
      "text": "Smartphone XYZ\nO mais avançado smartphone da linha",
      "image": "https://exemplo.com/produto1.jpg",
      "buttons": [
        {
          "id": "SIM_COMPRAR_XYZ",
          "text": "Comprar Agora",
          "type": "REPLY"
        },
        {
          "id": "https://exemplo.com/xyz",
          "text": "Ver Detalhes",
          "type": "URL"
        }
      ]
    },
    {
      "text": "Cupom de Desconto\nGanhe 20% OFF em qualquer produto",
      "image": "https://exemplo.com/cupom.jpg",
      "buttons": [
        {
          "id": "DESCONTO20",
          "text": "Copiar Cupom",
          "type": "COPY"
        },
        {
          "id": "5511999999999",
          "text": "Falar com Vendedor",
          "type": "CALL"
        }
      ]
    }
  ],
  "delay": 0,
  "readchat": true
}
Request
Body
number
string
required
Número do destinatário (formato internacional)

Example: "5511999999999"

text
string
required
Texto principal da mensagem

Example: "Nossos Produtos em Destaque"

carousel
array
required
Array de cartões do carrossel

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
Carrossel enviado com sucesso

400
Requisição inválida

401
Não autorizado

500
Erro interno do servidor
Try It
Code
POST
https://growhatshomolog.uazapi.com/send/carousel
Language

cURL

curl --request POST \
  --url https://growhatshomolog.uazapi.com/send/carousel \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "number": "5511999999999",
  "text": "Nossos Produtos em Destaque",
  "carousel": [
    {
      "text": "Smartphone XYZ\nO mais avançado smartphone da linha",
      "image": "https://exemplo.com/produto1.jpg",
      "buttons": [
        {
          "id": "buy_xyz",
          "text": "Comprar Agora",
          "type": "REPLY"
        }
      ]
    }
  ],
  "track_source": "chatwoot",
  "track_id": "msg_123456789"
}'