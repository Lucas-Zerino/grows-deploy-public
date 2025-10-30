# Script para parar pgAdmin
Write-Host "🛑 Parando pgAdmin..." -ForegroundColor Cyan

docker-compose -f docker-compose.pgadmin.yml down

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ pgAdmin parado com sucesso!" -ForegroundColor Green
} else {
    Write-Host "❌ Erro ao parar pgAdmin!" -ForegroundColor Red
}
