# Script PowerShell para testar os endpoints separados por integração
# Uso: .\test-integration-endpoints.ps1 -Integration <UAZAPI|WAHA> -Token <TOKEN> -BaseUrl <URL>

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("UAZAPI", "WAHA")]
    [string]$Integration,
    
    [Parameter(Mandatory=$true)]
    [string]$Token,
    
    [string]$BaseUrl = "http://localhost:8001"
)

Write-Host "=== Teste de Endpoints de Instância ===" -ForegroundColor Green
Write-Host "Integração: $Integration" -ForegroundColor Yellow
Write-Host "Base URL: $BaseUrl" -ForegroundColor Yellow
Write-Host "Token: $($Token.Substring(0, 8))..." -ForegroundColor Yellow
Write-Host ""

# Definir prefixo baseado na integração
$prefix = if ($Integration -eq "UAZAPI") { "/uazapi/instance" } else { "/waha/instance" }

# Headers comuns
$headers = @{
    "Authorization" = "Bearer $Token"
    "Content-Type" = "application/json"
}

function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [string]$Body = $null,
        [string]$Description
    )
    
    Write-Host "$Description..." -ForegroundColor Cyan
    
    try {
        $params = @{
            Uri = "$BaseUrl$prefix$Endpoint"
            Method = $Method
            Headers = $headers
        }
        
        if ($Body) {
            $params.Body = $Body
        }
        
        $response = Invoke-RestMethod @params
        $response | ConvertTo-Json -Depth 10 | Write-Host
    }
    catch {
        Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
}

# Testes
Test-Endpoint -Method "GET" -Endpoint "/status" -Description "1. Testando GET $prefix/status"

Test-Endpoint -Method "POST" -Endpoint "/connect" -Body '{}' -Description "2. Testando POST $prefix/connect (sem telefone)"

Test-Endpoint -Method "POST" -Endpoint "/connect" -Body '{"phone":"5511999999999"}' -Description "3. Testando POST $prefix/connect (com telefone)"

Test-Endpoint -Method "GET" -Endpoint "/qrcode" -Description "4. Testando GET $prefix/qrcode"

Test-Endpoint -Method "PUT" -Endpoint "/name" -Body '{"name":"Instância Teste"}' -Description "5. Testando PUT $prefix/name"

Test-Endpoint -Method "GET" -Endpoint "/privacy" -Description "6. Testando GET $prefix/privacy"

Test-Endpoint -Method "PUT" -Endpoint "/privacy" -Body '{"readreceipts":"all","groups":"all"}' -Description "7. Testando PUT $prefix/privacy"

Test-Endpoint -Method "PUT" -Endpoint "/presence" -Body '{"status":"available","message":"Online"}' -Description "8. Testando PUT $prefix/presence"

Test-Endpoint -Method "POST" -Endpoint "/disconnect" -Description "9. Testando POST $prefix/disconnect"

Test-Endpoint -Method "DELETE" -Endpoint "" -Description "10. Testando DELETE $prefix"

Write-Host "=== Teste Concluído ===" -ForegroundColor Green
