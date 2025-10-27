# Ver logs de webhooks em tempo real
Write-Host "🔔 Monitorando webhooks (Ctrl+C para sair)..." -ForegroundColor Cyan
Get-Content logs/app-*.log -Wait -Tail 50 | Select-String "webhook"

