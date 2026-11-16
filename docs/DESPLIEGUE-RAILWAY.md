# Despliegue escolar en Railway

**Destino:** Railway, para una demostración escolar breve con datos ficticios.
**URL pública:** https://dentissa-production-11db.up.railway.app/
**Estado:** el 10 de noviembre de 2026 `/up` respondió 200 y `/version` reportó
el commit `e7f08ab` (merge del PR #29). El chat de la portada respondió en
producción después de corregir su token CSRF. La configuración interna de Railway no se
ha inspeccionado desde este repositorio; confirma variables, respaldos y
almacenamiento persistente desde el panel antes de usar datos reales.

Railway será el alojamiento de la demostración, no una autorización para usar
Dentissa con pacientes reales. El objetivo de esta etapa es separar el
contenedor local del constructor de producción, documentar la configuración y
validar que la aplicación sigue funcionando en Docker.

## Costos y alcance de la demostración

El equipo decidió Railway para la presentación. Antes de activar servicios hay
que verificar el precio vigente y el límite de consumo en el panel. La cuota
mínima mensual no se prorratea por usar el servicio solo un día. Un límite de
consumo puede apagar los servicios al alcanzarlo, así que se debe probar la
demo con anticipación y conservar una alternativa local.

La demostración usará únicamente datos ficticios: nada de pacientes,
diagnósticos ni archivos clínicos reales. Ollama, WhatsApp y Telegram no son
dependencias del recorrido de presentación; permanecerán desactivados hasta
configurarse y probarse por separado.

## Diferencia entre el entorno local y producción

`Dockerfile.local` se usa con `compose.yaml` para desarrollo y QA:

- instala dependencias de desarrollo por defecto (`INSTALL_DEV=true`);
- arranca con `php artisan serve` en el puerto local `8000`;
- ejecuta `php artisan migrate --force` cada vez que inicia el contenedor;
- depende del `.env` local montado por Compose.

El repositorio conserva un `Dockerfile` de producción separado y
`Dockerfile.local` para el entorno de desarrollo. En el panel inspeccionado el
27/10/2026, el servicio Railway usa el builder Dockerfile y no tiene comando de
inicio personalizado; el `compose.yaml` local no se despliega como una unidad.
El Dockerfile productivo inicia `scripts/start-production.sh`, que aplica las
migraciones y ejecuta Supervisor para mantener juntos el servidor web y el
proceso `php artisan dentissa:telegram`. Por eso, el token del bot debe existir
como variable privada `DENTISSA_TELEGRAM_BOT_TOKEN` en el servicio `dentissa`.
Supervisor también ejecuta `php artisan schedule:work`, que manda los
recordatorios de citas cada 15 minutos; no hace falta configurar un cron en
Railway.
Nunca se incorpora al repositorio ni al comando de inicio. No se crea un segundo
servicio para este prototipo.

El builder Dockerfile compila Vite y Composer desde el archivo raíz. El servidor
web usa el puerto `PORT` provisto por Railway. Si cambia el builder o se añade
un comando personalizado en Railway, volver a verificar que el arranque
mantenga ambos procesos activos y que no se exponga el token.

### Correo opcional mediante Gmail API

El transporte `gmail` puede usar archivos privados locales o variables privadas
del servicio. Para habilitarlo temporalmente en el prototipo de Railway, agrega
en Variables del servicio `dentissa`:

- `MAIL_MAILER=gmail`
- `MAIL_FROM_ADDRESS` con la misma cuenta de Google que autorizó Gmail API
- `MAIL_FROM_NAME=Dentissa`
- `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET` y
  `GMAIL_OAUTH_REFRESH_TOKEN`, copiados de forma privada desde la credencial
  OAuth de escritorio y el token de autorización local.

No agregues los JSON ni los valores secretos al repositorio, al chat, al comando
de inicio o a los logs. Mantén el emisor como usuario de prueba de Google OAuth.
Mientras la audiencia externa siga en estado de prueba y solicite `gmail.send`,
Google puede caducar la autorización (incluido el refresh token) a los siete
días; habrá que volver a autorizarla. Por eso esta opción sirve para validar una
demo breve, pero no es un correo estable para producción. Para una operación
continua, conserva `MAIL_MAILER=resend` (o completa la publicación/verificación
OAuth que corresponda antes de depender de Gmail).

Después de guardar las variables privadas, Railway debe reconstruir o reiniciar
el servicio para leerlas. Verifica el envío desde recuperación de contraseña y
comprueba en el buzón que llegó el mensaje; nunca uses los logs para inspeccionar
secretos.

## Checklist local previo a configurar Railway

- [x] Validar que `docker compose config` acepte los servicios y resuelva
  `Dockerfile.local`.
- [x] Reconstruir la app local y verificar `/up` y `/agendar-cita`.
- [x] Ejecutar la suite automatizada completa.
- [x] Revisar que `.env`, tokens y datos locales sigan excluidos de Git.
- [x] Confirmar que los seeders y el guion de demostración solo usen datos
  ficticios.

El seeder general no se debe ejecutar en Railway: actualmente contiene las
cuentas locales `admin@dentissa.com` y `asistente@dentissa.com` con la
contraseña fija `password`. Para preparar las cuentas y catálogo demo existe
`DemoPresentacionSeeder`, que está desactivado por defecto y exige correos y
contraseñas privadas distintos (20–72 bytes). Crea solo un admin, un asistente,
el catálogo ficticio y un paciente demo sin historial clínico.

## Configuración de los servicios al llegar al panel

Antes de generar un dominio público:

- Crear el servicio web y el servicio MySQL únicamente después de confirmar el
  costo con el equipo.
- Mantener MySQL en la red privada del proyecto; usar las variables del servicio
  MySQL, no habilitar un proxy TCP público para la aplicación.
- Configurar en el servicio web `DB_CONNECTION=mysql` y mapear `DB_HOST`,
  `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` a las variables del
  servicio MySQL (`MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`,
  `MYSQLPASSWORD`). Los valores reales deben guardarse solo en el panel de
  variables de Railway, nunca en Git.
- Antes de recibir pacientes reales, configurar `DENTISSA_PRIVACIDAD_RESPONSABLE`,
  `DENTISSA_PRIVACIDAD_DOMICILIO` y `DENTISSA_PRIVACIDAD_CORREO` con los datos
  de la clínica. Poner `DENTISSA_AVISO_REVISADO=true` solo cuando un abogado o
  la clínica hayan aprobado `/aviso-de-privacidad` (Manual Técnico, sección 23).
- Generar una `APP_KEY` exclusiva de producción. Configurar `APP_ENV=production`,
  `APP_DEBUG=false` y `APP_URL` con el dominio definitivo; revisar logs para que
  no incluyan datos personales o clínicos.
- Ejecutar `php artisan migrate --force` como comando pre-deploy controlado,
  después de revisar migraciones. No ejecutar el seeder general ni un `db:seed`
  sin clase en producción.
- Para la demo completa, configurar temporalmente en Variables
  `DENTISSA_DEMO_SEED_ENABLED=true`, correos distintos en
  `DENTISSA_DEMO_ADMIN_EMAIL` y `DENTISSA_DEMO_ASISTENTE_EMAIL`, y contraseñas
  aleatorias distintas de 20–72 bytes en sus variables `*_PASSWORD`. Ejecutar
  manualmente una vez desde la Shell del servicio:

  ```bash
  php artisan db:seed --class=DemoPresentacionSeeder --force
  ```

  Después cambiar el indicador a `false` y quitar las variables de contraseña.
  No copiar credenciales al README, al chat ni a Git. El catálogo que inserta usa
  los tratamientos y duraciones reales de la clínica, sin precios. Si se vuelve a
  ejecutar sobre una base existente, desactiva (no borra) el catálogo de ejemplo
  anterior y no toca lo creado desde el panel.
- Configurar `/up` como healthcheck: Laravel ya registra esa ruta en
  `bootstrap/app.php`. Railway usa el healthcheck para validar la nueva versión
  durante el despliegue; no sustituye un monitor continuo.
- **Montar un volumen de Railway en `/var/www/html/storage/app`.** Sin él, las
  imágenes clínicas y las fotos de perfil se borran en cada despliegue.
- Respaldos: configurar `DENTISSA_RESPALDO_CLAVE` (genérala con
  `php artisan dentissa:respaldo-llave` y guárdala también en un gestor de
  contraseñas), volver a autorizar Google con `scripts/google-oauth.ps1` para
  agregar el permiso de Drive y actualizar `GMAIL_OAUTH_REFRESH_TOKEN`. Después
  ejecutar `php artisan dentissa:respaldar` y `php artisan dentissa:restaurar-prueba`
  (Manual Técnico, sección 14.1). El scheduler lo repite cada día a las 03:00. Las fotos de perfil también se guardan de forma privada en
  `storage/app/private/profile-photos`; no exponerlas mediante el disco público.
- Dejar deshabilitada la IA local en Railway: el servicio Ollama de Docker
  Compose es un prototipo para desarrollo y no está incluido en el despliegue.
- Probar con contenido ficticio y revisar autenticación, permisos por rol,
  sesiones, enlaces de correo, archivos, PDF y dominio HTTPS antes de invitar al
  equipo.

## Lista de verificación de despliegue y operación

1. Confirmar propietario, precio vigente y límite de consumo desde el panel.
   No compartir credenciales ni datos de pago en el repositorio o el chat.
2. Crear un servicio MySQL administrado y conectar el repositorio al servicio
   web.
3. Configurar variables y `APP_KEY` solo en Railway.
4. Ejecutar migraciones con un comando controlado; no ejecutar seeders en
   producción automáticamente.
5. Verificar `/up`, `/agendar-cita`, agenda, archivos y consumo con contenido
   ficticio.
6. Mantener lista la alternativa local para la presentación.

## Referencias oficiales consultadas

- [Desplegar Laravel](https://docs.railway.com/guides/laravel)
- [MySQL en Railway](https://docs.railway.com/databases/mysql)
- [Variables en Railway](https://docs.railway.com/variables)
- [Healthchecks](https://docs.railway.com/deployments/healthchecks)
- [Volúmenes](https://docs.railway.com/volumes)
- [Planes y precios](https://docs.railway.com/pricing/plans)
- [Builds y Railpack](https://docs.railway.com/builds)
- [Mapeo de Docker Compose a servicios Railway](https://docs.railway.com/guides/docker-compose)
