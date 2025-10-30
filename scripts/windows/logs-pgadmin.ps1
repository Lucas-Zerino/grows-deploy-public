# Script para ver logs do pgAdmin
Write-Host "📋 Logs do pgAdmin (Ctrl+C para sair)..." -ForegroundColor Cyan
docker-compose -f docker-compose.pgadmin.yml logs -f
