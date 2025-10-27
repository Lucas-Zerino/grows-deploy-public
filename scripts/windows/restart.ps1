# Restart rápido de todos os containers
Write-Host "🔄 Reiniciando containers..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml restart
Write-Host "✅ Containers reiniciados!" -ForegroundColor Green

