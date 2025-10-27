# Script para verificar fluxo completo de webhooks
param(
    [Parameter(Mandatory=$true)]
    [string]$InstanceId,
    
    [Parameter(Mandatory=$false)]
    [int]$CompanyId = 2
)

Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   🔔 VERIFICAÇÃO COMPLETA DE WEBHOOKS                ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar se instância tem webhook_url configurado
Write-Host "📋 Passo 1: Verificando configuração da instância..." -ForegroundColor Yellow
Write-Host ""

$query = "SELECT id, instance_name, webhook_url, status FROM instances WHERE id = $InstanceId"
Write-Host "Execute no banco de dados:" -ForegroundColor Gray
Write-Host "  docker-compose -f docker-compose.dev.yml exec postgres psql -U postgres -d growhub_gateway -c `"$query`"" -ForegroundColor White
Write-Host ""

# 2. Enviar webhook de teste
Write-Host "📡 Passo 2: Enviando webhook de teste..." -ForegroundColor Yellow
$webhookUrl = "http://localhost:8000/webhook/waha/$InstanceId"
$testPayload = @{
    event = "message"
    payload = @{
        id = "test-$(Get-Random)"
        from = "5511999999999@c.us"
        body = "Teste de webhook - $(Get-Date -Format 'HH:mm:ss')"
        timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
        fromMe = $false
    }
} | ConvertTo-Json -Depth 10

try {
    $response = Invoke-RestMethod -Uri $webhookUrl -Method Post -Body $testPayload -ContentType "application/json"
    Write-Host "  ✅ Webhook recebido pelo backend!" -ForegroundColor Green
    Write-Host ""
} catch {
    Write-Host "  ❌ Erro ao enviar webhook!" -ForegroundColor Red
    Write-Host "  $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# 3. Verificar fila RabbitMQ
Write-Host "🐰 Passo 3: Verificando fila RabbitMQ..." -ForegroundColor Yellow
Write-Host ""
Write-Host "  Acesse: http://localhost:15672" -ForegroundColor White
Write-Host "  Login: admin / admin123" -ForegroundColor Gray
Write-Host "  Procure fila: company.$CompanyId.inbound" -ForegroundColor Gray
Write-Host ""
Write-Host "  Ou via CLI:" -ForegroundColor Gray
Write-Host "  docker-compose -f docker-compose.dev.yml exec rabbitmq rabbitmqctl list_queues name messages" -ForegroundColor White
Write-Host ""

# 4. Verificar worker
Write-Host "👷 Passo 4: Verificando worker de eventos..." -ForegroundColor Yellow
Write-Host ""

$workerStatus = docker-compose -f docker-compose.dev.yml ps worker-inbound 2>$null

if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Worker está rodando" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Ver logs do worker:" -ForegroundColor Gray
    Write-Host "  docker-compose -f docker-compose.dev.yml logs -f worker-inbound" -ForegroundColor White
} else {
    Write-Host "  ❌ Worker NÃO está rodando!" -ForegroundColor Red
    Write-Host ""
    Write-Host "  Inicie o worker:" -ForegroundColor Yellow
    Write-Host "  docker-compose -f docker-compose.dev.yml up -d worker-inbound" -ForegroundColor White
}

Write-Host ""

# 5. Resumo
Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   📊 RESUMO DO FLUXO                                 ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. WAHA envia → http://localhost:8000/webhook/waha/$InstanceId" -ForegroundColor White
Write-Host "2. Backend processa → Coloca na fila RabbitMQ (company.$CompanyId.inbound)" -ForegroundColor White
Write-Host "3. Worker consome → Lê webhook_url da instância" -ForegroundColor White
Write-Host "4. Worker envia → POST para webhook_url do cliente" -ForegroundColor White
Write-Host ""
Write-Host "💡 Para funcionar, a instância DEVE ter webhook_url configurado!" -ForegroundColor Yellow
Write-Host ""

