param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string] $Backup
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$backupPath = if ([System.IO.Path]::IsPathRooted($Backup)) {
    [System.IO.Path]::GetFullPath($Backup)
} else {
    [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Backup))
}
$databasePath = Join-Path $projectRoot 'database/database.sqlite'
$storageAppPath = Join-Path $projectRoot 'storage/app'
$wasRunning = $false
$stagePath = $null
$oldStoragePath = $null
$oldDatabasePath = $null
$storageSwapped = $false
$databaseSwapped = $false

if (-not (Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    throw "No se encontró el respaldo: $backupPath"
}

$stagePath = Join-Path ([System.IO.Path]::GetTempPath()) "dentissa-restore-$([guid]::NewGuid().ToString('N'))"
New-Item -ItemType Directory -Path $stagePath -Force | Out-Null

try {
    Expand-Archive -LiteralPath $backupPath -DestinationPath $stagePath
    $stagedDatabasePath = Join-Path $stagePath 'database/database.sqlite'
    $stagedStoragePath = Join-Path $stagePath 'storage/app'

    if (-not (Test-Path -LiteralPath $stagedDatabasePath -PathType Leaf)) {
        throw 'El ZIP no contiene database/database.sqlite; no se modificó la instalación.'
    }

    $headerBytes = New-Object byte[] 16
    $stream = [System.IO.File]::OpenRead($stagedDatabasePath)
    try {
        $bytesRead = $stream.Read($headerBytes, 0, $headerBytes.Length)
    } finally {
        $stream.Dispose()
    }
    $header = [System.Text.Encoding]::ASCII.GetString($headerBytes)
    if ($bytesRead -ne 16 -or $header -ne "SQLite format 3`0") {
        throw 'El archivo de base de datos del ZIP no tiene una cabecera SQLite válida; no se modificó la instalación.'
    }

    Write-Host "Se restaurará la base de datos y los archivos de storage/app desde: $backupPath"
    $confirmation = Read-Host 'Escribe RESTAURAR para continuar'
    if ($confirmation -cne 'RESTAURAR') {
        Write-Host 'Restauración cancelada; no se modificó la instalación.'
        return
    }

    Push-Location $projectRoot
    try {
        & (Join-Path $PSScriptRoot 'backup.ps1')
        if ($LASTEXITCODE -ne 0) { throw 'Falló la copia de seguridad preventiva.' }

        $wasRunning = (docker compose ps --status running --services) -contains 'app'
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar Docker Compose.' }
        if ($wasRunning) {
            docker compose stop app
            if ($LASTEXITCODE -ne 0) { throw 'No se pudo detener el servicio app.' }
        }

        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss-fff'
        $oldDatabasePath = Join-Path (Split-Path -Parent $databasePath) "database.sqlite.restore-$stamp"
        $oldStoragePath = Join-Path (Split-Path -Parent $storageAppPath) "app.restore-$stamp"

        if (Test-Path -LiteralPath $databasePath) {
            Move-Item -LiteralPath $databasePath -Destination $oldDatabasePath
            $databaseSwapped = $true
        }
        Move-Item -LiteralPath $stagedDatabasePath -Destination $databasePath

        if (Test-Path -LiteralPath $storageAppPath) {
            Move-Item -LiteralPath $storageAppPath -Destination $oldStoragePath
            $storageSwapped = $true
        }
        if (Test-Path -LiteralPath $stagedStoragePath -PathType Container) {
            Move-Item -LiteralPath $stagedStoragePath -Destination $storageAppPath
        } else {
            New-Item -ItemType Directory -Path $storageAppPath -Force | Out-Null
        }

        Write-Host 'Restauración completada. El servicio app volverá a migrar y arrancar.'
    } catch {
        if (Test-Path -LiteralPath $databasePath) { Remove-Item -LiteralPath $databasePath -Force }
        if ($databaseSwapped -and (Test-Path -LiteralPath $oldDatabasePath)) {
            Move-Item -LiteralPath $oldDatabasePath -Destination $databasePath
        }
        if (Test-Path -LiteralPath $storageAppPath) { Remove-Item -LiteralPath $storageAppPath -Recurse -Force }
        if ($storageSwapped -and (Test-Path -LiteralPath $oldStoragePath)) {
            Move-Item -LiteralPath $oldStoragePath -Destination $storageAppPath
        }
        throw
    } finally {
        if ($databaseSwapped -and (Test-Path -LiteralPath $oldDatabasePath)) {
            Remove-Item -LiteralPath $oldDatabasePath -Force -ErrorAction SilentlyContinue
        }
        if ($storageSwapped -and (Test-Path -LiteralPath $oldStoragePath)) {
            Remove-Item -LiteralPath $oldStoragePath -Recurse -Force -ErrorAction SilentlyContinue
        }
        if ($wasRunning) {
            docker compose start app
            if ($LASTEXITCODE -ne 0) {
                Write-Warning 'No se pudo reiniciar app. Ejecuta: docker compose up -d app'
            }
        }
        Pop-Location
    }
} finally {
    if ($stagePath -and (Test-Path -LiteralPath $stagePath)) {
        Remove-Item -LiteralPath $stagePath -Recurse -Force
    }
}
