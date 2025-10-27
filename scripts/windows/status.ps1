# Ver status dos containers
Write-Host "📊 Status dos Containers:" -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml ps

