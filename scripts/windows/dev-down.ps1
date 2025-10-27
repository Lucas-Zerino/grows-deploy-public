# Parar ambiente de desenvolvimento
Write-Host "⏹️  Parando containers..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml down
Write-Host "✅ Containers parados!" -ForegroundColor Green

