[CmdletBinding()]
param(
    [string]$Repositorio = 'E:\DentissaBackups\restic-repo',
    [string]$Destino = (Join-Path 'E:\DentissaBackups\restauraciones-prueba' (Get-Date -Format 'yyyyMMdd-HHmmss')),
    [string]$Snapshot = 'latest'
)

$ErrorActionPreference = 'Stop'
if (-not (Test-Path -LiteralPath (Join-Path $Repositorio 'config'))) { throw "No se encontró un repositorio Restic existente en: $Repositorio" }
if (Test-Path -LiteralPath $Destino) { throw "El destino de prueba ya existe y no se sobrescribirá: $Destino" }

$segura = Read-Host 'Escribe la contraseña del respaldo (no se mostrará)' -AsSecureString
$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($segura)
try { $env:RESTIC_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr) }
finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
if ([string]::IsNullOrWhiteSpace($env:RESTIC_PASSWORD)) { throw 'No se recibió una contraseña para el repositorio cifrado.' }

try {
    New-Item -ItemType Directory -Path $Destino -Force | Out-Null
    $argumentosDocker = @('run', '--rm', '--mount', "type=bind,source=$Repositorio,target=/repo", '--mount', "type=bind,source=$Destino,target=/restore", '-e', 'RESTIC_REPOSITORY=/repo', '-e', 'RESTIC_PASSWORD', 'restic/restic:0.17.3', 'restore', $Snapshot, '--target', '/restore')
    Write-Host 'Recuperando una copia únicamente en la carpeta de prueba...' -ForegroundColor Cyan
    & docker @argumentosDocker
    if ($LASTEXITCODE -ne 0) { throw 'La restauración de prueba falló.' }

    $manifest = Get-ChildItem -LiteralPath $Destino -Recurse -Filter MANIFEST.txt -File | Select-Object -First 1
    $dump = Get-ChildItem -LiteralPath $Destino -Recurse -Filter mysql-dentissa.sql -File | Select-Object -First 1
    if ($null -eq $manifest -or $null -eq $dump -or $dump.Length -eq 0) { throw 'La restauración no contiene el manifiesto o el dump de MySQL esperado.' }
    Write-Host "Restauración de prueba aprobada en: $Destino" -ForegroundColor Green
    Write-Host 'No se tocó ni se importó nada en la base de datos activa.' -ForegroundColor Green
}
finally { Remove-Item Env:\RESTIC_PASSWORD -ErrorAction SilentlyContinue }
