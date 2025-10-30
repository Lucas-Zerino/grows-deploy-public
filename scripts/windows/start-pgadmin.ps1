# Script para iniciar pgAdmin
Write-Host "🐘 Iniciando pgAdmin..." -ForegroundColor Cyan

# Verificar se a rede existe
Write-Host "Verificando rede growhub_network..." -ForegroundColor Yellow
$networkExists = docker network ls --filter name=growhub_network --format "{{.Name}}" | Select-String "growhub_network"

if (-not $networkExists) {
    Write-Host "❌ Rede growhub_network não encontrada!" -ForegroundColor Red
    Write-Host "Criando rede..." -ForegroundColor Yellow
    docker network create growhub_network
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Rede criada com sucesso!" -ForegroundColor Green
    } else {
        Write-Host "❌ Erro ao criar rede!" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✅ Rede encontrada" -ForegroundColor Green
}

# Verificar se PostgreSQL está rodando
Write-Host "Verificando PostgreSQL..." -ForegroundColor Yellow
$postgresRunning = docker ps --filter name=growhub_postgres --format "{{.Names}}" | Select-String "growhub_postgres"

if (-not $postgresRunning) {
    Write-Host "⚠️  PostgreSQL não está rodando!" -ForegroundColor Yellow
    Write-Host "Execute primeiro: docker-compose -f docker-compose.dev.yml up -d" -ForegroundColor Yellow
    Write-Host "Ou: make dev-up" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Continuando mesmo assim... pgAdmin será iniciado mas não conseguirá conectar." -ForegroundColor Gray
}

# Iniciar pgAdmin
Write-Host "Iniciando pgAdmin..." -ForegroundColor Yellow
docker-compose -f docker-compose.pgadmin.yml up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ pgAdmin iniciado com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🌐 Acesse: http://localhost:8080" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📧 Login:" -ForegroundColor Yellow
    Write-Host "   Email: admin@growhub.com" -ForegroundColor White
    Write-Host "   Senha: Admin@123456" -ForegroundColor White
    Write-Host ""
    Write-Host "🔗 Conexão com PostgreSQL:" -ForegroundColor Yellow
    Write-Host "   Host: growhub_postgres_dev" -ForegroundColor White
    Write-Host "   Port: 5432" -ForegroundColor White
    Write-Host "   Database: growhub_gateway" -ForegroundColor White
    Write-Host "   Username: postgres" -ForegroundColor White
    Write-Host "   Password: postgres" -ForegroundColor White
    Write-Host ""
    Write-Host "📊 Para ver logs: docker-compose -f docker-compose.pgadmin.yml logs -f" -ForegroundColor Gray
} else {
    Write-Host "❌ Erro ao iniciar pgAdmin!" -ForegroundColor Red
}
