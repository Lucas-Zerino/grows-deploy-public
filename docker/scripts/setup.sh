#!/bin/sh
# Script que roda automaticamente para configurar o ambiente

set -e

echo "========================================"
echo "  GrowHub Gateway - Auto Setup"
echo "========================================"
echo ""

# Aguardar PostgreSQL estar pronto (usando variáveis do .env)
echo "Aguardando PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "  Aguardando conexão com PostgreSQL..."
    sleep 2
done
echo "✓ PostgreSQL pronto"

# Aguardar RabbitMQ estar pronto
echo "Aguardando RabbitMQ..."
until wget -q --spider http://rabbitmq:15672 > /dev/null 2>&1; do
    echo "  Aguardando RabbitMQ..."
    sleep 2
done
echo "✓ RabbitMQ pronto"

# Aguardar mais um pouco para garantir
echo "Aguardando serviços estabilizarem..."
sleep 5

# Configurar RabbitMQ (exchanges e queues globais)
echo ""
echo "Configurando RabbitMQ..."
php /var/www/html/config/rabbitmq_setup.php
echo "✓ RabbitMQ configurado"

# Criar superadmin
echo ""
echo "Criando superadmin..."
php /var/www/html/scripts/seed-superadmin.php
echo "✓ Superadmin criado"

# Criar providers padrão
echo ""
echo "Criando providers padrão..."
php /var/www/html/scripts/seed-providers.php
echo "✓ Providers criados"

# Criar dados de teste (empresa + provider)
echo ""
echo "Criando dados de teste..."
php /var/www/html/scripts/create-test-data.php || echo "⚠ Dados de teste já existem ou script falhou"

echo ""
echo "========================================"
echo "  ✓ Setup concluído com sucesso!"
echo "========================================"
echo ""
echo "Ambiente pronto para uso:"
echo "  API:      http://localhost:8000"
echo "  RabbitMQ: http://localhost:15672"
echo ""
echo "Login Superadmin:"
echo "  Email: admin@growhub.com"
echo "  Senha: Admin@123456"
echo ""

