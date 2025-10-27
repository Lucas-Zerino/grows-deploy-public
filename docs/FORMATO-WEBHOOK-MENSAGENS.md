# 📨 Formato de Webhook de Mensagens

## 🎯 Formato Customizado

Os webhooks de mensagens são enviados no formato personalizado com todos os campos necessários.

---

## 📋 Estrutura do Webhook

### Mensagem de Texto

```json
{
  "usalid": false,
  "type": "text",
  "isMedia": false,
  "de_para_json": true,
  "container": "api-9",
  "session": 9,
  "device": "558498537596",
  "event": "on-message",
  "pushName": "Nome do Remetente",
  "from": "5511999999999",
  "lid": "",
  "id": "3EB063D3DA3490B3FA4272",
  "content": "Olá, tudo bem?",
  "isgroup": false,
  "api": 10,
  "tipo_api": 10,
  "participant": "",
  "participant_lid": "",
  "timestamp": 1760471539000,
  "content_msg": {
    "text": "Olá, tudo bem?",
    "contextInfo": {
      "ephemeralSettingTimestamp": 1746216109
    }
  },
  "webhook": "webhook_wh_message",
  "ambiente": "dev",
  "token": "14b0e467-261b-4228-aa94-d5d5cafada6e"
}
```

---

### Mensagem de Áudio

```json
{
  "usalid": false,
  "type": "audio",
  "isMedia": true,
  "de_para_json": true,
  "container": "api-9",
  "session": 9,
  "device": "558498537596",
  "event": "on-message",
  "pushName": "Nome do Remetente",
  "from": "5511999999999",
  "lid": "",
  "id": "CFDF314C6AA85E7EE9F263C740832FCA",
  "content": "",
  "isgroup": false,
  "api": 10,
  "tipo_api": 10,
  "participant": "",
  "participant_lid": "",
  "timestamp": 1756812842000,
  "content_msg": {
    "URL": "https://mmg.whatsapp.net/...",
    "mimetype": "audio/ogg; codecs=opus",
    "fileSHA256": "...",
    "fileLength": 7420,
    "seconds": 3,
    "PTT": true,
    "mediaKey": "...",
    "fileEncSHA256": "...",
    "directPath": "/v/t62.7117-24/...",
    "mediaKeyTimestamp": 1756812840,
    "waveform": "..."
  },
  "webhook": "webhook_wh_message",
  "ambiente": "dev",
  "token": "14b0e467-261b-4228-aa94-d5d5cafada6e",
  "file": {
    "mimetype": "audio/ogg; codecs=opus",
    "filename": "",
    "fileLength": 7420,
    "caption": ""
  }
}
```

---

### Mensagem de Documento/Imagem

```json
{
  "usalid": false,
  "type": "document",
  "isMedia": true,
  "de_para_json": true,
  "container": "api-9",
  "session": 9,
  "device": "558498537596",
  "event": "on-message",
  "pushName": "Nome do Remetente",
  "from": "5511999999999",
  "lid": "",
  "id": "3EB0E693B0A601CD0ECB7D",
  "content": "",
  "isgroup": false,
  "api": 10,
  "tipo_api": 10,
  "participant": "",
  "participant_lid": "",
  "timestamp": 1756731798000,
  "content_msg": {
    "URL": "https://mmg.whatsapp.net/...",
    "mimetype": "image/jpeg",
    "title": "image.jpg",
    "fileSHA256": "...",
    "fileLength": 1968613,
    "mediaKey": "...",
    "fileName": "image.jpg",
    "fileEncSHA256": "...",
    "directPath": "/v/t62.7119-24/...",
    "mediaKeyTimestamp": 1756731796,
    "contactVcard": false
  },
  "webhook": "webhook_wh_message",
  "ambiente": "dev",
  "token": "14b0e467-261b-4228-aa94-d5d5cafada6e",
  "file": {
    "mimetype": "image/jpeg",
    "filename": "image.jpg",
    "fileLength": 1968613,
    "caption": ""
  }
}
```

---

## 📖 Campos Explicados

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `usalid` | boolean | Se usa LID (LinkedIn Identifier) |
| `type` | string | Tipo: text, audio, video, image, document, etc |
| `isMedia` | boolean | Se é mídia (imagem, áudio, vídeo, documento) |
| `de_para_json` | boolean | Sempre `true` |
| `container` | string | ID do container: `api-{session}` |
| `session` | number | ID da sessão (instância) |
| `device` | string | Número do dispositivo conectado |
| `event` | string | Sempre `on-message` para mensagens |
| `pushName` | string | Nome de exibição do remetente |
| `from` | string | Número do remetente (sem @c.us) |
| `lid` | string | LID do remetente (se existir) |
| `id` | string | ID único da mensagem |
| `content` | string | Conteúdo da mensagem (vazio se mídia) |
| `isgroup` | boolean | Se a mensagem é de grupo |
| `api` | number | Sempre `10` |
| `tipo_api` | number | Sempre `10` |
| `participant` | string | Participante do grupo (se for grupo) |
| `participant_lid` | string | LID do participante (se existir) |
| `timestamp` | number | Timestamp em milissegundos |
| `content_msg` | object | Objeto completo da mensagem WAHA |
| `webhook` | string | Sempre `webhook_wh_message` |
| `ambiente` | string | Ambiente: `dev` ou `prod` |
| `token` | string | Token da instância |
| `file` | object | Info do arquivo (apenas se `isMedia: true`) |

