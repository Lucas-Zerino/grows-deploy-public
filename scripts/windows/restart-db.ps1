# Restart apenas do PostgreSQL
Write-Host "🔄 Reiniciando banco de dados..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml restart postgres
Write-Host "✅ Banco de dados reiniciado!" -ForegroundColor Green

