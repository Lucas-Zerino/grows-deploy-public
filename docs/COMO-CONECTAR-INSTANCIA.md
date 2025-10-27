# 📱 Como Conectar uma Instância ao WhatsApp

## ✅ Fluxo Correto (Atualizado 2025)

```
1. POST /api/instances           → Criar instância (recebe instance_token)
2. POST /instance/authenticate   → Autenticar (escolher método)
3. GET /instance/status          → Ver status e confirmar conexão
```

---

## 📋 Passo a Passo Completo

### Passo 1: Criar a Instância

```http
POST /api/instances
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_name": "Vendas"
}
```

**Resposta:**
```json
{
  "data": {
    "id": 1,
    "instance_name": "Vendas",
    "token": "inst_abc123...",  ⬅️ IMPORTANTE! Guarde este token
    "status": "creating"
  }
}
```

---

### Passo 2: Autenticar (Escolher Método)

#### Opção A: QR Code (Recomendado para Desktop/Web)

```http
POST /instance/authenticate
Authorization: Bearer inst_abc123...  ⬅️ Token da instância!
Content-Type: application/json

{
  "method": "qrcode"
}
```

**Resposta:**
```json
{
  "method": "qrcode",
  "status": "connecting",
  "qrcode": "2@ABDKJASD...",
  "message": "QR code generated. Scan it with your WhatsApp."
}
```

#### Opção B: Código por Telefone (Recomendado para Mobile)

```http
POST /instance/authenticate
Authorization: Bearer inst_abc123...
Content-Type: application/json

{
  "method": "phone_code",
  "phone_number": "5511999999999"
}
```

**Resposta:**
```json
{
  "method": "phone_code",
  "status": "connecting",
  "code": "12345678",
  "message": "Authentication code sent. Enter it in WhatsApp."
}
```

---

### Passo 3: Verificar Status (Aguardar Conexão)

```http
GET /instance/status
Authorization: Bearer inst_abc123...
```

**Enquanto conectando:**
```json
{
  "id": "1-Vendas",
  "name": "Vendas",
  "status": "connecting",
  "qrcode": "2@..."  // Se método for QR
}
```

**Após conectar:**
```json
{
  "id": "1-Vendas",
  "name": "Vendas",
  "status": "connected",  ✅
  "phone_number": "5511999999999@c.us"
}
```

---

## 🎯 Métodos de Autenticação

### QR Code 📸
**Melhor para:** Desktop, Web, Computador

**Vantagens:**
- Rápido e visual
- Não precisa digitar nada
- Familiaridade do usuário

**Como funciona:**
1. Gera QR code
2. Usuário abre WhatsApp no celular
3. WhatsApp > ⋮ > Aparelhos conectados > Conectar um aparelho
4. Escaneia o QR code
5. Pronto! ✅

---

### Código por Telefone 📞
**Melhor para:** Mobile, Apps, Sem câmera

**Vantagens:**
- Não precisa de câmera
- Mais seguro
- Melhor para mobile

**Como funciona:**
1. Informa seu número
2. Recebe código de 8 dígitos
3. Abre WhatsApp > ⋮ > Aparelhos conectados > Conectar com número
4. Digita o código
5. Pronto! ✅

---

## 🔄 Obter QR Code em Diferentes Formatos

### Raw (String)
```http
GET /instance/authenticate/qrcode?format=raw
Authorization: Bearer inst_abc123...
```

**Resposta:**
```json
{
  "qrcode": "2@ABDKJASD..."
}
```

**Use para:** Gerar QR code no frontend (com biblioteca QR)

---

### Imagem PNG
```http
GET /instance/authenticate/qrcode?format=image
Authorization: Bearer inst_abc123...
```

**Resposta:** Imagem PNG diretamente
- Content-Type: image/png
- Body: dados binários da imagem

**Use para:** Mostrar imagem direto no HTML
```html
<img src="{{base_url}}/instance/authenticate/qrcode?format=image" />
```

---

