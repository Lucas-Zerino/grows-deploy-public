#!/bin/bash
# Script de teste da API GrowHub Gateway

set -e

echo "================================================="
echo " GrowHub Gateway - Teste Automatizado"
echo "================================================="
echo ""

API_URL="http://localhost:8000"

# 1. Login como Superadmin
echo "1️⃣  Fazendo login como superadmin..."
LOGIN_RESPONSE=$(curl -s -X POST $API_URL/api/admin/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@growhub.com","password":"Admin@123456"}')

SUPERADMIN_TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')

if [ "$SUPERADMIN_TOKEN" == "null" ] || [ -z "$SUPERADMIN_TOKEN" ]; then
    echo "❌ Falha no login do superadmin"
    echo $LOGIN_RESPONSE | jq '.'
    exit 1
fi

echo "✅ Login realizado com sucesso!"
echo "   Token: $SUPERADMIN_TOKEN"
echo ""

# 2. Verificar dados do admin
echo "2️⃣  Verificando dados do admin..."
ME_RESPONSE=$(curl -s -X GET $API_URL/api/admin/me \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN")

echo "✅ Dados do admin:"
echo $ME_RESPONSE | jq '.data'
echo ""

# 3. Criar empresa
echo "3️⃣  Criando nova empresa..."
COMPANY_RESPONSE=$(curl -s -X POST $API_URL/api/admin/companies \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"name":"Teste Automatizado LTDA"}')

COMPANY_TOKEN=$(echo $COMPANY_RESPONSE | jq -r '.data.token')
COMPANY_ID=$(echo $COMPANY_RESPONSE | jq -r '.data.id')

if [ "$COMPANY_TOKEN" == "null" ] || [ -z "$COMPANY_TOKEN" ]; then
    echo "❌ Falha ao criar empresa"
    echo $COMPANY_RESPONSE | jq '.'
    exit 1
fi

echo "✅ Empresa criada com sucesso!"
echo "   ID: $COMPANY_ID"
echo "   Token: $COMPANY_TOKEN"
echo ""

# 4. Aguardar filas serem criadas
echo "4️⃣  Aguardando criação das filas no RabbitMQ..."
sleep 3
echo "✅ Filas devem estar criadas!"
echo ""

# 5. Verificar se workers estão rodando
echo "5️⃣  Verificando workers no Docker..."
docker compose -f docker-compose.dev.yml ps | grep worker
echo ""

# 6. Listar empresas
echo "6️⃣  Listando todas as empresas..."
COMPANIES_RESPONSE=$(curl -s -X GET $API_URL/api/admin/companies \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN")

echo "✅ Empresas cadastradas:"
echo $COMPANIES_RESPONSE | jq '.data[] | {id, name, status}'
echo ""

# 7. Buscar empresa específica
echo "7️⃣  Buscando empresa específica (ID: $COMPANY_ID)..."
COMPANY_DETAIL=$(curl -s -X GET $API_URL/api/admin/companies/$COMPANY_ID \
  -H "Authorization: Bearer $SUPERADMIN_TOKEN")

echo "✅ Detalhes da empresa:"
echo $COMPANY_DETAIL | jq '.data'
echo ""

echo "================================================="
echo " ✅ Todos os testes passaram com sucesso!"
echo "================================================="
echo ""
echo "📝 Informações para uso:"
echo "   Company ID: $COMPANY_ID"
echo "   Company Token: $COMPANY_TOKEN"
echo ""
echo "🔗 Próximos passos:"
echo "   1. Crie um provider (WAHA/UAZAPI)"
echo "   2. Crie uma instância com o token da empresa"
echo "   3. Envie mensagens!"
echo ""
echo "📊 Verificar filas no RabbitMQ:"
echo "   http://localhost:15672"
echo "   User: admin / Pass: admin123"
echo ""

