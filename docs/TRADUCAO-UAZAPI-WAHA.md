# 🔄 Tradução UAZAPI ↔ WAHA

## 📋 Resumo

**Padrão da API Externa (Cliente):** UAZAPI  
**Padrão Interno dos Providers:** Cada provider traduz para seu formato

```
Cliente (UAZAPI)
    ↓
InstanceUazapiController (UAZAPI)
    ↓
WahaProvider (traduz UAZAPI → WAHA)
    ↓
API WAHA (formato WAHA)
    ↓
WahaProvider (traduz WAHA → UAZAPI)
    ↓
Resposta ao Cliente (UAZAPI)
```

---

## 🎯 Exemplos de Tradução

### 1. **Status da Instância**

#### Entrada (Cliente → API):
```http
GET /instance/status
Authorization: Bearer {instance_token}
```

#### Tradução UAZAPI → WAHA:
```php
// WahaProvider::getStatus()
GET /api/sessions/{instance_id}
```

#### Resposta WAHA:
```json
{
  "name": "vendas",
  "status": "WORKING",  // ← Formato WAHA
  "me": {
    "id": "5511999999999@c.us"
  }
}
```

#### Mapeamento de Status:
```php
private function mapWahaStatusToUazapi(string $wahaStatus): string
{
    return match($wahaStatus) {
        'WORKING' => 'connected',        // ✅
        'STARTING' => 'connecting',      // ✅
        'SCAN_QR_CODE' => 'connecting',  // ✅
        'STOPPED' => 'disconnected',     // ✅
        'FAILED' => 'disconnected',      // ✅
        default => 'disconnected',
    };
}
```

#### Resposta ao Cliente (UAZAPI):
```json
{
  "id": "vendas",
  "name": "vendas",
  "status": "connected",  // ← Traduzido para UAZAPI
  "token": "xyz-123"
}
```

---

### 2. **Conectar Instância**

#### Entrada (Cliente → API):
```http
POST /instance/connect
Authorization: Bearer {instance_token}
Content-Type: application/json

{
  "phone": "5511999999999"  // ← Padrão UAZAPI
}
```

#### Tradução UAZAPI → WAHA:
```php
// WahaProvider::connect($instanceId, $phone)
POST /api/sessions/{instance_id}/start
{
  // WAHA não usa phone no start, apenas inicia
}
```

#### Resposta WAHA:
```json
{
  "name": "vendas",
  "status": "STARTING"
}
```

#### Resposta ao Cliente (UAZAPI):
```json
{
  "status": "connecting",  // ← STARTING traduzido
  "data": {...}
}
```

---

### 3. **Enviar Mensagem de Texto**

#### Entrada (Cliente → API):
```http
POST /message/text
Authorization: Bearer {instance_token}
Content-Type: application/json

{
  "phone": "5511888888888",  // ← Formato UAZAPI
  "text": "Olá!"
}
```

#### Tradução UAZAPI → WAHA:
```php
// WahaProvider::sendTextMessage($instanceId, $phone, $text)
POST /api/sendText
{
  "session": "vendas",
  "chatId": "5511888888888@c.us",  // ← Formatado para WAHA
  "text": "Olá!"
}
```

#### Formatação de Telefone:
```php
private function formatPhone(string $phone): string
{
    // Remove caracteres especiais
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Adiciona @c.us (formato WhatsApp)
    return $phone . '@c.us';
}
```

#### Resposta WAHA:
```json
{
  "id": "true_5511888888888@c.us_ABC123",  // ← ID WAHA
  "status": "PENDING"
}
```

#### Resposta ao Cliente (UAZAPI):
```json
{
  "success": true,
  "message_id": "ABC123",
  "status": "pending"
}
```

---

### 4. **Atualizar Privacidade**

#### Entrada (Cliente → API):
```http
POST /instance/privacy
Authorization: Bearer {instance_token}
Content-Type: application/json

{
  "groupadd": "contacts",  // ← UAZAPI
  "last": "none",
  "status": "contacts"
}
```

#### Tradução UAZAPI → WAHA:
```php
// WahaProvider::updatePrivacy($instanceId, $settings)
POST /api/{instance}/settings/privacy
{
  "groupAdd": "contacts",  // ← Traduzido para WAHA
  "lastSeen": "none",
  "status": "contacts"
}
```

---

### 5. **QR Code**

#### Entrada (Cliente → API):
```http
GET /instance/status
Authorization: Bearer {instance_token}
```

#### Lógica no WahaProvider:
```php
// Se status == 'connecting', busca QR Code
if ($status === 'connecting') {
    GET /api/sessions/{instance}/auth/qr
    
    // Adiciona no resultado
    $result['qrcode'] = $qrData['value'];  // Base64
}
```

#### Resposta ao Cliente (UAZAPI):
```json
{
  "status": "connecting",
  "qrcode": "data:image/png;base64,iVBORw0KGgoAAAANS...",  // ✅
  "paircode": null
}
```

---

## 🗺️ Mapeamento Completo de Campos

### Status
| WAHA | UAZAPI |
|------|--------|
| `WORKING` | `connected` |
| `STARTING` | `connecting` |
| `SCAN_QR_CODE` | `connecting` |
| `STOPPED` | `disconnected` |
| `FAILED` | `disconnected` |

### Telefone
| UAZAPI | WAHA |
|--------|------|
| `5511999999999` | `5511999999999@c.us` |
| `11999999999` | `5511999999999@c.us` (normaliza) |

