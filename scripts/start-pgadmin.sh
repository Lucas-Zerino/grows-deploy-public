#!/bin/bash

# Script para iniciar pgAdmin no Linux
echo "🐘 Iniciando pgAdmin..."

# Verificar se a rede existe
echo "Verificando rede growhub_network..."
if ! docker network ls --filter name=growhub_network --format "{{.Name}}" | grep -q "growhub_network"; then
    echo "❌ Rede growhub_network não encontrada!"
    echo "Criando rede..."
    docker network create growhub_network
    if [ $? -eq 0 ]; then
        echo "✅ Rede criada com sucesso!"
    else
        echo "❌ Erro ao criar rede!"
        exit 1
    fi
else
    echo "✅ Rede encontrada"
fi

# Verificar se PostgreSQL está rodando
echo "Verificando PostgreSQL..."
if ! docker ps --filter name=growhub_postgres --format "{{.Names}}" | grep -q "growhub_postgres"; then
    echo "⚠️  PostgreSQL não está rodando!"
    echo "Execute primeiro: docker-compose -f docker-compose.dev.yml up -d"
    echo "Ou: make dev-up"
    echo ""
    echo "Continuando mesmo assim... pgAdmin será iniciado mas não conseguirá conectar."
fi

# Iniciar pgAdmin
echo "Iniciando pgAdmin..."
docker-compose -f docker-compose.pgadmin.yml up -d

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ pgAdmin iniciado com sucesso!"
    echo ""
    echo "🌐 Acesse: http://localhost:8080"
    echo ""
    echo "📧 Login:"
    echo "   Email: admin@growhub.com"
    echo "   Senha: Admin@123456"
    echo ""
    echo "🔗 Conexão com PostgreSQL:"
    echo "   Host: growhub_postgres_dev"
    echo "   Port: 5432"
    echo "   Database: growhub_gateway"
    echo "   Username: postgres"
    echo "   Password: postgres"
    echo ""
    echo "📊 Para ver logs: docker-compose -f docker-compose.pgadmin.yml logs -f"
else
    echo "❌ Erro ao iniciar pgAdmin!"
fi
