param(
    [Parameter(Mandatory = $true)]
    [string] $Repository,

    [string] $ResticExe
)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'mysql-backup-common.ps1')

function Resolve-ResticExecutable {
    param([string] $RequestedPath)

    if (-not [string]::IsNullOrWhiteSpace($RequestedPath)) {
        $resolvedPath = [System.IO.Path]::GetFullPath($RequestedPath)
        if (-not (Test-Path -LiteralPath $resolvedPath -PathType Leaf)) {
            throw "No se encontró el ejecutable de Restic: $resolvedPath"
        }

        return $resolvedPath
    }

    $command = Get-Command restic -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Path
    }

    $wingetPackagePath = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\restic.restic_Microsoft.Winget.Source_8wekyb3d8bbwe'
    if (Test-Path -LiteralPath $wingetPackagePath -PathType Container) {
        $installedExecutables = @(Get-ChildItem -LiteralPath $wingetPackagePath -Filter 'restic_*.exe' -File)
        if ($installedExecutables.Count -gt 0) {
            return ($installedExecutables | Sort-Object LastWriteTime -Descending | Select-Object -First 1).FullName
        }
    }

    throw 'No se encontró Restic. Instálalo o proporciona la ruta con -ResticExe.'
}

function Invoke-Restic {
    param([Parameter(Mandatory = $true)][string[]] $Arguments)

    & $script:resticExecutable --repo $script:repositoryPath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Restic falló con código $LASTEXITCODE. No se debe considerar completa esta copia."
    }
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$repositoryPath = [System.IO.Path]::GetFullPath($Repository)
$storageAppPath = Join-Path $projectRoot 'storage/app'
$resticExecutable = Resolve-ResticExecutable -RequestedPath $ResticExe
$runTag = "run-$(Get-Date -Format 'yyyyMMdd-HHmmss-fff')"
$mysqlSnapshotCreated = $false

if (-not (Test-Path -LiteralPath $repositoryPath -PathType Container)) {
    throw "No existe el repositorio Restic: $repositoryPath. Inicialízalo primero con restic init."
}

if (-not (Test-Path -LiteralPath $storageAppPath -PathType Container)) {
    throw "No existe la carpeta que se debe proteger: $storageAppPath"
}

Push-Location $projectRoot
try {
    $runningServices = Invoke-DockerCompose -Arguments @(
        'compose', 'ps', '--status', 'running', '--services'
    )
    if ($runningServices -notmatch '(^|\r?\n)mysql(\r?\n|$)') {
        throw 'El servicio mysql no está iniciado. Ejecuta docker compose up -d mysql y espera a que esté saludable.'
    }

    $database = Get-ComposeMysqlVariable -Name 'MYSQL_DATABASE'
    if ($database -notmatch '^[A-Za-z0-9_]+$') {
        throw 'MYSQL_DATABASE contiene caracteres no admitidos para este respaldo.'
    }

    $dumpCommand = 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --user=root --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 --no-tablespaces "$MYSQL_DATABASE"'

    Write-Output "Guardando dump MySQL cifrado en Restic (etiqueta $runTag)..."
    Invoke-Restic -Arguments @(
        'backup',
        '--stdin-from-command',
        '--stdin-filename', "database/$database.sql",
        '--tag', 'dentissa',
        '--tag', 'mysql',
        '--tag', $runTag,
        '--', 'docker', 'compose', 'exec', '-T', 'mysql', 'sh', '-lc', $dumpCommand
    )
    $mysqlSnapshotCreated = $true

    Write-Output "Guardando storage/app cifrado en Restic (etiqueta $runTag)..."
    Invoke-Restic -Arguments @(
        'backup',
        '--tag', 'dentissa',
        '--tag', 'storage',
        '--tag', $runTag,
        $storageAppPath
    )

    Write-Output "Respaldo completo. Verifica los dos snapshots con: restic snapshots --tag $runTag"
} catch {
    if ($mysqlSnapshotCreated) {
        throw "El snapshot MySQL de $runTag se creó, pero el conjunto de respaldo quedó incompleto porque falló storage/app. Error: $($_.Exception.Message)"
    }

    throw
} finally {
    Pop-Location
}