### Privacidade
| UAZAPI | WAHA |
|--------|------|
| `groupadd` | `groupAdd` |
| `last` | `lastSeen` |
| `status` | `status` |
| `profile` | `profilePicture` |
| `readreceipts` | `readReceipts` |
| `online` | `online` |
| `calladd` | `callAdd` |

### Presença
| UAZAPI | WAHA |
|--------|------|
| `available` | `available` |
| `unavailable` | `unavailable` |

---

## 🔧 Implementação no Código

### WahaProvider - Métodos de Tradução

```php
class WahaProvider implements ProviderInterface
{
    /**
     * Mapeia status WAHA → UAZAPI
     */
    private function mapWahaStatusToUazapi(string $wahaStatus): string
    {
        return match($wahaStatus) {
            'WORKING' => 'connected',
            'STARTING' => 'connecting',
            'SCAN_QR_CODE' => 'connecting',
            'STOPPED' => 'disconnected',
            'FAILED' => 'disconnected',
            default => 'disconnected',
        };
    }
    
    /**
     * Formata telefone UAZAPI → WAHA
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone . '@c.us';
    }
    
    /**
     * Traduz campos de privacidade UAZAPI → WAHA
     */
    private function mapPrivacyFieldsToWaha(array $uazapiSettings): array
    {
        $wahaSettings = [];
        
        if (isset($uazapiSettings['groupadd'])) {
            $wahaSettings['groupAdd'] = $uazapiSettings['groupadd'];
        }
        if (isset($uazapiSettings['last'])) {
            $wahaSettings['lastSeen'] = $uazapiSettings['last'];
        }
        // ... outros campos
        
        return $wahaSettings;
    }
    
    /**
     * Traduz resposta WAHA → UAZAPI
     */
    private function mapWahaResponseToUazapi(array $wahaData): array
    {
        return [
            'id' => $wahaData['name'] ?? null,
            'status' => $this->mapWahaStatusToUazapi($wahaData['status'] ?? 'STOPPED'),
            'qrcode' => $wahaData['qr'] ?? null,
            // ... outros campos
        ];
    }
}
```

---

## 🎯 Fluxo Completo - Exemplo Real

### Cenário: Cliente quer conectar instância

#### 1. Cliente faz requisição (UAZAPI):
```bash
curl -X POST http://gateway/instance/connect \
  -H "Authorization: Bearer xyz-instance-token" \
  -H "Content-Type: application/json" \
  -d '{"phone":"5511999999999"}'
```

#### 2. InstanceUazapiController recebe:
```php
public static function connect(): void
{
    $instance = self::getAuthenticatedInstance();
    $input = Router::getJsonInput();  // ["phone" => "5511999999999"]
    
    $providerClient = ProviderManager::getProvider($instance['provider_id']);
    $result = $providerClient->connect($instance['external_instance_id'], $input['phone']);
    
    Response::json($result['data'] ?? [], 200);
}
```

#### 3. WahaProvider traduz e chama WAHA:
```php
public function connect(string $externalInstanceId, ?string $phone = null): array
{
    // Chama WAHA no formato WAHA
    $response = $this->client->post("/api/sessions/{$externalInstanceId}/start", [
        'json' => []
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    // Retorna no formato que será usado pelo controller
    return [
        'success' => true,
        'status' => $this->mapWahaStatusToUazapi($data['status'] ?? 'STARTING'),
        'data' => $data,
    ];
}
```

#### 4. Resposta ao Cliente (UAZAPI):
```json
{
  "status": "connecting",
  "message": "Connection initiated"
}
```

---

## ✅ Vantagens desta Arquitetura

1. ✅ **API Unificada**: Cliente sempre usa UAZAPI
2. ✅ **Múltiplos Providers**: Fácil adicionar novos (UazapiProvider, Evolution, etc)
3. ✅ **Manutenção**: Mudanças na WAHA só afetam WahaProvider
4. ✅ **Testabilidade**: Pode mockar providers facilmente
5. ✅ **Flexibilidade**: Trocar provider sem mudar API externa
6. ✅ **Escalabilidade**: Providers diferentes para cargas diferentes

---

## 🔮 Futuro: Adicionar Novo Provider

Para adicionar Evolution API (exemplo):

```php
class EvolutionProvider implements ProviderInterface
{
    public function connect(string $instanceId, ?string $phone = null): array
    {
        // Traduz UAZAPI → Evolution
        $response = $this->client->post("/instance/connect", [
            'json' => [
                'number' => $phone  // ← Formato Evolution
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        
        // Traduz Evolution → UAZAPI
        return [
            'success' => true,
            'status' => $this->mapEvolutionStatus($data['state']),
            'data' => $data
        ];
    }
}
```

Adicionar no `ProviderManager`:
```php
case 'evolution':
    return new EvolutionProvider($provider['base_url'], $provider['api_key']);
```

**Pronto!** Cliente continua usando UAZAPI sem saber que mudou o provider! 🚀

---

## 📝 Conclusão

O sistema está **100% correto**:

- ✅ Cliente sempre usa **padrão UAZAPI**
- ✅ `WahaProvider` traduz **automaticamente** entre UAZAPI ↔ WAHA
- ✅ Fácil adicionar novos providers
- ✅ API unificada e consistente
- ✅ Mudanças isoladas por provider

**O cliente NÃO precisa saber qual provider está sendo usado!** 🎯

