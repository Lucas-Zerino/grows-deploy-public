# 📮 Coleção Postman - GrowHub Gateway

## 📦 Arquivos Principais

- **`GrowHub-Gateway.postman_collection.json`** ⭐ - **Collection única combinada (IMPORTAR ESTE)**
- **`GrowHub-Gateway.postman_environment.json`** - Environment com variáveis
- **`collections/`** - Arquivos fonte separados por domínio (para desenvolvimento)

---

## 🚀 Como Usar

### 1. Importar no Postman

**Passo 1: Importar Collection**
1. Abra o Postman
2. File > Import
3. Selecione: **`GrowHub-Gateway.postman_collection.json`**
4. ✅ Será importada **UMA collection** com 9 pastas organizadas

**Passo 2: Importar Environment**
1. File > Import
2. Selecione: **`GrowHub-Gateway.postman_environment.json`**
3. Selecione o environment no canto superior direito

---

### 2. Estrutura da Collection (9 Pastas)

Ao importar, você terá UMA collection com estas pastas:

```
📁 GrowHub Gateway API
├── 🔐 Auth                    (Login, perfil, trocar senha)
├── 🏢 Companies               (CRUD de empresas)
├── 🔌 Providers               (CRUD de providers)
├── 📱 Instances API           (CRUD de instâncias)
├── 📲 Instance UAZAPI         (Autenticar, status, operar)
├── 👨‍💼 Instances Superadmin    (Gerenciar todas as instâncias)
├── 💬 Messages                (Enviar mensagens)
├── 📊 Events                  (Consultar eventos)
└── 🏥 Health                  (Health checks)
```

---

## 🔄 Gerar Collection Atualizada

Se você modificou os arquivos em `collections/`, regenere o arquivo combinado:

### Windows (PowerShell)
```powershell
cd postman
.\combine-collections.ps1
```

### Linux/Mac (Bash)
```bash
cd postman
chmod +x combine-collections.sh
./combine-collections.sh
```

Depois, reimporte o arquivo `GrowHub-Gateway.postman_collection.json` no Postman.

---

## 🔑 3 Tipos de Token

| Token          | Para que serve                    | Usa em             |
|----------------|-----------------------------------|---------------------|
| **Superadmin** | Gerenciar sistema completo        | `/api/admin/*`      |
| **Company**    | Criar/listar instâncias           | `/api/instances`    |
| **Instance**   | Operar WhatsApp (conectar, enviar)| `/instance/*`       |

### Exemplo Prático

```
1. Login superadmin
   POST /api/admin/login
   → Recebe: superadmin_token

2. Criar empresa
   POST /api/admin/companies
   Auth: Bearer {superadmin_token}
   → Recebe: company_token

3. Criar instância
   POST /api/instances
   Auth: Bearer {company_token}
   → Recebe: instance_token

4. Autenticar instância
   POST /instance/authenticate
   Auth: Bearer {instance_token}  ← Token da instância!
   → Recebe: QR code ou código
```

---

## 📋 Fluxo de Teste Completo

Execute na ordem (os tokens são salvos automaticamente):

### A. Setup Inicial (Superadmin)

1. ✅ **Auth → Login**
   - Salva `superadmin_token` automaticamente
   
2. ✅ **Companies → Criar Empresa**
   - Salva `company_token` e `company_id`
   
3. ✅ **Providers → Criar Provider**
   - Salva `provider_id`

### B. Uso da API (Como Empresa)

4. ✅ **Instances API → Criar Instância**
   - Usa `company_token`
   - Salva `instance_id` e `instance_token`

5. ✅ **Instance UAZAPI → Autenticar (QR Code)**
   - Usa `instance_token`
   - Retorna QR code para escanear

6. ✅ **Instance UAZAPI → Status**
   - Verifica se conectou

7. ✅ **Messages → Enviar Mensagem**
   - Envia mensagem de teste

8. ✅ **Events → Listar Eventos**
   - Consulta eventos de entrega

