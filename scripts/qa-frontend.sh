#!/bin/sh
set -eu

echo "==> Validando vistas Blade"
php artisan view:clear
php artisan view:cache

echo "==> Ejecutando pruebas de portada, recursos y adaptación responsiva"
php artisan test --compact --filter='PortadaPublicaTest|NavegacionResponsivaTest|AsistenteVistasPublicasTest|ImagenesPublicasTest|PwaArchivosPublicosTest'

echo "==> QA de frontend aprobado"
