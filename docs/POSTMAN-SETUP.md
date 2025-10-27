# 📮 Setup do Postman

## 📥 Importar Collection (Forma Recomendada)

### Passo 1: Importar Collection Única

1. Abra o **Postman**
2. Clique em **Import** (canto superior esquerdo)
3. Selecione o arquivo: **`postman/GrowHub-Gateway.postman_collection.json`**
4. Clique em **Import**

✅ **Resultado:** UMA collection com **9 pastas** organizadas!

```
📁 GrowHub Gateway API
├── 🔐 Auth
├── 🏢 Companies
├── 🔌 Providers
├── 📱 Instances API
├── 📲 Instance UAZAPI
├── 👨‍💼 Instances Superadmin
├── 💬 Messages
├── 📊 Events
└── 🏥 Health
```

### Passo 2: Importar Environment

1. File > Import
2. Selecione: **`postman/GrowHub-Gateway.postman_environment.json`**
3. No canto superior direito, selecione o environment **"GrowHub Gateway - Development"**

---

## 🔄 Regenerar Collection (Após Modificações)

Se você modificou os arquivos em `postman/collections/`:

### Windows
```powershell
# Opção 1: Script direto
cd postman
.\combine-collections.ps1

# Opção 2: Via scripts/windows
.\scripts\windows\postman-combine.ps1
```

### Linux/Mac
```bash
# Opção 1: Script direto
cd postman
./combine-collections.sh

# Opção 2: Via make
make postman
```

Depois, **reimporte** o arquivo `GrowHub-Gateway.postman_collection.json` no Postman.

---

## 📁 Estrutura de Arquivos

```
postman/
├── GrowHub-Gateway.postman_collection.json     ← IMPORTAR ESTE (único)
├── GrowHub-Gateway.postman_environment.json    ← Variáveis
├── combine-collections.ps1                     ← Script Windows
├── combine-collections.sh                      ← Script Linux/Mac
└── collections/                                ← Fonte (separado)
    ├── 01-auth.json
    ├── 02-companies.json
    ├── 03-providers.json
    ├── 04-instances-api.json
    ├── 05-instance-uazapi.json
    ├── 06-instances-superadmin.json
    ├── 07-messages.json
    ├── 08-events.json
    └── 09-health.json
```

### Por que Dois Formatos?

1. **`GrowHub-Gateway.postman_collection.json`** (Combinado)
   - ✅ Para **importar no Postman** (uma única collection)
   - ✅ Gerado automaticamente pelos scripts
   - ✅ Fácil de usar

2. **`collections/*.json`** (Separado)
   - ✅ Para **desenvolvimento** (organização)
   - ✅ Mais fácil de editar
   - ✅ Melhor para Git (diff)

---

## 🎯 Workflow Recomendado

### Para Usuários (Só testar a API)

1. Importar `GrowHub-Gateway.postman_collection.json`
2. Importar `GrowHub-Gateway.postman_environment.json`
3. Usar e testar!

### Para Desenvolvedores (Modificar collections)

1. Editar arquivos em `collections/`
2. Executar `combine-collections.ps1` (Windows) ou `.sh` (Linux/Mac)
3. Commit ambos os arquivos (fonte e combinado)
4. Usuários reimportam o arquivo combinado

---

## 🔑 Variáveis do Environment

Após importar o environment, você terá estas variáveis:

| Variável            | Valor Padrão              | Auto-preenchida? |
|---------------------|---------------------------|------------------|
| `base_url`          | http://localhost:8000     | ✅               |
| `superadmin_email`  | admin@growhub.com         | ✅               |
| `superadmin_password`| admin123                 | ✅               |
| `superadmin_token`  | -                         | 🔄 Após login    |
| `company_token`     | -                         | 🔄 Após criar empresa |
| `company_id`        | -                         | 🔄 Após criar empresa |
| `provider_id`       | -                         | 🔄 Após criar provider |
| `instance_id`       | -                         | 🔄 Após criar instância |
| `instance_token`    | -                         | 🔄 Após criar instância |

**Scripts de teste** salvam os tokens automaticamente! 🚀

---

## 🚀 Teste Rápido

Execute as requisições nesta ordem:

```
1. 🔐 Auth → Login
   ↓ Salva superadmin_token

2. 🏢 Companies → Criar Empresa
   ↓ Salva company_token

3. 🔌 Providers → Criar Provider
   ↓ Salva provider_id

4. 📱 Instances API → Criar Instância
   ↓ Salva instance_id e instance_token

5. 📲 Instance UAZAPI → Autenticar (QR Code)
   ↓ Retorna QR code

6. 📲 Instance UAZAPI → Status
   ↓ Verifica conexão

7. 💬 Messages → Enviar Mensagem
   ↓ Envia primeira mensagem
```

---

## 🐛 Problemas Comuns

### Múltiplas Collections Importadas

**Problema:** Importou a pasta `collections/` inteira e agora tem 9 collections separadas.

**Solução:**
1. Delete todas as collections importadas
2. Importe apenas: `GrowHub-Gateway.postman_collection.json`
3. Agora terá **1 collection com 9 pastas** ✅

### Token Inválido

**Problema:** Erro 401 "Unauthorized"

**Solução:**
1. Execute **Auth → Login** novamente
2. Verifique se o environment está selecionado
3. Verifique se está usando o token correto (superadmin vs company vs instance)

### Environment não aparece

**Problema:** Environment não está disponível

**Solução:**
1. File > Import
2. Selecione `GrowHub-Gateway.postman_environment.json`
3. Selecione no canto superior direito

---

## 📖 Documentação Adicional

- [Como Conectar Instância](../docs/COMO-CONECTAR-INSTANCIA.md)
- [Autenticação de Instância](../docs/AUTENTICACAO-INSTANCIA.md)
- [Troubleshooting](../docs/TROUBLESHOOTING.md)

---

## 💡 Dicas

1. ✅ **Sempre importe o arquivo combinado**, não a pasta collections/
2. ✅ **Selecione o environment** antes de testar
3. ✅ **Execute na ordem** para o primeiro teste
4. ✅ **Tokens são salvos automaticamente** pelos scripts
5. ✅ **Regenere** após modificar arquivos em collections/

