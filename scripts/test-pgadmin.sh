#!/bin/bash

# Script para testar se pgAdmin está funcionando
echo "🐘 Testando pgAdmin..."

# Verificar se o container está rodando
if docker ps --filter name=growhub_pgadmin_dev --format "{{.Names}}" | grep -q "growhub_pgadmin_dev"; then
    echo "✅ Container pgAdmin está rodando"
else
    echo "❌ Container pgAdmin não está rodando"
    echo "Execute: docker-compose -f docker-compose.dev.yml up -d"
    exit 1
fi

# Verificar se a porta está aberta
if curl -s http://localhost:8080 > /dev/null; then
    echo "✅ pgAdmin está acessível em http://localhost:8080"
    echo ""
    echo "🌐 Acesse: http://localhost:8080"
    echo "📧 Login: admin@growhub.com"
    echo "🔑 Senha: Admin@123456"
    echo ""
    echo "🔗 Conexão PostgreSQL:"
    echo "   Host: growhub_postgres_dev"
    echo "   Port: 5432"
    echo "   Database: growhub_gateway"
    echo "   Username: postgres"
    echo "   Password: postgres"
else
    echo "❌ pgAdmin não está acessível"
    echo "Verificando logs..."
    docker-compose -f docker-compose.dev.yml logs pgadmin
fi
