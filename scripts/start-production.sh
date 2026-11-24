#!/bin/sh
set -eu

cd /var/www/html

# Railway monta el volumen de storage/app como root al arrancar y tapa el
# chown que hizo el Dockerfile. Este script corre como root antes de soltar
# a www-data (supervisord): aquí se crean las carpetas y se ajusta el dueño
# para que la app pueda guardar imágenes clínicas y respaldos temporales.
mkdir -p storage/app/private storage/app/public storage/app/respaldos-tmp
chown -R www-data:www-data storage/app

php artisan migrate --force

# Festivos del año actual y el siguiente (no duplica). Si falla, la app arranca
# igual: el programador lo reintenta el día 1 de cada mes.
php artisan dentissa:bloquear-festivos || echo "Aviso: no se pudieron bloquear los festivos; se reintentará el día 1."

exec supervisord -n -c /etc/supervisor/conf.d/dentissa.conf