## 💡 Exemplo Completo no Postman/Insomnia

### 1. Login (Pegar Company Token)
```http
POST http://localhost:8000/api/admin/login
Content-Type: application/json

{
  "email": "admin@growhub.com",
  "password": "admin123"
}

# Resposta: { "data": { "company": { "token": "comp_xyz..." } } }
```

---

### 2. Criar Instância
```http
POST http://localhost:8000/api/instances
Authorization: Bearer comp_xyz...
Content-Type: application/json

{
  "instance_name": "MinhaInstancia"
}

# Resposta: { "data": { "token": "inst_abc123..." } }
```

---

### 3. Autenticar (QR Code)
```http
POST http://localhost:8000/instance/authenticate
Authorization: Bearer inst_abc123...
Content-Type: application/json

{
  "method": "qrcode"
}

# Resposta: { "qrcode": "2@...", "message": "Scan with WhatsApp" }
```

---

### 4. Verificar Status até Conectar
```http
# Continue chamando a cada 2-3 segundos
GET http://localhost:8000/instance/status
Authorization: Bearer inst_abc123...

# Quando status for "connected", está pronto!
```

---

## 🐛 Troubleshooting

### QR Code não aparece

**Causa:** Sessão ainda está inicializando

**Solução:** 
1. Aguarde 3-5 segundos após chamar `/authenticate`
2. Tente novamente
3. Se não funcionar após 30s, veja os logs

---

### Código não chega

**Causa:** Número inválido ou WhatsApp não instalado

**Solução:**
- Formato correto: `5511999999999` (sem +, espaços ou caracteres especiais)
- Certifique-se que WhatsApp está instalado no celular

---

### Erro 400 "Invalid authentication method"

**Causa:** Método inválido no payload

**Solução:** Use `"method": "qrcode"` ou `"method": "phone_code"`

---

### Status fica em "connecting" para sempre

**Causa:** QR code/código não foi usado no WhatsApp

**Solução:**
1. Desconecte: `POST /instance/disconnect`
2. Autentique novamente: `POST /instance/authenticate`
3. Escaneie/digite o código rapidamente

---

## 📊 Estados da Instância

| Status          | Significado                       | O que fazer              |
|-----------------|-----------------------------------|--------------------------|
| `creating`      | Sendo criada                      | Aguardar                 |
| `disconnected`  | Desconectada                      | Chamar `/authenticate`   |
| `connecting`    | Aguardando autenticação           | Escanear QR ou digitar código |
| `connected`     | Conectada! ✅                     | Usar normalmente         |

---

## 🎯 Dicas Pro

### 1. Detectar Dispositivo Automaticamente

```javascript
const isMobile = /iPhone|iPad|Android/i.test(navigator.userAgent);
const method = isMobile ? 'phone_code' : 'qrcode';
```

### 2. Polling de Status Inteligente

```javascript
const pollStatus = async (token) => {
  const interval = setInterval(async () => {
    const status = await getStatus(token);
    
    if (status.status === 'connected') {
      clearInterval(interval);
      onConnected();
    }
  }, 3000); // A cada 3 segundos
  
  // Timeout após 2 minutos
  setTimeout(() => clearInterval(interval), 120000);
};
```

### 3. Exibir QR Code como Imagem

```html
<!-- Opção 1: URL direta -->
<img src="{{base_url}}/instance/authenticate/qrcode?format=image" />

<!-- Opção 2: Gerar com biblioteca -->
<div id="qrcode"></div>
<script src="qrcode.js"></script>
<script>
  new QRCode(document.getElementById("qrcode"), qrcodeString);
</script>
```

---

## 📚 Ver Também

- [Autenticação de Instância (Guia Completo)](AUTENTICACAO-INSTANCIA.md)
- [Diferença entre Connect e Authenticate](DIFERENCA-ENDPOINTS.md)
- [Troubleshooting](TROUBLESHOOTING.md)
- [Debug de QR Code](DEBUG-QRCODE.md)
