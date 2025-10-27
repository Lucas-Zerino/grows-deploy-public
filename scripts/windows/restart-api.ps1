# Restart apenas da API
Write-Host "🔄 Reiniciando API..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml restart nginx php-fpm
Write-Host "✅ API reiniciada!" -ForegroundColor Green

