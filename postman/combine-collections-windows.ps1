# Combinar Collections do Postman - Script PowerShell para Windows
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Combinando Collections do Postman" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$CollectionsPath = Join-Path $ScriptDir "collections"
$OutputFile = Join-Path $ScriptDir "GrowHub-Gateway.postman_collection.json"

# Verificar se o diretório collections existe
if (-not (Test-Path $CollectionsPath)) {
    Write-Host "Erro: Diretório collections não encontrado em $CollectionsPath" -ForegroundColor Red
    exit 1
}

# Criar objeto da collection principal
$MainCollection = @{
    info = @{
        _postman_id = "growhub-gateway-complete-2025"
        name = "GrowHub Gateway API"
        description = "Gateway de mensagens para WhatsApp (WAHA/UAZAPI). Collection organizada em pastas por funcionalidade."
        schema = "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    }
    item = @()
}

# Processar cada arquivo JSON
$JsonFiles = Get-ChildItem -Path $CollectionsPath -Filter "*.json" | Where-Object { $_.Name -ne "README.md" }

foreach ($File in $JsonFiles) {
    Write-Host "Lendo: $($File.Name)" -ForegroundColor Yellow
    
    try {
        # Ler e validar JSON
        $JsonContent = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        $JsonObject = $JsonContent | ConvertFrom-Json
        
        # Verificar se tem as propriedades necessárias
        if (-not $JsonObject.info -or -not $JsonObject.info.name) {
            Write-Host "  - Erro: Estrutura inválida em $($File.Name)" -ForegroundColor Red
            continue
        }
        
        # Criar folder
        $Folder = @{
            name = $JsonObject.info.name
            item = $JsonObject.item
        }
        
        # Adicionar à collection principal
        $MainCollection.item += $Folder
        
        Write-Host "  - Adicionado: $($JsonObject.info.name)" -ForegroundColor Green
        
    } catch {
        Write-Host "  - Erro ao processar $($File.Name): $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Converter para JSON e salvar
try {
    $JsonOutput = $MainCollection | ConvertTo-Json -Depth 10
    $JsonOutput | Out-File -FilePath $OutputFile -Encoding UTF8 -NoNewline
    
    Write-Host ""
    Write-Host "Arquivo combinado criado com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Arquivo:" -ForegroundColor Cyan
    Write-Host "  $OutputFile" -ForegroundColor White
    Write-Host ""
    Write-Host "Total de pastas: $($MainCollection.item.Count)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Para importar no Postman:" -ForegroundColor Cyan
    Write-Host "  1. Abra o Postman" -ForegroundColor White
    Write-Host "  2. File > Import" -ForegroundColor White
    Write-Host "  3. Selecione: GrowHub-Gateway.postman_collection.json" -ForegroundColor White
    Write-Host "  4. Será importada UMA collection com múltiplas pastas" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host "Erro ao salvar arquivo: $($_.Exception.Message)" -ForegroundColor Red
}
