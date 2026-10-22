[CmdletBinding()]
param(
    # Sólo ejecuta la revisión; no reconstruye la aplicación.
    [switch]$ValidarSolo
)

$ErrorActionPreference = 'Stop'
$proyecto = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$archivosRequeridos = @('resources\views\welcome.blade.php') + (1..9 | ForEach-Object { "public\images\$_.jpg" })
$faltantes = $archivosRequeridos | Where-Object { -not (Test-Path -LiteralPath (Join-Path $proyecto $_)) }

if ($faltantes.Count -gt 0) {
    throw "Faltan archivos requeridos para la portada:`n$($faltantes -join "`n")"
}

Write-Host 'Antigravity y Dentissa comparten esta carpeta:' -ForegroundColor Cyan
Write-Host $proyecto -ForegroundColor Cyan
Write-Host 'Validando la entrega visual en Docker...' -ForegroundColor Cyan

& docker compose --profile qa run --rm --build qa
if ($LASTEXITCODE -ne 0) {
    throw 'La revisión QA no aprobó los cambios. La aplicación no se reconstruyó.'
}

if ($ValidarSolo) {
    Write-Host 'QA aprobado. No se reconstruyó la aplicación porque usaste -ValidarSolo.' -ForegroundColor Green
    exit 0
}

Write-Host 'QA aprobado. Reconstruyendo la aplicación local...' -ForegroundColor Cyan
& docker compose up -d --build app
if ($LASTEXITCODE -ne 0) {
    throw 'La aplicación no pudo reconstruirse.'
}

Write-Host 'Listo: revisa http://localhost:8000' -ForegroundColor Green
