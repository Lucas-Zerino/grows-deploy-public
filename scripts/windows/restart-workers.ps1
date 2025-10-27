# Restart apenas dos workers
Write-Host "🔄 Reiniciando workers..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml restart worker-outbound worker-inbound worker-outbox worker-health
Write-Host "✅ Workers reiniciados!" -ForegroundColor Green

