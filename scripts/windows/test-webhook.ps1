# Testar se webhooks estão chegando no backend
param(
    [Parameter(Mandatory=$true)]
    [string]$InstanceId
)

Write-Host "🧪 Testando webhook para instância ID: $InstanceId" -ForegroundColor Cyan
Write-Host ""

$webhookUrl = "http://localhost:8000/webhook/waha/$InstanceId"
$testPayload = @{
    event = "message"
    payload = @{
        id = "test-msg-123"
        from = "5511999999999@c.us"
        body = "Mensagem de teste"
        timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
        fromMe = $false
    }
} | ConvertTo-Json -Depth 10

Write-Host "📡 Enviando webhook de teste..." -ForegroundColor Yellow
Write-Host "URL: $webhookUrl" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri $webhookUrl -Method Post -Body $testPayload -ContentType "application/json"
    
    Write-Host "✅ Webhook recebido com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Resposta:" -ForegroundColor Cyan
    $response | ConvertTo-Json -Depth 5
    Write-Host ""
    Write-Host "💡 Agora verifique:" -ForegroundColor Yellow
    Write-Host "  1. Logs da API: .\scripts\windows\logs-api.ps1" -ForegroundColor Gray
    Write-Host "  2. RabbitMQ: http://localhost:15672" -ForegroundColor Gray
    Write-Host "     - Fila: company.2.inbound" -ForegroundColor Gray
    
} catch {
    Write-Host "❌ Erro ao enviar webhook!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

Write-Host ""

