# Gerar collection Postman combinada
Write-Host "📮 Gerando collection Postman combinada..." -ForegroundColor Cyan

$currentDir = Get-Location
Set-Location "$PSScriptRoot\..\..\postman"

& .\combine-collections.ps1

Set-Location $currentDir