---

## 🆕 Novidade: Autenticação Reformulada

O endpoint de autenticação foi reformulado! Agora você escolhe o método:

### Método 1: QR Code (Desktop)
```http
POST /instance/authenticate
Authorization: Bearer {instance_token}
{
  "method": "qrcode"
}
```

### Método 2: Código por Telefone (Mobile)
```http
POST /instance/authenticate
Authorization: Bearer {instance_token}
{
  "method": "phone_code",
  "phone_number": "5511999999999"
}
```

Ver: [../docs/AUTENTICACAO-INSTANCIA.md](../docs/AUTENTICACAO-INSTANCIA.md)

---

## 🎯 Variáveis de Environment

Variáveis preenchidas automaticamente:

| Variável            | Descrição                | Quando é preenchida      |
|---------------------|--------------------------|--------------------------|
| `base_url`          | URL da API               | ✅ Pré-configurado       |
| `superadmin_email`  | Email do superadmin      | ✅ Pré-configurado       |
| `superadmin_password`| Senha do superadmin     | ✅ Pré-configurado       |
| `superadmin_token`  | Token do superadmin      | 🔄 Após login            |
| `company_token`     | Token da empresa         | 🔄 Após criar empresa    |
| `company_id`        | ID da empresa            | 🔄 Após criar empresa    |
| `provider_id`       | ID do provider           | 🔄 Após criar provider   |
| `instance_id`       | ID da instância          | 🔄 Após criar instância  |
| `instance_token`    | Token da instância       | 🔄 Após criar instância  |

---

## 🔧 Desenvolvimento: Collections Modulares

A pasta `collections/` contém arquivos separados por domínio:

```
collections/
├── 01-auth.json                    (Autenticação)
├── 02-companies.json               (Empresas)
├── 03-providers.json               (Providers)
├── 04-instances-api.json           (CRUD de instâncias)
├── 05-instance-uazapi.json         (Operações da instância) ✨ Atualizado!
├── 06-instances-superadmin.json    (Administração)
├── 07-messages.json                (Mensagens)
├── 08-events.json                  (Eventos)
└── 09-health.json                  (Health checks)
```

**Quando modificar:**
1. Edite o arquivo específico em `collections/`
2. Execute `combine-collections.ps1` ou `.sh`
3. Reimporte no Postman

---

## 🐛 Troubleshooting

### Token Inválido
- Faça login novamente (Auth → Login)
- Verifique se o environment correto está selecionado

### Múltiplas Collections Importadas
- **Problema:** Importou a pasta `collections/` inteira
- **Solução:** Delete todas e importe apenas `GrowHub-Gateway.postman_collection.json`

### QR Code não aparece
- Certifique-se que chamou `/instance/authenticate` primeiro
- Aguarde 3-5 segundos e chame `/instance/status` novamente

---

## 📚 Documentação Adicional

- [Como Conectar Instância](../docs/COMO-CONECTAR-INSTANCIA.md)
- [Autenticação de Instância](../docs/AUTENTICACAO-INSTANCIA.md)
- [Troubleshooting](../docs/TROUBLESHOOTING.md)
- [API Examples](../API_EXAMPLES.md)

---

## 💡 Dicas

1. ✅ **Importe apenas o arquivo combinado** para ter tudo organizado em uma collection
2. ✅ **Todos os tokens são salvos automaticamente** após as requisições
3. ✅ **Execute na ordem sugerida** para o primeiro teste
4. ✅ **Use Postman Runner** para executar toda a collection de uma vez
5. ✅ **Regenere o arquivo combinado** após modificar arquivos em `collections/`

---

## 🐳 Ambiente Docker

Certifique-se de que o ambiente está rodando:

```bash
# Windows
.\scripts\windows\dev-up.ps1

# Linux/Mac
make dev-up
```

API estará em: http://localhost:8000

---

**🎉 Pronto para usar!** Importe `GrowHub-Gateway.postman_collection.json` e comece a testar!
