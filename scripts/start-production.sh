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
exec supervisord -n -c /etc/supervisor/conf.d/dentissa.conf
