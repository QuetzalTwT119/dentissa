param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string] $SqlDump,

    [string] $TargetDatabase
)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'mysql-backup-common.ps1')

$projectRoot = Split-Path -Parent $PSScriptRoot
$dumpPath = if ([System.IO.Path]::IsPathRooted($SqlDump)) {
    [System.IO.Path]::GetFullPath($SqlDump)
} else {
    [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $SqlDump))
}

if (-not (Test-Path -LiteralPath $dumpPath -PathType Leaf)) {
    throw "No se encontró el dump SQL restaurado: $dumpPath"
}

$restoreDatabase = if ([string]::IsNullOrWhiteSpace($TargetDatabase)) {
    "dentissa_restore_$(Get-Date -Format 'yyyyMMdd_HHmmss')_$([guid]::NewGuid().ToString('N').Substring(0, 6))"
} else {
    $TargetDatabase
}
if ($restoreDatabase -notmatch '^dentissa_restore_[A-Za-z0-9_]+$') {
    throw 'Por seguridad, la base destino debe comenzar con dentissa_restore_. La base activa nunca es un destino permitido.'
}

$targetCreated = $false
$sqlInputStream = $null

Push-Location $projectRoot
try {
    $sourceDatabase = Get-ComposeMysqlVariable -Name 'MYSQL_DATABASE'
    $mysqlUser = Get-ComposeMysqlVariable -Name 'MYSQL_USER'
    if ($sourceDatabase -notmatch '^[A-Za-z0-9_]+$' -or $mysqlUser -notmatch '^[A-Za-z0-9_]+$') {
        throw 'La configuración MYSQL_DATABASE o MYSQL_USER contiene caracteres no admitidos.'
    }

    $validationReader = [System.IO.StreamReader]::new($dumpPath, [System.Text.Encoding]::UTF8, $true)
    try {
        $firstLine = $validationReader.ReadLine()
        if ($firstLine -notmatch '^-- MySQL dump ') {
            throw 'El archivo no tiene la cabecera esperada de mysqldump.'
        }
        while ($null -ne ($line = $validationReader.ReadLine())) {
            if ($line -match '^\s*(USE\s+|CREATE\s+DATABASE|DROP\s+DATABASE)') {
                throw 'El dump intenta elegir o crear una base por su cuenta; se rechazó para evitar tocar la base activa.'
            }
        }
    } finally {
        $validationReader.Dispose()
    }

    $exists = Invoke-ComposeMysqlRootQuery -Query "SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$restoreDatabase'"
    if ($exists -ne '0') {
        throw "La base destino '$restoreDatabase' ya existe. Elige otro nombre; el script no la sobrescribe."
    }

    $activeUserCount = Invoke-ComposeMysqlRootQuery -Query "SELECT COUNT(*) FROM ``$sourceDatabase``.users"
    $createQuery = "CREATE DATABASE ``$restoreDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    [void] (Invoke-ComposeMysqlRootQuery -Query $createQuery)
    $targetCreated = $true

    $grantQuery = "GRANT ALL PRIVILEGES ON ``$restoreDatabase``.* TO '$mysqlUser'@'%'"
    [void] (Invoke-ComposeMysqlRootQuery -Query $grantQuery)

    $sqlInputStream = [System.IO.File]::OpenRead($dumpPath)
    try {
        Invoke-DockerCompose -StandardInputStream $sqlInputStream -Arguments @(
            'compose', 'exec', '-T', 'mysql', 'sh', '-lc',
            'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql --user="$MYSQL_USER" --default-character-set=utf8mb4 --database="$1"',
            'sh', $restoreDatabase
        )
    } finally {
        $sqlInputStream.Dispose()
        $sqlInputStream = $null
    }

    $restoredUserCount = Invoke-ComposeMysqlRootQuery -Query "SELECT COUNT(*) FROM ``$restoreDatabase``.users"
    Write-Output "Restauración Restic verificada en base aislada: $restoreDatabase"
    Write-Output "Usuarios en la base activa: $activeUserCount"
    Write-Output "Usuarios recuperados: $restoredUserCount"
    Write-Warning 'La base restaurada se conserva para inspección y no reemplaza la base activa.'
} catch {
    if ($targetCreated) {
        try {
            [void] (Invoke-ComposeMysqlRootQuery -Query "DROP DATABASE ``$restoreDatabase``")
            Write-Warning "Se eliminó la base aislada incompleta '$restoreDatabase' creada por este intento fallido."
        } catch {
            Write-Warning "No se pudo limpiar la base aislada incompleta '$restoreDatabase'. Revísala antes de volver a intentar."
        }
    }
    throw
} finally {
    if ($sqlInputStream) { $sqlInputStream.Dispose() }
    Pop-Location
}
