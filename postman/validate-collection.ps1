# Validar Collection do Postman
# Verifica se o arquivo JSON está válido e completo

param(
    [string]$FilePath = "GrowHub-Gateway.postman_collection.json"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Validando Collection do Postman" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$FullPath = Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) $FilePath

if (-not (Test-Path $FullPath)) {
    Write-Host "ERRO: Arquivo nao encontrado: $FullPath" -ForegroundColor Red
    exit 1
}

try {
    Write-Host "Lendo arquivo: $FilePath" -ForegroundColor Yellow
    
    # Ler arquivo
    $JsonContent = Get-Content -Path $FullPath -Raw -Encoding UTF8
    
    # Validar se não está vazio
    if ([string]::IsNullOrWhiteSpace($JsonContent)) {
        Write-Host "ERRO: Arquivo vazio!" -ForegroundColor Red
        exit 1
    }
    
    # Converter JSON
    $Collection = $JsonContent | ConvertFrom-Json
    
    # Validar estrutura básica
    if (-not $Collection.info) {
        Write-Host "ERRO: Campo 'info' nao encontrado!" -ForegroundColor Red
        exit 1
    }
    
    if (-not $Collection.item) {
        Write-Host "ERRO: Campo 'item' nao encontrado!" -ForegroundColor Red
        exit 1
    }
    
    if ($Collection.item.Count -eq 0) {
        Write-Host "ERRO: Array 'item' esta vazio!" -ForegroundColor Red
        exit 1
    }
    
    # Contar endpoints
    $TotalEndpoints = 0
    foreach ($Folder in $Collection.item) {
        if ($Folder.item) {
            $TotalEndpoints += $Folder.item.Count
        }
    }
    
    # Estatísticas
    $FileSize = (Get-Item $FullPath).Length
    $FileSizeKB = [math]::Round($FileSize / 1KB, 2)
    
    Write-Host ""
    Write-Host "VALIDACAO CONCLUIDA COM SUCESSO!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Informacoes da Collection:" -ForegroundColor Cyan
    Write-Host "  Nome: $($Collection.info.name)" -ForegroundColor White
    Write-Host "  Descricao: $($Collection.info.description)" -ForegroundColor White
    Write-Host "  Schema: $($Collection.info.schema)" -ForegroundColor White
    Write-Host "  ID: $($Collection.info._postman_id)" -ForegroundColor White
    Write-Host ""
    Write-Host "Estatisticas:" -ForegroundColor Cyan
    Write-Host "  Total de pastas: $($Collection.item.Count)" -ForegroundColor Green
    Write-Host "  Total de endpoints: $TotalEndpoints" -ForegroundColor Green
    Write-Host "  Tamanho do arquivo: $FileSizeKB KB" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "Pastas encontradas:" -ForegroundColor Cyan
    foreach ($Folder in $Collection.item) {
        $EndpointCount = if ($Folder.item) { $Folder.item.Count } else { 0 }
        Write-Host "  - $($Folder.name) ($EndpointCount endpoints)" -ForegroundColor White
    }
    
    Write-Host ""
    Write-Host "ARQUIVO VALIDO E PRONTO PARA IMPORTAR NO POSTMAN!" -ForegroundColor Green
    Write-Host ""
    
} catch {
    Write-Host "ERRO na validacao: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Detalhes: $($_.Exception.ToString())" -ForegroundColor DarkRed
    exit 1
}
