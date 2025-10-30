# Combinar Collections do Postman - Funciona no Windows
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Combinando Collections do Postman" -ForegroundColor Cyan
Write-Host "  GrowHub Gateway API v2.0" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$CollectionsPath = Join-Path $ScriptDir "collections"
$OutputFile = Join-Path $ScriptDir "GrowHub-Gateway.postman_collection.json"

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
$JsonFiles = Get-ChildItem -Path $CollectionsPath -Filter "*.json" | Where-Object { $_.Name -ne "README.md" -and $_.Name -notlike "README-*" } | Sort-Object Name

Write-Host "Encontrados $($JsonFiles.Count) arquivos de coleção:" -ForegroundColor Yellow
Write-Host ""

$SuccessCount = 0
$ErrorCount = 0

foreach ($File in $JsonFiles) {
    Write-Host "Lendo: $($File.Name)" -ForegroundColor Yellow
    
    try {
        # Ler e validar JSON
        $JsonContent = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        $JsonObject = $JsonContent | ConvertFrom-Json
        
        # Validar estrutura básica
        if (-not $JsonObject.info -or -not $JsonObject.item) {
            throw "Estrutura JSON inválida - falta 'info' ou 'item'"
        }
        
        # Criar folder
        $Folder = @{
            name = $JsonObject.info.name
            item = $JsonObject.item
        }
        
        # Adicionar à collection principal
        $MainCollection.item += $Folder
        
        $SuccessCount++
        Write-Host "  ✅ Adicionado: $($JsonObject.info.name) ($($JsonObject.item.Count) endpoints)" -ForegroundColor Green
        
    } catch {
        $ErrorCount++
        Write-Host "  ❌ Erro ao processar $($File.Name): $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Resumo do processamento:" -ForegroundColor Cyan
Write-Host "  ✅ Sucessos: $SuccessCount" -ForegroundColor Green
Write-Host "  ❌ Erros: $ErrorCount" -ForegroundColor Red
Write-Host ""

# Converter para JSON e salvar
try {
    $JsonOutput = $MainCollection | ConvertTo-Json -Depth 10
    $JsonOutput | Out-File -FilePath $OutputFile -Encoding UTF8 -NoNewline
    
    Write-Host ""
    Write-Host "Arquivo combinado criado com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Arquivo gerado:" -ForegroundColor Cyan
    Write-Host "  $OutputFile" -ForegroundColor White
    Write-Host ""
    Write-Host "Estatisticas:" -ForegroundColor Cyan
    Write-Host "  Total de pastas: $($MainCollection.item.Count)" -ForegroundColor Green
    
    # Contar total de endpoints
    $TotalEndpoints = 0
    foreach ($Folder in $MainCollection.item) {
        $TotalEndpoints += $Folder.item.Count
    }
    Write-Host "  Total de endpoints: $TotalEndpoints" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "Para importar no Postman:" -ForegroundColor Cyan
    Write-Host "  1. Abra o Postman" -ForegroundColor White
    Write-Host "  2. File > Import" -ForegroundColor White
    Write-Host "  3. Selecione: GrowHub-Gateway.postman_collection.json" -ForegroundColor White
    Write-Host "  4. Sera importada UMA collection com $($MainCollection.item.Count) pastas" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Funcionalidades incluidas:" -ForegroundColor Cyan
    Write-Host "  - Autenticacao e Gerenciamento" -ForegroundColor Green
    Write-Host "  - Instancias e Providers" -ForegroundColor Green
    Write-Host "  - Mensagens e Eventos" -ForegroundColor Green
    Write-Host "  - Contatos, Grupos e Comunidades" -ForegroundColor Green
    Write-Host "  - Multiplos Webhooks por Instancia" -ForegroundColor Green
    Write-Host "  - Health & Monitoring" -ForegroundColor Green
    Write-Host ""
    
} catch {
    Write-Host "Erro ao salvar arquivo: $($_.Exception.Message)" -ForegroundColor Red
}