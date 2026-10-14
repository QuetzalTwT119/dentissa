# Preparación de despliegue en Railway

**Estado:** guía de preparación; no se ha creado ni desplegado ningún servicio.
**Revisión:** 14 de octubre de 2026.

El equipo ha señalado Railway con MySQL como destino candidato. La instalación
actual de Docker Desktop sigue siendo el entorno local de desarrollo y pruebas.
Este documento separa ambos entornos para evitar publicar accidentalmente una
configuración local o datos ficticios como si fueran producción.

## Decisiones que faltan antes de desplegar

1. **Aprobar el costo y el plan con el equipo.** Railway no equivale a una
   plataforma de costo garantizado cero: su plan Free incluye hasta USD 1 de
   crédito de uso al mes; Hobby cuesta USD 5 al mes e incluye USD 5 de uso,
   con cobros adicionales si el consumo supera el crédito. El costo exacto
   depende de CPU, RAM, almacenamiento y tráfico, y debe revisarse nuevamente
   en la página de precios antes de activar servicios.
2. **Aprobar dónde se conservarán las subidas.** Dentissa guarda imágenes de
   galería y otros contenidos en `storage/app/public`, y adjuntos clínicos en
   `storage/app/private`. El almacenamiento local efímero de una aplicación
   desplegada no es un repositorio persistente. Se necesita elegir y probar un
   volumen persistente o almacenamiento compatible con S3; para los adjuntos
   clínicos debe preservarse acceso privado. Un volumen Free tiene un límite
   publicado de 0.5 GB y los volúmenes limitan ciertas opciones de réplicas y
   despliegue.
3. **Mantener fuera los datos de pacientes reales.** El procedimiento de
   respaldo y restauración está diferido. No se debe cargar información médica
   real ni abrir el servicio al uso clínico hasta aprobar, implementar y probar
   la recuperación de la base y archivos.

## Diferencia entre el entorno local y producción

El `Dockerfile` raíz actual se usa con `compose.yaml` para desarrollo y QA:

- instala dependencias de desarrollo por defecto (`INSTALL_DEV=true`);
- arranca con `php artisan serve` en el puerto local `8000`;
- ejecuta `php artisan migrate --force` cada vez que inicia el contenedor;
- depende del `.env` local montado por Compose.

No se debe conectar Railway a esa configuración tal como está. La guía de
Laravel de Railway describe su despliegue Railpack con PHP-FPM y Caddy; como el
repositorio tiene un Dockerfile raíz, antes del primer deploy hay que decidir y
verificar en los detalles de build cuál constructor usará el servicio. No
asumir que Railway está utilizando Railpack solo porque el proyecto sea Laravel.
La configuración debe usar un proceso web apto para producción y escuchar en el
puerto `PORT` proporcionado por Railway.

## Checklist de preparación

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
  después de revisar migraciones. No ejecutar `db:seed` en producción salvo una
  decisión explícita y datos aprobados.
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

## Orden recomendado cuando el equipo autorice el despliegue

1. Confirmar presupuesto, límites de uso y propietario de la cuenta/proyecto.
2. Elegir constructor de producción y almacenamiento persistente.
3. Crear servicios y variables desde el dashboard, sin copiar secretos al repo.
4. Desplegar una versión de staging con contenido exclusivamente ficticio.
5. Verificar `/up`, migraciones, flujos principales, archivos y costos observados.
6. Aprobar respaldos/restauración y privacidad antes de considerar datos reales.

## Referencias oficiales consultadas

- [Desplegar Laravel](https://docs.railway.com/guides/laravel)
- [MySQL en Railway](https://docs.railway.com/databases/mysql)
- [Variables en Railway](https://docs.railway.com/variables)
- [Healthchecks](https://docs.railway.com/deployments/healthchecks)
- [Volúmenes](https://docs.railway.com/volumes)
- [Planes y precios](https://docs.railway.com/pricing/plans)
