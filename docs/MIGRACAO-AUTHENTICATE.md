# 🔄 Guia de Migração: Connect → Authenticate

## ⚠️ BREAKING CHANGE

O endpoint `/instance/connect` foi **removido** e substituído por `/instance/authenticate`.

---

## 📋 O que Mudou

### ❌ Antes (Removido)
```http
POST /instance/connect
{
  "phone": "5511999999999"  // opcional
}
```

### ✅ Agora (Novo)
```http
POST /instance/authenticate
{
  "method": "qrcode"  // ou "phone_code"
  "phone_number": "5511999999999"  // se phone_code
}
```

---

## 🚀 Como Migrar

### Migração 1: Connect sem phone (QR Code)

**Antes:**
```http
POST /instance/connect
Authorization: Bearer {token}
{}
```

**Depois:**
```http
POST /instance/authenticate
Authorization: Bearer {token}
{
  "method": "qrcode"
}
```

---

### Migração 2: Connect com phone (Código)

**Antes:**
```http
POST /instance/connect
Authorization: Bearer {token}
{
  "phone": "5511999999999"
}
```

**Depois:**
```http
POST /instance/authenticate
Authorization: Bearer {token}
{
  "method": "phone_code",
  "phone_number": "5511999999999"
}
```

---

## 💻 Exemplos de Código

### JavaScript/TypeScript

**Antes:**
```javascript
// Antigo
async function connectInstance(token, phone) {
  const response = await fetch('/instance/connect', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ phone })
  });
  
  return response.json();
}
```

**Depois:**
```javascript
// Novo
async function authenticateInstance(token, method, phoneNumber = null) {
  const payload = { method };
  
  if (method === 'phone_code' && phoneNumber) {
    payload.phone_number = phoneNumber;
  }
  
  const response = await fetch('/instance/authenticate', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
  
  return response.json();
}

// Uso:
await authenticateInstance(token, 'qrcode');
await authenticateInstance(token, 'phone_code', '5511999999999');
```

---

### PHP

**Antes:**
```php
// Antigo
function connectInstance($token, $phone = null) {
    $payload = $phone ? ['phone' => $phone] : [];
    
    $ch = curl_init('http://localhost:8000/instance/connect');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    return json_decode(curl_exec($ch), true);
}
```

**Depois:**
```php
// Novo
function authenticateInstance($token, $method, $phoneNumber = null) {
    $payload = ['method' => $method];
    
    if ($method === 'phone_code' && $phoneNumber) {
        $payload['phone_number'] = $phoneNumber;
    }
    
    $ch = curl_init('http://localhost:8000/instance/authenticate');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    return json_decode(curl_exec($ch), true);
}

// Uso:
authenticateInstance($token, 'qrcode');
authenticateInstance($token, 'phone_code', '5511999999999');
```

---

### Python

**Antes:**
```python
# Antigo
import requests

def connect_instance(token, phone=None):
    payload = {'phone': phone} if phone else {}
    
    response = requests.post(
        'http://localhost:8000/instance/connect',
        headers={
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        },
        json=payload
    )
    
    return response.json()
```

**Depois:**
```python
# Novo
import requests

def authenticate_instance(token, method, phone_number=None):
    payload = {'method': method}
    
    if method == 'phone_code' and phone_number:
        payload['phone_number'] = phone_number
    
    response = requests.post(
        'http://localhost:8000/instance/authenticate',
        headers={
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        },
        json=payload
    )
    
    return response.json()

# Uso:
authenticate_instance(token, 'qrcode')
authenticate_instance(token, 'phone_code', '5511999999999')
```

---

## 📊 Checklist de Migração

- [ ] Substituir chamadas a `/instance/connect` por `/instance/authenticate`
- [ ] Adicionar campo `method` no payload
- [ ] Renomear `phone` para `phone_number`
- [ ] Atualizar testes automatizados
- [ ] Atualizar documentação interna
- [ ] Comunicar time de desenvolvimento
- [ ] Testar em ambiente de staging
- [ ] Deploy para produção

---

## 🎯 Benefícios da Migração

1. ✅ **Método explícito**: Fica claro qual método está sendo usado
2. ✅ **Melhor UX**: Pode oferecer ambas opções ao usuário
3. ✅ **Mais flexível**: Fácil adicionar novos métodos no futuro
4. ✅ **Código mais limpo**: Lógica separada por método
5. ✅ **QR code na resposta**: Não precisa chamar `/status` depois

---

## 🔍 Como Identificar Uso do Endpoint Antigo

### Grep no Código

```bash
# Linux/Mac
grep -r "instance/connect" .

# Windows PowerShell
Select-String -Path . -Pattern "instance/connect" -Recurse
```

### Logs do Servidor

```bash
# Ver se ainda há chamadas ao endpoint antigo
cat logs/app-*.log | grep "instance/connect"
```

---

## 📅 Timeline

| Data       | Ação                                      |
|------------|-------------------------------------------|
| 14/10/2025 | `/instance/authenticate` criado           |
| 14/10/2025 | `/instance/connect` removido              |
| -          | Migração obrigatória                      |

---

## 🆘 Suporte

Se encontrar problemas na migração:

1. Ver [Troubleshooting](TROUBLESHOOTING.md)
2. Ver [Debug de QR Code](DEBUG-QRCODE.md)
3. Verificar logs da API
4. Testar com Postman/Insomnia

---

## 📚 Documentação Relacionada

- [Autenticação de Instância](AUTENTICACAO-INSTANCIA.md) - Guia completo do novo sistema
- [Como Conectar Instância](COMO-CONECTAR-INSTANCIA.md) - Passo a passo atualizado
- [Diferença entre Endpoints](DIFERENCA-ENDPOINTS.md) - Comparação detalhada

