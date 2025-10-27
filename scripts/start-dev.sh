#!/bin/bash

# Script para iniciar o ambiente de desenvolvimento

echo "🚀 Starting GrowHub Gateway (Development Mode)"
echo ""

# Verificar se o Docker está rodando
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Subir serviços Docker
echo "📦 Starting PostgreSQL and RabbitMQ..."
docker-compose -f docker-compose.dev.yml up -d

# Aguardar serviços ficarem prontos
echo "⏳ Waiting for services to be ready..."
sleep 10

# Verificar se PostgreSQL está pronto
until docker-compose -f docker-compose.dev.yml exec -T postgres pg_isready -U postgres > /dev/null 2>&1; do
    echo "   Waiting for PostgreSQL..."
    sleep 2
done
echo "✓ PostgreSQL is ready"

# Verificar se RabbitMQ está pronto
until docker-compose -f docker-compose.dev.yml exec -T rabbitmq rabbitmq-diagnostics ping > /dev/null 2>&1; do
    echo "   Waiting for RabbitMQ..."
    sleep 2
done
echo "✓ RabbitMQ is ready"

# Instalar dependências se necessário
if [ ! -d "vendor" ]; then
    echo "📚 Installing Composer dependencies..."
    composer install
fi

# Aplicar schema do banco se necessário
echo "🗄️  Setting up database..."
docker-compose -f docker-compose.dev.yml exec -T postgres psql -U postgres -d growhub_gateway -c "SELECT 1 FROM companies LIMIT 1" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "   Applying database schema..."
    docker-compose -f docker-compose.dev.yml exec -T postgres psql -U postgres -d growhub_gateway < database/schema.sql
    echo "✓ Database schema applied"
else
    echo "✓ Database already initialized"
fi

# Configurar RabbitMQ
echo "🐰 Setting up RabbitMQ topology..."
php config/rabbitmq_setup.php

# Criar superadmin
echo "👤 Creating superadmin..."
php scripts/seed-superadmin.php

echo ""
echo "✅ Environment is ready!"
echo ""
echo "📋 Next steps:"
echo "   1. Start the API server:"
echo "      php -S localhost:8000 -t public"
echo ""
echo "   2. Start the workers (in separate terminals):"
echo "      php workers/message_sender_worker.php"
echo "      php workers/event_processor_worker.php"
echo "      php workers/outbox_processor_worker.php"
echo "      php workers/health_check_worker.php"
echo ""
echo "🌐 Services:"
echo "   API: http://localhost:8000"
echo "   RabbitMQ Management: http://localhost:15672"
echo "   PostgreSQL: localhost:5432"
echo ""

