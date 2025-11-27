# Script de Backup SQL - PowerShell
# Este script faz backup do banco de dados usando mysqldump

param(
    [string]$dbHost = "localhost",
    [string]$dbUser = "admin",
    [string]$dbPass = "@#8520@#",
    [string]$dbName = "it_inventory",
    [string]$backupDir = "C:\xampp\htdocs\sistema4\backups"
)

# Criar diretorio se nao existir
if (!(Test-Path -Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-Host "Diretorio criado: $backupDir"
}

# Gerar nome do arquivo com timestamp
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$backupFile = "$backupDir\backup_$timestamp.sql"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "INICIANDO BACKUP DO BANCO DE DADOS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Banco: $dbName" -ForegroundColor Yellow
Write-Host "Arquivo: $backupFile" -ForegroundColor Yellow

try {
    # Executar mysqldump
    $mysqlDumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
    
    if (!(Test-Path $mysqlDumpPath)) {
        Write-Host "ERRO: mysqldump nao encontrado" -ForegroundColor Red
        exit 1
    }
    
    # Executar comando de backup
    & $mysqlDumpPath -h $dbHost -u $dbUser "-p$dbPass" $dbName | Out-File -FilePath $backupFile -Encoding UTF8
    
    # Verificar se backup foi criado
    if (Test-Path $backupFile) {
        $fileSize = (Get-Item $backupFile).Length
        $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
        
        Write-Host "Sucesso! Backup realizado." -ForegroundColor Green
        Write-Host "Arquivo: $(Split-Path $backupFile -Leaf)" -ForegroundColor Green
        Write-Host "Tamanho: $fileSizeMB MB" -ForegroundColor Green
        exit 0
    } else {
        Write-Host "ERRO: Arquivo nao criado" -ForegroundColor Red
        exit 1
    }
    
} catch {
    Write-Host "ERRO: Falha no backup" -ForegroundColor Red
    exit 1
}
