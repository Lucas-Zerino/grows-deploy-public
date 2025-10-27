# Ver logs da API em tempo real
Write-Host "📋 Logs da API (Ctrl+C para sair)..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml logs -f nginx php-fpm

