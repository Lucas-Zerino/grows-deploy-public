# Teste Completo do GrowHub Gateway

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  GrowHub - Teste Completo" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$companyToken = "35bbeffd-ace4-44b2-a770-545d1adbc66b"

# ========================================
# 1. CRIAR INSTANCIA (token da empresa)
# ========================================
Write-Host "1. Criando instancia com TOKEN DA EMPRESA..." -ForegroundColor Yellow

$instance = Invoke-RestMethod -Method Post `
    -Uri "http://localhost:8000/api/instances" `
    -Headers @{Authorization="Bearer $companyToken"} `
    -ContentType "application/json" `
    -Body '{"instance_name":"vendas_nova","phone_number":"5511999999999","webhook_url":"https://meuapp.com/webhook"}'

Write-Host "   OK! Instancia criada" -ForegroundColor Green
Write-Host "   ID: $($instance.data.id)" -ForegroundColor Gray
Write-Host "   Nome: $($instance.data.instance_name)" -ForegroundColor Gray
Write-Host "   Token Instancia: $($instance.data.token)" -ForegroundColor Green
Write-Host ""

$instanceToken = $instance.data.token
$instanceId = $instance.data.id

# ========================================
# 2. LISTAR INSTANCIAS (token da empresa)
# ========================================
Write-Host "2. Listando instancias com TOKEN DA EMPRESA..." -ForegroundColor Yellow

$instances = Invoke-RestMethod -Method Get `
    -Uri "http://localhost:8000/api/instances" `
    -Headers @{Authorization="Bearer $companyToken"}

Write-Host "   OK! Total: $($instances.data.Count) instancias" -ForegroundColor Green
$instances.data | ForEach-Object {
    Write-Host "     - ID: $($_.id) | Nome: $($_.instance_name) | Status: $($_.status)" -ForegroundColor Gray
}
Write-Host ""

# ========================================
# 3. TESTAR STATUS (token da instancia)
# ========================================
Write-Host "3. Verificando status com TOKEN DA INSTANCIA..." -ForegroundColor Yellow

try {
    $status = Invoke-RestMethod -Method Get `
        -Uri "http://localhost:8000/instance/status" `
        -Headers @{Authorization="Bearer $instanceToken"}
    
    Write-Host "   OK! Status obtido" -ForegroundColor Green
    Write-Host "   ID: $($status.id)" -ForegroundColor Gray
    Write-Host "   Nome: $($status.name)" -ForegroundColor Gray
} catch {
    Write-Host "   Erro: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# ========================================
# 4. TESTAR CONNECT (token da instancia)
# ========================================
Write-Host "4. Conectando instancia com TOKEN DA INSTANCIA..." -ForegroundColor Yellow

try {
    $connect = Invoke-RestMethod -Method Post `
        -Uri "http://localhost:8000/instance/connect" `
        -Headers @{Authorization="Bearer $instanceToken"} `
        -ContentType "application/json" `
        -Body '{}'
    
    Write-Host "   OK! Conexao iniciada" -ForegroundColor Green
} catch {
    $errorMsg = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "   Resposta: $($errorMsg.message -or $errorMsg.error)" -ForegroundColor Yellow
}
Write-Host ""

# ========================================
# 5. TESTAR DELETE (token da empresa)
# ========================================
Write-Host "5. Deletando instancia com TOKEN DA EMPRESA..." -ForegroundColor Yellow

try {
    $delete = Invoke-RestMethod -Method Delete `
        -Uri "http://localhost:8000/api/instances/$instanceId" `
        -Headers @{Authorization="Bearer $companyToken"}
    
    Write-Host "   OK! Instancia deletada" -ForegroundColor Green
} catch {
    Write-Host "   Erro: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# ========================================
# 6. VALIDAR TOKEN ERRADO
# ========================================
Write-Host "6. Testando validacoes de token..." -ForegroundColor Yellow

# Placeholder
try {
    Invoke-RestMethod -Method Get `
        -Uri "http://localhost:8000/api/instances" `
        -Headers @{Authorization="Bearer {{token}}"}
} catch {
    $errorMsg = $_.ErrorDetails.Message | ConvertFrom-Json
    if ($errorMsg.message -like "*placeholder*") {
        Write-Host "   OK! Placeholder detectado" -ForegroundColor Green
    }
}

# Token vazio
try {
    Invoke-RestMethod -Method Get `
        -Uri "http://localhost:8000/api/instances" `
        -Headers @{Authorization="Bearer "}
} catch {
    Write-Host "   OK! Token vazio rejeitado" -ForegroundColor Green
}

# Token invalido
try {
    Invoke-RestMethod -Method Get `
        -Uri "http://localhost:8000/api/instances" `
        -Headers @{Authorization="Bearer token-invalido-123"}
} catch {
    Write-Host "   OK! Token invalido rejeitado" -ForegroundColor Green
}

Write-Host ""

# ========================================
# RESUMO
# ========================================
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Testes Concluidos!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tokens:" -ForegroundColor White
Write-Host "  Empresa: $companyToken" -ForegroundColor Gray
Write-Host "  Instancia: $instanceToken" -ForegroundColor Gray
Write-Host ""

