# Preparación de despliegue escolar en Railway

**Destino decidido:** Railway, para una demostración escolar breve con datos
ficticios. **Estado:** preparación local; aún no se creó ni desplegó ningún
servicio. **Revisión:** 15 de octubre de 2026.

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

El servicio de Railway se preparará desde GitHub usando Railpack, que detecta
Laravel y utiliza PHP-FPM/Caddy. Se renombró el Dockerfile local para que
Railway no detecte por accidente el contenedor de desarrollo como producción.
La aplicación y MySQL se crearán como servicios separados; Railway no ejecuta
el `compose.yaml` local como una unidad.

Railpack debe compilar los recursos Vite (`npm run build`) y usar la versión
PHP compatible con `composer.json` (PHP 8.3 o superior, dentro de la línea
soportada por Laravel). En los logs de build se comprobará la instalación de
Composer y Node y la generación de `public/build/manifest.json`. No configurar
un comando de inicio local como `php artisan serve`.

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
  No copiar credenciales al README, al chat ni a Git. Los valores de tratamientos
  que inserta el catálogo son ficticios; no representan precios reales.
- Configurar `/up` como healthcheck: Laravel ya registra esa ruta en
  `bootstrap/app.php`. Railway usa el healthcheck para validar la nueva versión
  durante el despliegue; no sustituye un monitor continuo.
- Elegir almacenamiento persistente para `storage/app` y comprobar una
  restauración. No exponer los archivos privados del historial mediante el
  disco público.
- Dejar deshabilitada la IA local en Railway: el servicio Ollama de Docker
  Compose es un prototipo para desarrollo y no está incluido en el despliegue.
- Probar con contenido ficticio y revisar autenticación, permisos por rol,
  sesiones, enlaces de correo, archivos, PDF y dominio HTTPS antes de invitar al
  equipo.

## Orden del despliegue (todavía no ejecutado)

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
