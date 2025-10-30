# 📜 Scripts de Combinação - Postman Collections

## 🚀 Scripts Disponíveis

### 1. **combine-collections.ps1** (Script Original)
- ✅ **Funciona**: Sim
- 📝 **Descrição**: Script básico para combinar coleções
- 🎯 **Uso**: `powershell -ExecutionPolicy Bypass -File combine-collections.ps1`

### 2. **combine-collections-windows.ps1** (Script Avançado) ⭐
- ✅ **Funciona**: Sim
- 📝 **Descrição**: Script otimizado para Windows com validação robusta
- 🎯 **Uso**: `powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1`

## 🔧 Parâmetros do Script Avançado

### **-Verbose**
Mostra detalhes do processamento de cada arquivo.

```powershell
.\combine-collections-windows.ps1 -Verbose
```

### **-Force**
Sobrescreve o arquivo de saída se já existir.

```powershell
.\combine-collections-windows.ps1 -Force
```

### **Combinado**
```powershell
.\combine-collections-windows.ps1 -Verbose -Force
```

## 📊 Estatísticas Atuais

- **Total de Coleções**: 13
- **Total de Endpoints**: 76
- **Tamanho do Arquivo**: ~275 KB
- **Encoding**: UTF-8

## 📁 Coleções Incluídas

| # | Nome | Endpoints | Descrição |
|---|------|-----------|-----------|
| 01 | Auth - Superadmin | 3 | Autenticação e perfil |
| 02 | Companies - Superadmin | 5 | Gerenciamento de empresas |
| 03 | Providers - Superadmin | 6 | Gerenciamento de providers |
| 04 | Instancias | 3 | CRUD de instâncias |
| 05 | Instance UAZAPI | 8 | Operações de instância |
| 06 | Administracao de Instancias | 3 | Admin de instâncias |
| 07 | Enviar Mensagem | 8 | Envio de mensagens |
| 08 | Acoes na mensagem e Buscar | 7 | Ações em mensagens |
| 09 | Contatos | 7 | Gerenciamento de contatos |
| 10 | Grupos | 13 | Gerenciamento de grupos |
| 11 | Comunidades | 8 | Gerenciamento de comunidades |
| 12 | Events | 2 | Eventos do sistema |
| 13 | Health & Monitoring | 2 | Monitoramento |
| 14 | Instance Webhooks | 9 | **Múltiplos webhooks** |

## 🎯 Como Usar

### **Opção 1: Script Simples**
```bash
cd postman
powershell -ExecutionPolicy Bypass -File combine-collections.ps1
```

### **Opção 2: Script Avançado (Recomendado)**
```bash
cd postman
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Force
```

### **Opção 3: Modo Detalhado**
```bash
cd postman
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Verbose -Force
```

## 📋 Pré-requisitos

- ✅ **Windows PowerShell** 5.1 ou superior
- ✅ **ExecutionPolicy** configurado (Bypass ou RemoteSigned)
- ✅ **Pasta collections** com arquivos JSON válidos

## 🔍 Validações do Script Avançado

- ✅ **Estrutura JSON** válida
- ✅ **Encoding UTF-8** correto
- ✅ **Arquivos vazios** detectados
- ✅ **Nomes de coleção** válidos
- ✅ **Contagem de endpoints** precisa
- ✅ **Tamanho do arquivo** calculado

## 🚨 Troubleshooting

### **Erro: ExecutionPolicy**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### **Erro: Arquivo já existe**
```powershell
.\combine-collections-windows.ps1 -Force
```

### **Erro: Encoding**
O script avançado usa UTF-8 automaticamente.

### **Erro: JSON inválido**
Verifique se os arquivos JSON estão bem formados.

## 📈 Melhorias Implementadas

### **Script Original**
- ✅ Combina todas as coleções
- ✅ Ordena por nome
- ✅ Ignora arquivos README

### **Script Avançado**
- ✅ **Validação robusta** de JSON
- ✅ **Detecção de erros** detalhada
- ✅ **Parâmetros** -Verbose e -Force
- ✅ **Encoding UTF-8** correto
- ✅ **Estatísticas** detalhadas
- ✅ **Tamanho do arquivo** calculado
- ✅ **Mensagens** informativas
- ✅ **Tratamento de erros** melhorado

## 🎉 Resultado

Após executar qualquer script, você terá:

- **Arquivo**: `GrowHub-Gateway.postman_collection.json`
- **Conteúdo**: 13 pastas com 76 endpoints
- **Tamanho**: ~275 KB
- **Formato**: Postman Collection v2.1.0

## 📥 Importar no Postman

1. **Abrir Postman**
2. **File > Import**
3. **Selecionar**: `GrowHub-Gateway.postman_collection.json`
4. **Resultado**: Uma collection com 13 pastas organizadas

## 🔄 Atualizações

Quando adicionar novas coleções:

1. **Adicione o arquivo** na pasta `collections/`
2. **Execute o script** novamente
3. **Importe** a nova versão no Postman

O script detecta automaticamente novos arquivos JSON!