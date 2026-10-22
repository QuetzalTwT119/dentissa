#!/usr/bin/env bash
set -euo pipefail

# Respaldo cifrado de Dentissa desde Ubuntu/WSL.
# Úsalo cuando Docker Desktop no pueda montar directamente la unidad E: desde PowerShell.

PROJECT_DIR="${PROJECT_DIR:-/mnt/c/laragon/www/dentissa}"
REPOSITORY="${RESTIC_REPOSITORY_PATH:-/mnt/e/DentissaBackups/restic-repo}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
STAGING="${PROJECT_DIR}/.backup-staging/dentissa-${TIMESTAMP}"

die() {
    echo "Error: $*" >&2
    exit 1
}

[[ -d "$(dirname "${REPOSITORY}")" ]] || die "No se encontró la carpeta del disco externo para el repositorio: $(dirname "${REPOSITORY}")"
[[ -f "${PROJECT_DIR}/compose.yaml" ]] || die "No se encontró el proyecto en: ${PROJECT_DIR}"
command -v docker >/dev/null || die "Docker no está disponible en Ubuntu/WSL."

read -r -s -p "Escribe la contraseña del respaldo (no se mostrará): " RESTIC_PASSWORD
echo
[[ -n "${RESTIC_PASSWORD}" ]] || die "No se recibió una contraseña para el repositorio cifrado."
export RESTIC_PASSWORD

cleanup() {
    unset RESTIC_PASSWORD
    [[ -d "${STAGING}" ]] && rm -rf -- "${STAGING}"
}
trap cleanup EXIT

run_restic() {
    docker run --rm \
        --mount "type=bind,source=${REPOSITORY},target=/repo" \
        --mount "type=bind,source=${STAGING},target=/source,readonly" \
        -e RESTIC_REPOSITORY=/repo \
        -e RESTIC_PASSWORD \
        restic/restic:0.17.3 "$@"
}

echo "Verificando acceso al repositorio cifrado..."
mkdir -p -- "${REPOSITORY}"
mkdir -p -- "${STAGING}"
if [[ ! -f "${REPOSITORY}/config" ]]; then
    echo "Inicializando un repositorio cifrado nuevo..."
    run_restic init
fi
run_restic snapshots

echo "Exportando MySQL de forma consistente..."
mkdir -p -- "${STAGING}/storage-app"
(
    cd "${PROJECT_DIR}"
    docker compose exec -T mysql sh -c 'exec mysqldump --single-transaction --routines --events --triggers -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
) > "${STAGING}/mysql-dentissa.sql"
[[ -s "${STAGING}/mysql-dentissa.sql" ]] || die "La exportación de MySQL falló o quedó vacía."

if [[ -d "${PROJECT_DIR}/storage/app" ]]; then
    cp -a "${PROJECT_DIR}/storage/app/." "${STAGING}/storage-app/"
fi

SQL_HASH="$(sha256sum "${STAGING}/mysql-dentissa.sql" | awk '{print $1}')"
cat > "${STAGING}/MANIFEST.txt" <<EOF
creado_en=$(date --iso-8601=seconds)
base_de_datos=mysql
archivo_sql=mysql-dentissa.sql
sha256_sql=${SQL_HASH}
archivos=storage/app
EOF

echo "Cifrando MySQL y archivos en el disco externo..."
run_restic backup /source --tag dentissa --tag mysql-storage --tag local

echo "Comprobando integridad del repositorio..."
run_restic check --read-data-subset=1/10

echo "Respaldo terminado y verificado. No se eliminó ninguna copia anterior."
