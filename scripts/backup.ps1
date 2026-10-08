param(
    [string] $Destination = "backups"
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$destinationPath = if ([System.IO.Path]::IsPathRooted($Destination)) {
    [System.IO.Path]::GetFullPath($Destination)
} else {
    [System.IO.Path]::GetFullPath((Join-Path $projectRoot $Destination))
}
$databasePath = Join-Path $projectRoot 'database/database.sqlite'
$storageAppPath = Join-Path $projectRoot 'storage/app'
$wasRunning = $false
$stagePath = $null

Push-Location $projectRoot
try {
    if (-not (Test-Path -LiteralPath $databasePath -PathType Leaf)) {
        throw "No existe la base SQLite: $databasePath"
    }

    New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
    $wasRunning = (docker compose ps --status running --services) -contains 'app'
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo consultar Docker Compose. Confirma que Docker Desktop esté iniciado.'
    }

    if ($wasRunning) {
        docker compose stop app
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo detener el servicio app para copiar datos consistentes.' }
    }

    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss-fff'
    $backupPath = Join-Path $destinationPath "dentissa-$stamp.zip"
    $stagePath = Join-Path ([System.IO.Path]::GetTempPath()) "dentissa-backup-$([guid]::NewGuid().ToString('N'))"
    New-Item -ItemType Directory -Path (Join-Path $stagePath 'database') -Force | Out-Null
    Copy-Item -LiteralPath $databasePath -Destination (Join-Path $stagePath 'database/database.sqlite')

    if (Test-Path -LiteralPath $storageAppPath -PathType Container) {
        New-Item -ItemType Directory -Path (Join-Path $stagePath 'storage') -Force | Out-Null
        Copy-Item -LiteralPath $storageAppPath -Destination (Join-Path $stagePath 'storage') -Recurse
    }

    Compress-Archive -Path (Join-Path $stagePath '*') -DestinationPath $backupPath
    Write-Output "Respaldo creado: $backupPath"
} finally {
    if ($stagePath -and (Test-Path -LiteralPath $stagePath)) {
        Remove-Item -LiteralPath $stagePath -Recurse -Force
    }
    if ($wasRunning) {
        docker compose start app
        if ($LASTEXITCODE -ne 0) {
            Write-Warning 'El respaldo terminó, pero el servicio app no pudo reiniciarse. Ejecuta: docker compose up -d app'
        }
    }
    Pop-Location
}
