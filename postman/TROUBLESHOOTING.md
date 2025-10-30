# 🔧 Troubleshooting - Postman Collections

## 🚨 Problema: Collection Vazia ao Importar

### **Sintomas:**
- Arquivo `GrowHub-Gateway.postman_collection.json` importa vazio no Postman
- Collection aparece sem pastas ou endpoints
- Arquivo pode ter tamanho correto mas conteúdo vazio

### **Causas Possíveis:**

#### 1. **Cache do Postman**
- Postman carregou versão antiga em cache
- Arquivo foi atualizado mas Postman não recarregou

#### 2. **Arquivo Corrompido**
- Script gerou arquivo com estrutura inválida
- Problema na conversão JSON do PowerShell

#### 3. **Encoding Incorreto**
- Arquivo salvo com encoding errado
- Caracteres especiais corrompidos

#### 4. **Problema de Profundidade JSON**
- PowerShell não converteu objetos aninhados corretamente
- Depth insuficiente na conversão

## ✅ **Soluções:**

### **Solução 1: Limpar Cache do Postman**
```bash
# 1. Fechar Postman completamente
# 2. Deletar arquivo da collection
# 3. Regenerar arquivo
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Force
# 4. Importar novamente
```

### **Solução 2: Validar Arquivo**
```bash
# Verificar se arquivo está válido
powershell -ExecutionPolicy Bypass -File validate-collection.ps1
```

### **Solução 3: Regenerar com Script Melhorado**
```bash
# Usar script com maior profundidade JSON
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Verbose -Force
```

### **Solução 4: Importar Individualmente**
```bash
# Se problema persistir, importar coleções individuais
# File > Import > Selecionar pasta collections/
```

## 🔍 **Diagnóstico:**

### **Verificar Tamanho do Arquivo:**
```powershell
Get-Item "GrowHub-Gateway.postman_collection.json" | Select-Object Name, Length
```
**Esperado:** ~275 KB

### **Verificar Estrutura JSON:**
```powershell
# Verificar se tem pastas
$json = Get-Content "GrowHub-Gateway.postman_collection.json" | ConvertFrom-Json
$json.item.Count
```
**Esperado:** 13

### **Verificar Conteúdo:**
```powershell
# Verificar se array item não está vazio
$json = Get-Content "GrowHub-Gateway.postman_collection.json" | ConvertFrom-Json
$json.item[0].name
```
**Esperado:** "Auth - Superadmin"

## 🛠️ **Scripts de Diagnóstico:**

### **1. validate-collection.ps1**
Valida estrutura completa da collection:
```bash
powershell -ExecutionPolicy Bypass -File validate-collection.ps1
```

### **2. combine-collections-windows.ps1 -Verbose**
Gera collection com logs detalhados:
```bash
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Verbose -Force
```

## 📋 **Checklist de Verificação:**

- [ ] **Arquivo existe** e tem tamanho correto (~275 KB)
- [ ] **Estrutura JSON** válida (info + item)
- [ ] **Array item** não está vazio (13 pastas)
- [ ] **Encoding UTF-8** correto
- [ ] **Postman** não tem cache antigo
- [ ] **Script** executou sem erros

## 🚀 **Prevenção:**

### **1. Sempre usar -Force**
```bash
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Force
```

### **2. Validar após gerar**
```bash
powershell -ExecutionPolicy Bypass -File validate-collection.ps1
```

### **3. Limpar cache do Postman**
- Fechar Postman
- Deletar collection antiga
- Importar nova versão

## 📞 **Se Problema Persistir:**

### **1. Verificar Logs:**
```bash
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Verbose -Force
```

### **2. Testar Coleção Individual:**
Importar apenas uma coleção para testar se problema é específico

### **3. Verificar Versão do PowerShell:**
```powershell
$PSVersionTable.PSVersion
```

### **4. Usar Script Alternativo:**
```bash
powershell -ExecutionPolicy Bypass -File combine-collections.ps1
```

## 🎯 **Comandos Rápidos:**

```bash
# Regenerar collection
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Force

# Validar collection
powershell -ExecutionPolicy Bypass -File validate-collection.ps1

# Modo debug completo
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1 -Verbose -Force
```

## ✅ **Resultado Esperado:**

Após executar as soluções, você deve ter:
- **Arquivo válido** com 275+ KB
- **13 pastas** organizadas
- **76 endpoints** funcionais
- **Importação** bem-sucedida no Postman
