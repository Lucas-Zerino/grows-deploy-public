# Restart apenas do RabbitMQ
Write-Host "🔄 Reiniciando RabbitMQ..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml restart rabbitmq
Write-Host "✅ RabbitMQ reiniciado!" -ForegroundColor Green

