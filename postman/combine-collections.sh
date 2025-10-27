#!/bin/bash

# Combinar Collections do Postman - Script Shell
echo "========================================"
echo "  Combinando Collections do Postman"
echo "========================================"
echo ""

# Obter diretório do script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COLLECTIONS_PATH="$SCRIPT_DIR/collections"
OUTPUT_FILE="$SCRIPT_DIR/GrowHub-Gateway.postman_collection.json"

# Verificar se o diretório collections existe
if [ ! -d "$COLLECTIONS_PATH" ]; then
    echo "Erro: Diretório collections não encontrado em $COLLECTIONS_PATH"
    exit 1
fi

# Criar arquivo JSON principal
cat > "$OUTPUT_FILE" << 'EOF'
{
  "info": {
    "_postman_id": "growhub-gateway-complete-2025",
    "name": "GrowHub Gateway API",
    "description": "Gateway de mensagens para WhatsApp (WAHA/UAZAPI). Collection organizada em pastas por funcionalidade.",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
EOF

# Contador para controlar vírgulas
count=0
total_files=$(find "$COLLECTIONS_PATH" -name "*.json" -not -name "README.md" | wc -l)

# Processar cada arquivo JSON
for file in "$COLLECTIONS_PATH"/*.json; do
    # Pular README.md se existir
    if [[ "$(basename "$file")" == "README.md" ]]; then
        continue
    fi
    
    echo "Lendo: $(basename "$file")"
    
    # Verificar se o arquivo existe e é válido
    if [ ! -f "$file" ]; then
        echo "  - Arquivo não encontrado: $file"
        continue
    fi
    
    # Verificar se é um JSON válido
    if ! jq empty "$file" 2>/dev/null; then
        echo "  - Erro: JSON inválido em $(basename "$file")"
        continue
    fi
    
    # Extrair informações do JSON
    collection_name=$(jq -r '.info.name' "$file")
    collection_items=$(jq '.item' "$file")
    
    if [ "$collection_name" = "null" ] || [ -z "$collection_name" ]; then
        echo "  - Erro: Nome da collection não encontrado em $(basename "$file")"
        continue
    fi
    
    # Adicionar vírgula se não for o primeiro item
    if [ $count -gt 0 ]; then
        echo "," >> "$OUTPUT_FILE"
    fi
    
    # Adicionar folder à collection principal
    cat >> "$OUTPUT_FILE" << EOF
    {
      "name": "$collection_name",
      "item": $collection_items
    }
EOF
    
    echo "  - Adicionado: $collection_name"
    ((count++))
done

# Fechar o JSON
cat >> "$OUTPUT_FILE" << 'EOF'
  ]
}
EOF

# Verificar se jq está instalado
if ! command -v jq &> /dev/null; then
    echo ""
    echo "AVISO: jq não está instalado. Instale com:"
    echo "  Ubuntu/Debian: sudo apt-get install jq"
    echo "  macOS: brew install jq"
    echo "  Windows: choco install jq"
    echo ""
    echo "O arquivo foi criado, mas pode não estar formatado corretamente."
    exit 1
fi

# Validar o JSON final
if jq empty "$OUTPUT_FILE" 2>/dev/null; then
    echo ""
    echo "Arquivo combinado criado com sucesso!"
    echo ""
    echo "Arquivo:"
    echo "  $OUTPUT_FILE"
    echo ""
    echo "Total de pastas: $count"
    echo ""
    echo "Para importar no Postman:"
    echo "  1. Abra o Postman"
    echo "  2. File > Import"
    echo "  3. Selecione: GrowHub-Gateway.postman_collection.json"
    echo "  4. Será importada UMA collection com múltiplas pastas"
    echo ""
else
    echo "Erro: Arquivo JSON final é inválido"
    exit 1
fi