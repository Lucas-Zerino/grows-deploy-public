#!/bin/bash

# Script para configurar o ambiente Docker de desenvolvimento

echo "============================================="
echo "  GrowHub Gateway - Docker Dev Setup"
echo "============================================="
echo ""

# Verificar se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker não está rodando. Inicie o Docker primeiro."
    exit 1
fi

# Criar arquivo .env se não existir
if [ ! -f .env ]; then
    echo "📝 Criando arquivo .env..."
    cp env.example .env
    echo "✓ Arquivo .env criado. Configure as credenciais se necessário."
else
    echo "✓ Arquivo .env já existe"
fi

# Parar containers existentes
echo ""
echo "🛑 Parando containers existentes..."
docker-compose -f docker-compose.dev.yml down

# Build das imagens
echo ""
echo "🔨 Construindo imagens Docker..."
docker-compose -f docker-compose.dev.yml build

# Subir containers
echo ""
echo "🚀 Iniciando containers..."
docker-compose -f docker-compose.dev.yml up -d

# Aguardar serviços ficarem prontos
echo ""
echo "⏳ Aguardando serviços ficarem prontos..."
sleep 15

# Verificar PostgreSQL
echo ""
echo "🗄️  Verificando PostgreSQL..."
until docker-compose -f docker-compose.dev.yml exec -T postgres pg_isready -U postgres > /dev/null 2>&1; do
    echo "   Aguardando PostgreSQL..."
    sleep 2
done
echo "✓ PostgreSQL está pronto"

# Verificar RabbitMQ
echo ""
echo "🐰 Verificando RabbitMQ..."
until docker-compose -f docker-compose.dev.yml exec -T rabbitmq rabbitmq-diagnostics ping > /dev/null 2>&1; do
    echo "   Aguardando RabbitMQ..."
    sleep 2
done
echo "✓ RabbitMQ está pronto"

# Configurar RabbitMQ (exchanges e queues globais)
echo ""
echo "⚙️  Configurando RabbitMQ..."
docker-compose -f docker-compose.dev.yml exec -T php-fpm php config/rabbitmq_setup.php

# Criar superadmin
echo ""
echo "👤 Criando superadmin..."
docker-compose -f docker-compose.dev.yml exec -T php-fpm php scripts/seed-superadmin.php

# Criar dados de teste (opcional)
echo ""
read -p "Deseja criar dados de teste (empresa + provider)? (s/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    echo "📊 Criando dados de teste..."
    docker-compose -f docker-compose.dev.yml exec -T php-fpm php scripts/create-test-data.php
fi

echo ""
echo "============================================="
echo "  ✅ Ambiente configurado com sucesso!"
echo "============================================="
echo ""
echo "🌐 Serviços disponíveis:"
echo "   API Gateway:     http://localhost:8000"
echo "   RabbitMQ Admin:  http://localhost:15672 (admin/admin123)"
echo "   PostgreSQL:      localhost:5432 (postgres/postgres)"
echo ""
echo "📋 Comandos úteis:"
echo "   Ver logs:              docker-compose -f docker-compose.dev.yml logs -f"
echo "   Ver logs de um serviço: docker-compose -f docker-compose.dev.yml logs -f php-fpm"
echo "   Parar tudo:            docker-compose -f docker-compose.dev.yml down"
echo "   Reiniciar:             docker-compose -f docker-compose.dev.yml restart"
echo "   Acessar shell PHP:     docker-compose -f docker-compose.dev.yml exec php-fpm sh"
echo ""
echo "🔐 Login Superadmin:"
echo "   Email: admin@growhub.com"
echo "   Senha: Admin@123456"
echo ""

