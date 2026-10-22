[CmdletBinding()]
param(
    [string]$Repositorio = 'E:\DentissaBackups\restic-repo',
    [switch]$SoloVerificar
)

$ErrorActionPreference = 'Stop'
$proyecto = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$marcaTiempo = Get-Date -Format 'yyyyMMdd-HHmmss'

function Get-ContrasenaRestic {
    $segura = Read-Host 'Escribe la contraseña del respaldo (no se mostrará)' -AsSecureString
    $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($segura)
    try { $texto = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
    if ([string]::IsNullOrWhiteSpace($texto)) { throw 'No se recibió una contraseña para el repositorio cifrado.' }
    return $texto
}

function Invoke-Restic {
    param([string[]]$Argumentos, [string]$CarpetaOrigen)
    $argumentosDocker = @('run', '--rm', '--mount', "type=bind,source=$Repositorio,target=/repo", '-e', 'RESTIC_REPOSITORY=/repo', '-e', 'RESTIC_PASSWORD')
    if ($CarpetaOrigen) { $argumentosDocker += @('--mount', "type=bind,source=$CarpetaOrigen,target=/source") }
    $argumentosDocker += @('restic/restic:0.17.3') + $Argumentos
    & docker @argumentosDocker
    if ($LASTEXITCODE -ne 0) { throw "Restic no pudo completar: restic $($Argumentos -join ' ')" }
}

if (-not (Test-Path -LiteralPath (Join-Path $Repositorio 'config'))) { throw "No se encontró un repositorio Restic existente en: $Repositorio" }
$contrasena = Get-ContrasenaRestic
$env:RESTIC_PASSWORD = $contrasena
$contrasena = $null

try {
    Write-Host 'Verificando acceso al repositorio cifrado...' -ForegroundColor Cyan
    Invoke-Restic -Argumentos @('snapshots')
    if ($SoloVerificar) {
        Write-Host 'El repositorio es accesible y la contraseña es correcta.' -ForegroundColor Green
        exit 0
    }

    $contenedorMysql = (& docker compose ps -q mysql).Trim()
    if ([string]::IsNullOrWhiteSpace($contenedorMysql)) { throw 'MySQL no está en ejecución. Inicia Docker con: docker compose up -d mysql' }

    $staging = Join-Path (Join-Path (Split-Path -Parent $Repositorio) '.staging') "dentissa-$marcaTiempo"
    New-Item -ItemType Directory -Path $staging -Force | Out-Null
    try {
        $archivoSql = Join-Path $staging 'mysql-dentissa.sql'
        Write-Host 'Exportando MySQL de forma consistente...' -ForegroundColor Cyan
        $comandoDump = 'exec mysqldump --single-transaction --routines --events --triggers -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
        & docker compose exec -T mysql sh -c $comandoDump > $archivoSql
        if ($LASTEXITCODE -ne 0 -or (Get-Item -LiteralPath $archivoSql).Length -eq 0) { throw 'La exportación de MySQL falló o quedó vacía.' }

        $origenArchivos = Join-Path $proyecto 'storage\app'
        $destinoArchivos = Join-Path $staging 'storage-app'
        New-Item -ItemType Directory -Path $destinoArchivos -Force | Out-Null
        Get-ChildItem -LiteralPath $origenArchivos -Force | Copy-Item -Destination $destinoArchivos -Recurse -Force

        $hashSql = (Get-FileHash -LiteralPath $archivoSql -Algorithm SHA256).Hash
        @("creado_en=$(Get-Date -Format o)", 'base_de_datos=mysql', 'archivo_sql=mysql-dentissa.sql', "sha256_sql=$hashSql", 'archivos=storage/app') | Set-Content -LiteralPath (Join-Path $staging 'MANIFEST.txt') -Encoding utf8NoBOM

        Write-Host 'Cifrando MySQL y archivos en el disco externo...' -ForegroundColor Cyan
        Invoke-Restic -CarpetaOrigen $staging -Argumentos @('backup', '/source', '--tag', 'dentissa', '--tag', 'mysql-storage', '--tag', 'local')
        Write-Host 'Comprobando integridad del repositorio...' -ForegroundColor Cyan
        Invoke-Restic -Argumentos @('check', '--read-data-subset=1/10')
        Write-Host 'Respaldo terminado y verificado. No se eliminó ninguna copia anterior.' -ForegroundColor Green
    }
    finally {
        if (Test-Path -LiteralPath $staging) { Remove-Item -LiteralPath $staging -Recurse -Force }
    }
}
finally { Remove-Item Env:\RESTIC_PASSWORD -ErrorAction SilentlyContinue }