---

## 🔍 Tipos de Mensagens Suportados

| Tipo | isMedia | Campos Adicionais |
|------|---------|-------------------|
| `text` | false | `content` preenchido |
| `image` | true | `file.mimetype`, `file.filename` |
| `video` | true | `file.mimetype`, `file.filename` |
| `audio` | true | `file.mimetype`, `content_msg.seconds` |
| `document` | true | `file.mimetype`, `file.filename` |
| `sticker` | true | - |
| `contact` | false | - |
| `location` | false | - |

---

## 🔄 Mapeamento WAHA → Formato Customizado

| Campo WAHA | Campo Customizado | Transformação |
|------------|-------------------|---------------|
| `payload.from` | `from` | Remove `@c.us` ou `@g.us` |
| `payload.id` | `id` | Direto |
| `payload.body` | `content` | Apenas se type=text |
| `payload.timestamp` | `timestamp` | Multiplica por 1000 (milissegundos) |
| `payload._data.notifyName` | `pushName` | Direto |
| `payload.fromMe` | - | Usado para detectar direção |
| `payload._data.message.imageMessage` | `type: 'image'` | Detecta tipo |
| `payload._data.message.audioMessage` | `type: 'audio'` | Detecta tipo |
| `payload.from` contém `@g.us` | `isgroup: true` | Detecção automática |
| `payload.author` | `participant` | Se for grupo |

---

## 🎯 Exemplos de Uso

### Processar Mensagem de Texto

```javascript
if (webhook.type === 'text' && !webhook.isMedia) {
  const mensagem = webhook.content;
  const remetente = webhook.from;
  const nome = webhook.pushName;
  
  console.log(`${nome} (${remetente}): ${mensagem}`);
}
```

### Processar Mídia

```javascript
if (webhook.isMedia) {
  const tipo = webhook.type; // audio, image, video, document
  const arquivo = webhook.file;
  const url = webhook.content_msg.URL;
  
  console.log(`Mídia recebida: ${tipo}`);
  console.log(`Arquivo: ${arquivo.filename}`);
  console.log(`Tamanho: ${arquivo.fileLength} bytes`);
  console.log(`URL: ${url}`);
}
```

### Detectar Grupo

```javascript
if (webhook.isgroup) {
  const grupo = webhook.from; // ID do grupo
  const participante = webhook.participant; // Quem enviou
  const mensagem = webhook.content;
  
  console.log(`Mensagem no grupo ${grupo} de ${participante}: ${mensagem}`);
}
```

---

## 🔧 Configuração

### Definir Ambiente

No arquivo `.env`:
```env
APP_ENV=dev  # ou prod
```

Isso afeta o campo `ambiente` no webhook.

### Token da Instância

O campo `token` contém o token da instância que recebeu a mensagem, útil para:
- Identificar origem
- Responder automaticamente
- Logs e auditoria

---

## 📊 Fluxo Completo

```
1. WhatsApp → Mensagem recebida
   ↓
2. WAHA → Webhook para backend
   POST /webhook/waha/9
   ↓
3. WebhookController → Traduz WAHA → Formato Customizado
   ↓
4. RabbitMQ → Fila company.{id}.inbound
   ↓
5. Worker → Consome fila
   ↓
6. Worker → POST para webhook_url do cliente
   {
     "usalid": false,
     "type": "text",
     "content": "Mensagem...",
     ...
   }
   ↓
7. Cliente → Recebe webhook formatado!
```

---

## 🧪 Testar

### 1. Reiniciar API

```powershell
.\scripts\windows\restart-api.ps1
```

### 2. Enviar Mensagem de Teste

No WhatsApp conectado, envie:
- Mensagem de texto
- Áudio
- Imagem
- Documento

### 3. Verificar Webhook

Acesse seu webhook.site e veja o formato!

Deve aparecer com todos os campos formatados corretamente.

---

## 📝 Notas Importantes

1. **Timestamp em milissegundos**: Timestamp da WAHA vem em segundos, multiplicamos por 1000
2. **Números limpos**: Removemos `@c.us`, `@g.us`, `@lid` dos números
3. **LID**: Detectamos automaticamente se a mensagem usa LID
4. **Grupos**: Detectamos automaticamente se é mensagem de grupo
5. **Mídia**: Tipo detectado automaticamente pela estrutura da mensagem
6. **content_msg**: Contém dados completos da WAHA para processamento avançado

---

## 🚀 Próximas Melhorias

1. ⏳ Adicionar suporte para mais tipos de mensagem (poll, reaction, etc)
2. ⏳ Enriquecer dados de contato
3. ⏳ Adicionar informações de citação (reply)
4. ⏳ Suporte para mensagens efêmeras

---

**Formato implementado e pronto para uso!** 🎉

