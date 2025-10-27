# Scripts para Combinar Collections do Postman

Este diretório contém scripts para combinar múltiplas collections do Postman em uma única collection organizada.

## Arquivos Disponíveis

### 1. `combine-collections.sh` (Linux/macOS)
- **Sistema**: Linux, macOS, WSL
- **Dependências**: `jq` (instalar com `sudo apt-get install jq` ou `brew install jq`)
- **Uso**: `./combine-collections.sh`

### 2. `combine-collections-windows.ps1` (Windows)
- **Sistema**: Windows PowerShell
- **Dependências**: Nenhuma (usa cmdlets nativos do PowerShell)
- **Uso**: `powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1`

### 3. `combine-collections.ps1` (Versão Original)
- **Sistema**: Windows PowerShell
- **Status**: Funcional, mas pode ter problemas de encoding
- **Uso**: `powershell -ExecutionPolicy Bypass -File combine-collections.ps1`

## Como Usar

### No Windows (Recomendado)
```powershell
cd postman
powershell -ExecutionPolicy Bypass -File combine-collections-windows.ps1
```

### No Linux/macOS
```bash
cd postman
chmod +x combine-collections.sh
./combine-collections.sh
```

## O que o Script Faz

1. **Lê** todos os arquivos `.json` do diretório `collections/`
2. **Valida** se são JSONs válidos do Postman
3. **Combina** todas as collections em uma única collection
4. **Organiza** cada collection original como uma pasta
5. **Gera** o arquivo `GrowHub-Gateway.postman_collection.json`

## Estrutura Resultante

```
GrowHub Gateway API
├── Auth - Superadmin
├── Companies - Superadmin
├── Providers - Superadmin
├── Instancias
├── Administracao de Instancias
├── Enviar Mensagem
├── Acoes na mensagem e Buscar
├── Contatos
├── Grupos
├── Comunidades
├── Events
└── Health & Monitoring
```

## Importar no Postman

1. Abra o Postman
2. File > Import
3. Selecione: `GrowHub-Gateway.postman_collection.json`
4. Será importada UMA collection com múltiplas pastas

## Solução de Problemas

### Erro: "jq não está instalado" (Linux/macOS)
```bash
# Ubuntu/Debian
sudo apt-get install jq

# macOS
brew install jq

# Windows (Chocolatey)
choco install jq
```

### Erro: "ExecutionPolicy" (Windows)
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Erro: "JSON inválido"
- Verifique se os arquivos na pasta `collections/` são JSONs válidos do Postman
- Certifique-se de que não há caracteres especiais ou encoding incorreto

## Arquivos de Saída

- **GrowHub-Gateway.postman_collection.json**: Collection combinada pronta para importar
- **Logs**: O script mostra o progresso e erros no console
