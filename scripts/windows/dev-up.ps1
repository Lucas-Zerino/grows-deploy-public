# Subir ambiente de desenvolvimento
Write-Host "🚀 Iniciando ambiente de desenvolvimento..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml up -d --build
Write-Host "" 
Write-Host "✅ Containers iniciados!" -ForegroundColor Green
Write-Host "📡 API disponível em: http://localhost:8000" -ForegroundColor Yellow
Write-Host "🐰 RabbitMQ: http://localhost:15672 (admin/admin123)" -ForegroundColor Yellow

