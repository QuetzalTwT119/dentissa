param(
    [string]$Descripcion = "antes-rediseno-admin"
)

$ErrorActionPreference = 'Stop'

function Invoke-Git {
    & git @args
    if ($LASTEXITCODE -ne 0) {
        throw "Falló el comando git: git $($args -join ' ')"
    }
}

$repoRoot = (& git rev-parse --show-toplevel).Trim()
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo identificar la raíz del repositorio.'
}
$currentPath = [System.IO.Path]::GetFullPath((Get-Location).Path).Replace('/', '\').TrimEnd('\')
$repoRoot = [System.IO.Path]::GetFullPath($repoRoot).Replace('/', '\').TrimEnd('\')
if (-not [string]::Equals($currentPath, $repoRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Ejecuta este script desde la raíz del repositorio: $repoRoot"
}

$paths = @(
    'resources/views',
    'resources/css',
    'resources/js',
    'tailwind.config.js',
    'vite.config.js'
) | Where-Object { Test-Path -LiteralPath $_ }

$scriptPath = 'scripts/checkpoint-diseno.ps1'
$status = @(& git status --short -- @paths $scriptPath)
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo consultar el estado de Git.'
}

if ($status.Count -eq 0) {
    Write-Host 'No hay cambios en vistas ni recursos visuales; no se creó un checkpoint.'
    exit 0
}

Write-Host "Se guardarán únicamente estos cambios visuales y el script de checkpoint:`n"
$status | ForEach-Object { Write-Host "  $_" }
Write-Host "`nNo se incluirán backend, base de datos, documentación ni archivos de salida."
$confirmation = Read-Host '¿Crear una rama y un commit local con estos cambios? (S/N)'
if ($confirmation -notin @('S', 's', 'SI', 'Sí', 'si', 'sí')) {
    Write-Host 'Cancelado; no se modificó el estado de Git.'
    exit 0
}

$safeDescription = ($Descripcion.ToLowerInvariant() -replace '[^a-z0-9-]+', '-') -replace '(^-+|-+$)', ''
if ([string]::IsNullOrWhiteSpace($safeDescription)) {
    throw 'La descripción debe incluir letras o números.'
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$branchName = "codex/checkpoint-$safeDescription-$timestamp"
$commitMessage = "checkpoint: $safeDescription ($timestamp)"

Invoke-Git switch -c $branchName
Invoke-Git add -A -- @paths
Invoke-Git add -- $scriptPath
Invoke-Git commit --only -m $commitMessage -- @paths $scriptPath

Write-Host "`nCheckpoint creado correctamente."
Write-Host "Rama: $branchName"
Write-Host "Commit: $((& git rev-parse --short HEAD).Trim())"
Write-Host 'Es local; no se subió a GitHub.'
