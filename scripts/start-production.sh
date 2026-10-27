#!/bin/sh
set -eu

cd /var/www/html
php artisan migrate --force
exec supervisord -n -c /etc/supervisor/conf.d/dentissa.conf
