# 🔐 Autenticação de Instâncias - Guia Completo

## 📋 Diferença entre Connect e Authenticate

### `/instance/connect` ❓ Deprecado/Legacy
**O que faz:** Inicia a sessão (equivalente ao "start" da WAHA)

**Quando usar:** 
- Compatibilidade com sistemas antigos
- Reconectar após desconexão

### `/instance/authenticate` ✨ Novo e Recomendado
**O que faz:** Autentica a instância usando o método escolhido (QR Code OU Código)

**Quando usar:**
- **Sempre** para novas implementações
- Escolher método de autenticação dinamicamente
- Melhor UX (usuário escolhe QR ou código)

---

## 🚀 Novo Fluxo Recomendado

### 1. Criar Instância

```http
POST /api/instances
Authorization: Bearer {company_token}
Content-Type: application/json

{
  "instance_name": "vendas"
}
```

**Resposta:**
```json
{
  "data": {
    "id": 1,
    "token": "inst_abc123...",
    "instance_name": "vendas",
    "status": "creating"
  }
}
```

---

### 2. Autenticar (Escolher Método)

#### Opção A: QR Code (Recomendado para Desktop)

```http
POST /instance/authenticate
Authorization: Bearer inst_abc123...
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

### 3. Obter QR Code (Formato Específico)

#### Raw (String)

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

#### Imagem PNG

```http
GET /instance/authenticate/qrcode?format=image
Authorization: Bearer inst_abc123...
```

**Resposta:** Imagem PNG diretamente (Content-Type: image/png)

---

### 4. Verificar Status

```http
GET /instance/status
Authorization: Bearer inst_abc123...
```

**Resposta:**
```json
{
  "id": "1-vendas",
  "name": "vendas",
  "status": "connected",
  "phone_number": "5511999999999@c.us"
}
```

---

## 📊 Comparação de Métodos

| Método      | Onde Usar      | Vantagens                          | Desvantagens                    |
|-------------|----------------|------------------------------------|---------------------------------|
| QR Code     | Desktop/Web    | Mais rápido, visual                | Precisa de câmera               |
| Código      | Mobile/App     | Não precisa câmera, mais seguro    | Precisa digitar código          |

---

## 🔄 Fluxo Antigo vs Novo

### ❌ Fluxo Antigo (Ainda funciona, mas não recomendado)

```
1. POST /instance/connect
2. GET /instance/status (pegar QR code)
3. Aguardar conexão
```

**Problemas:**
- Não escolhe método
- Sempre QR code
- Menos flexível

### ✅ Fluxo Novo (Recomendado)

```
1. POST /instance/authenticate (escolhe método)
2. GET /instance/status (verificar)
3. (Opcional) GET /instance/authenticate/qrcode?format=image
```

**Vantagens:**
- Escolhe método dinamicamente
- Suporta QR code E código
- Melhor UX
- Mais flexível

---

## 🎯 Casos de Uso

### Caso 1: Sistema Web (Desktop)

```javascript
// 1. Criar instância
const instance = await createInstance({ name: 'vendas' });

// 2. Autenticar com QR Code
const auth = await fetch('/instance/authenticate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${instance.token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ method: 'qrcode' })
});

const { qrcode } = await auth.json();

// 3. Mostrar QR code na tela
showQRCode(qrcode);

// 4. Pooling de status
const checkStatus = setInterval(async () => {
  const status = await getInstanceStatus(instance.token);
  if (status.status === 'connected') {
    clearInterval(checkStatus);
    alert('Conectado!');
  }
}, 2000);
```

### Caso 2: App Mobile

```javascript
// 1. Criar instância
const instance = await createInstance({ name: 'vendas' });

// 2. Autenticar com código
const auth = await fetch('/instance/authenticate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${instance.token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ 
    method: 'phone_code',
    phone_number: user.phone
  })
});

const { code } = await auth.json();

// 3. Mostrar código para usuário digitar no WhatsApp
alert(`Digite este código no WhatsApp: ${code}`);

// 4. Aguardar conexão
waitForConnection(instance.token);
```

### Caso 3: Escolha Dinâmica (Melhor UX)

```javascript
// Detectar dispositivo
const isMobile = /iPhone|iPad|Android/i.test(navigator.userAgent);

const method = isMobile ? 'phone_code' : 'qrcode';
const payload = method === 'phone_code' 
  ? { method, phone_number: user.phone }
  : { method };

const auth = await authenticateInstance(instance.token, payload);

if (method === 'qrcode') {
  showQRCode(auth.qrcode);
} else {
  showCode(auth.code);
}
```

---

## 🔧 Endpoints Disponíveis

### Autenticação (Novo)

| Método | Endpoint                          | Descrição                        |
|--------|-----------------------------------|----------------------------------|
| POST   | `/instance/authenticate`          | Autenticar (escolher método)     |
| GET    | `/instance/authenticate/qrcode`   | Obter QR code (raw ou imagem)    |

### Gerenciamento (Compatibilidade)

| Método | Endpoint                          | Descrição                        |
|--------|-----------------------------------|----------------------------------|
| POST   | `/instance/connect`               | Iniciar sessão (legacy)          |
| POST   | `/instance/disconnect`            | Desconectar                      |
| GET    | `/instance/status`                | Ver status                       |
| POST   | `/instance/updateInstanceName`    | Atualizar nome                   |
| DELETE | `/instance`                       | Deletar instância                |

---

## 💡 Recomendações

1. ✅ **Use `/instance/authenticate`** para novas implementações
2. ✅ **Detecte o dispositivo** e escolha o método automaticamente
3. ✅ **Ofereça ambas opções** ao usuário se possível
4. ⚠️ **Mantenha `/instance/connect`** apenas para compatibilidade
5. ✅ **Use `format=image`** para QR code se for exibir na web

---

## 🐛 Troubleshooting

### QR Code não aparece

**Causa:** Sessão ainda está inicializando

**Solução:** Aguarde 3-5 segundos após `/authenticate` e tente novamente

### Código não chega

**Causa:** Número de telefone inválido ou WhatsApp não está instalado

**Solução:** 
- Verifique o formato do número (ex: 5511999999999)
- Certifique-se que o WhatsApp está instalado no celular

### Erro 400 "Invalid method"

**Causa:** Método inválido no payload

**Solução:** Use `"method": "qrcode"` ou `"method": "phone_code"`

---

## 📚 Exemplos Completos

Ver: [API_EXAMPLES.md](API_EXAMPLES.md)

