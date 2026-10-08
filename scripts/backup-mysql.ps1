param(
    [string] $Destination = 'backups'
)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'mysql-backup-common.ps1')

$projectRoot = Split-Path -Parent $PSScriptRoot
$destinationPath = if ([System.IO.Path]::IsPathRooted($Destination)) {
    [System.IO.Path]::GetFullPath($Destination)
} else {
    [System.IO.Path]::GetFullPath((Join-Path $projectRoot $Destination))
}
$stagePath = Join-Path ([System.IO.Path]::GetTempPath()) "dentissa-mysql-backup-$([guid]::NewGuid().ToString('N'))"
$temporaryArchivePath = "$stagePath.zip"

Push-Location $projectRoot
try {
    $runningServices = Invoke-DockerCompose -Arguments @(
        'compose', 'ps', '--status', 'running', '--services'
    )
    if ($runningServices -notmatch '(^|\r?\n)mysql(\r?\n|$)') {
        throw 'El servicio mysql no está iniciado. Ejecuta docker compose up -d mysql y espera a que esté healthy.'
    }

    $database = Get-ComposeMysqlVariable -Name 'MYSQL_DATABASE'
    if ($database -notmatch '^[A-Za-z0-9_]+$') {
        throw 'MYSQL_DATABASE contiene caracteres no admitidos para este respaldo.'
    }

    New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $stagePath 'database') -Force | Out-Null
    $dumpPath = Join-Path $stagePath 'database/dentissa.sql'

    Invoke-DockerCompose -StandardOutputPath $dumpPath -Arguments @(
        'compose', 'exec', '-T', 'mysql', 'sh', '-lc',
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --user=root --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 --no-tablespaces "$MYSQL_DATABASE"'
    )

    $dumpReader = [System.IO.StreamReader]::new($dumpPath, [System.Text.Encoding]::UTF8, $true)
    try {
        $firstLine = $dumpReader.ReadLine()
    } finally {
        $dumpReader.Dispose()
    }
    if ($firstLine -notmatch '^-- MySQL dump ') {
        throw 'El archivo generado no tiene la cabecera esperada de mysqldump.'
    }

    $metadata = [ordered]@{
        format = 'dentissa-mysql-backup-v1'
        database = $database
        created_at = (Get-Date).ToString('o')
        includes_uploaded_files = $false
    }
    $metadataPath = Join-Path $stagePath 'metadata.json'
    $metadataJson = $metadata | ConvertTo-Json
    [System.IO.File]::WriteAllText($metadataPath, $metadataJson, [System.Text.UTF8Encoding]::new($false))

    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss-fff'
    $archivePath = Join-Path $destinationPath "dentissa-mysql-$stamp.zip"
    Compress-Archive -Path (Join-Path $stagePath '*') -DestinationPath $temporaryArchivePath -CompressionLevel Optimal
    Move-Item -LiteralPath $temporaryArchivePath -Destination $archivePath

    Write-Output "Respaldo MySQL creado: $archivePath"
    Write-Warning 'El ZIP contiene datos en texto SQL y no está cifrado. Protégelo y no lo subas a Git.'
    Write-Warning 'Este respaldo solo incluye MySQL; no incluye archivos subidos en storage/app.'
} finally {
    if (Test-Path -LiteralPath $stagePath) {
        Remove-Item -LiteralPath $stagePath -Recurse -Force
    }
    if (Test-Path -LiteralPath $temporaryArchivePath) {
        Remove-Item -LiteralPath $temporaryArchivePath -Force
    }
    Pop-Location
}
