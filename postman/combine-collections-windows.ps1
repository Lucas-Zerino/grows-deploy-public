# Combinar Collections do Postman - Versão Windows Otimizada
# Suporte completo a caracteres especiais e validação robusta

param(
    [switch]$Verbose,
    [switch]$Force
)

# Configuração de encoding para suporte a UTF-8
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Combinando Collections do Postman" -ForegroundColor Cyan
Write-Host "  GrowHub Gateway API v2.0" -ForegroundColor Cyan
Write-Host "  Versao Windows Otimizada" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$CollectionsPath = Join-Path $ScriptDir "collections"
$OutputFile = Join-Path $ScriptDir "GrowHub-Gateway.postman_collection.json"

# Verificar se a pasta collections existe
if (-not (Test-Path $CollectionsPath)) {
    Write-Host "ERRO: Pasta collections nao encontrada: $CollectionsPath" -ForegroundColor Red
    exit 1
}

# Verificar se já existe arquivo de saída
if ((Test-Path $OutputFile) -and -not $Force) {
    Write-Host "Arquivo de saida ja existe: $OutputFile" -ForegroundColor Yellow
    Write-Host "Use -Force para sobrescrever ou delete o arquivo manualmente" -ForegroundColor Yellow
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
$JsonFiles = Get-ChildItem -Path $CollectionsPath -Filter "*.json" | 
    Where-Object { $_.Name -ne "README.md" -and $_.Name -notlike "README-*" } | 
    Sort-Object Name

Write-Host "Encontrados $($JsonFiles.Count) arquivos de colecao:" -ForegroundColor Yellow
Write-Host ""

$SuccessCount = 0
$ErrorCount = 0
$TotalEndpoints = 0

foreach ($File in $JsonFiles) {
    if ($Verbose) {
        Write-Host "Lendo: $($File.Name)" -ForegroundColor Yellow
    }
    
    try {
        # Ler arquivo com encoding UTF-8
        $JsonContent = Get-Content -Path $File.FullName -Raw -Encoding UTF8
        
        # Validar se não está vazio
        if ([string]::IsNullOrWhiteSpace($JsonContent)) {
            throw "Arquivo vazio ou invalido"
        }
        
        # Converter JSON
        $JsonObject = $JsonContent | ConvertFrom-Json
        
        # Validar estrutura básica
        if (-not $JsonObject.info -or -not $JsonObject.item) {
            throw "Estrutura JSON invalida - falta 'info' ou 'item'"
        }
        
        # Validar se tem nome
        if ([string]::IsNullOrWhiteSpace($JsonObject.info.name)) {
            throw "Nome da colecao nao definido"
        }
        
        # Criar folder
        $Folder = @{
            name = $JsonObject.info.name
            item = $JsonObject.item
        }
        
        # Adicionar à collection principal
        $MainCollection.item += $Folder
        
        $SuccessCount++
        $TotalEndpoints += $JsonObject.item.Count
        
        if ($Verbose) {
            Write-Host "  [OK] Adicionado: $($JsonObject.info.name) ($($JsonObject.item.Count) endpoints)" -ForegroundColor Green
        }
        
    } catch {
        $ErrorCount++
        Write-Host "  [ERRO] Falha ao processar $($File.Name): $($_.Exception.Message)" -ForegroundColor Red
        
        if ($Verbose) {
            Write-Host "    Detalhes: $($_.Exception.ToString())" -ForegroundColor DarkRed
        }
    }
}

Write-Host ""
Write-Host "Resumo do processamento:" -ForegroundColor Cyan
Write-Host "  [OK] Sucessos: $SuccessCount" -ForegroundColor Green
Write-Host "  [ERRO] Erros: $ErrorCount" -ForegroundColor Red
Write-Host ""

if ($ErrorCount -gt 0) {
    Write-Host "ATENCAO: $ErrorCount arquivo(s) com erro foram ignorados!" -ForegroundColor Yellow
    Write-Host ""
}

# Converter para JSON e salvar
try {
    Write-Host "Gerando arquivo combinado..." -ForegroundColor Yellow
    
    # Converter com profundidade adequada e formatação correta
    $JsonOutput = $MainCollection | ConvertTo-Json -Depth 20 -Compress:$false
    
    # Salvar com encoding UTF-8
    $JsonOutput | Out-File -FilePath $OutputFile -Encoding UTF8 -NoNewline
    
    Write-Host ""
    Write-Host "Arquivo combinado criado com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Arquivo gerado:" -ForegroundColor Cyan
    Write-Host "  $OutputFile" -ForegroundColor White
    Write-Host ""
    Write-Host "Estatisticas:" -ForegroundColor Cyan
    Write-Host "  Total de pastas: $($MainCollection.item.Count)" -ForegroundColor Green
    Write-Host "  Total de endpoints: $TotalEndpoints" -ForegroundColor Green
    Write-Host ""
    
    # Verificar tamanho do arquivo
    $FileSize = (Get-Item $OutputFile).Length
    $FileSizeKB = [math]::Round($FileSize / 1KB, 2)
    Write-Host "  Tamanho do arquivo: $FileSizeKB KB" -ForegroundColor Green
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
    
    Write-Host "Comandos disponiveis:" -ForegroundColor Cyan
    Write-Host "  .\combine-collections-windows.ps1 -Verbose    # Modo detalhado" -ForegroundColor White
    Write-Host "  .\combine-collections-windows.ps1 -Force      # Sobrescrever arquivo" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host "ERRO ao salvar arquivo: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Detalhes: $($_.Exception.ToString())" -ForegroundColor DarkRed
    exit 1
}

Write-Host "Processo concluido com sucesso!" -ForegroundColor Green