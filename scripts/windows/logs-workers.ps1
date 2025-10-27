# Ver logs dos workers em tempo real
Write-Host "👷 Logs dos Workers (Ctrl+C para sair)..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml logs -f worker-outbound worker-inbound worker-outbox worker-health

