# Acessar shell do container PHP
Write-Host "🐚 Acessando container PHP..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml exec php-fpm sh

