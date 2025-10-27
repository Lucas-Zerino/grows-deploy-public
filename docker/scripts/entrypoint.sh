#!/bin/sh
# Entrypoint para garantir que dependências estejam instaladas

set -e

echo "Verificando dependências do Composer..."

cd /var/www/html

# Verificar se vendor/autoload.php existe e está acessível
if [ ! -f "vendor/autoload.php" ]; then
    echo "Instalando dependências do Composer..."
    
    # Remover vendor se existir (pode estar corrompido)
    if [ -d "vendor" ]; then
        echo "Removendo vendor corrompido..."
        rm -rf vendor
    fi
    
    # Instalar dependências
    composer install --no-interaction --optimize-autoloader
    
    # Verificar se instalou corretamente
    if [ -f "vendor/autoload.php" ]; then
        echo "✓ Dependências instaladas com sucesso"
    else
        echo "❌ Erro ao instalar dependências"
        exit 1
    fi
else
    echo "✓ Dependências já instaladas"
fi

# Garantir permissões corretas
chmod -R 755 vendor 2>/dev/null || true

# Executar o comando original do container
exec "$@"

