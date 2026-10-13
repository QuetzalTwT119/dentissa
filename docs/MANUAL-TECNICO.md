# Manual técnico de Dentissa

## 1. Propósito

Dentissa es un sistema web para la gestión de una clínica dental. Permite
administrar pacientes, servicios, tratamientos, citas, atención oportuna,
historial clínico, notificaciones y reportes.

Este manual describe cómo levantar el proyecto, ejecutar las pruebas y
mantener el entorno de desarrollo.

## 2. Stack tecnológico

- Laravel 13 con PHP 8.3.
- Blade y Alpine.js para las vistas interactivas.
- Tailwind CSS y Vite para los estilos y assets.
- MySQL 8.4 para la aplicación local en Docker; SQLite en memoria como motor
  predeterminado de la suite rápida de pruebas.
- Docker Compose para ejecutar la aplicación, compatible con Docker Desktop o
  Docker Engine dentro de WSL.
- Git y GitHub para control de versiones.
- PHPUnit/Pest mediante `php artisan test`.

En Windows el equipo puede usar Docker Desktop con la integración de WSL2
activada. También es válido ejecutar Docker Engine directamente dentro de WSL;
los comandos de `docker compose` son los mismos.

## 3. Arranque del proyecto

Desde PowerShell o Windows Terminal:

```powershell
Set-Location C:\laragon\www\dentissa
Copy-Item .env.example .env # solo la primera vez
docker compose build app # solo la primera vez o al cambiar Dockerfile/dependencias
docker compose run --rm app php artisan key:generate --force # solo la primera vez
docker compose up -d
docker compose ps
```

Desde WSL:

```bash
cd /mnt/c/laragon/www/dentissa
cp .env.example .env # solo la primera vez
docker compose build app # solo la primera vez o al cambiar Dockerfile/dependencias
docker compose run --rm app php artisan key:generate --force # solo la primera vez
docker compose up -d
docker compose ps
```

La aplicación queda disponible en <http://localhost:8000>.

Para reconstruir la imagen cuando cambian el `Dockerfile`, los seeders o los
archivos que se copian durante la construcción:

```bash
docker compose build app
docker compose up -d --force-recreate app
```

## 4. Variables de entorno y datos sensibles

`.env` se usa dentro del contenedor, pero está excluido de Git mediante
`.gitignore` y `.dockerignore`. En la primera instalación se copia desde
`.env.example` y se genera `APP_KEY` dentro del contenedor. Nunca se deben subir contraseñas, tokens de
WhatsApp, claves de API ni otros secretos al repositorio.

El archivo `.env.example` sirve como plantilla. Cada desarrollador debe crear
su propio `.env` local.

## 5. Datos demo reproducibles

Los seeders crean usuarios, un paciente, servicios, tratamientos y sus
relaciones. Son repetibles: ejecutarlos no duplica los registros conocidos.

```bash
docker compose exec app php artisan db:seed
```

No usar `migrate:fresh` para una base de datos de desarrollo que contenga
información que se quiera conservar, porque elimina todas las tablas.

MySQL conserva los datos en el volumen Docker `mysql_data`; los archivos subidos
se conservan en `storage/`. El código de la aplicación se copia dentro de la
imagen, por eso se debe reconstruir después de cambiar archivos de código que
no estén montados como volumen.

La persistencia se verificó reiniciando MySQL: el conteo de usuarios demo fue
2 antes y después. Durante el arranque MySQL puede tardar unos segundos en
volver a aceptar conexiones; espera a que `docker compose ps` muestre `healthy`
antes de consultar la base. `docker compose restart mysql` conserva los datos;
`docker compose down -v` elimina el volumen y no debe usarse si se quieren
conservar.

Al iniciar, el contenedor crea las carpetas de caché de Laravel dentro de
`storage/` y `bootstrap/cache`. Esto es necesario porque el volumen del host
puede comenzar vacío en una instalación nueva.

## 6. Pruebas automatizadas

Ejecutar la suite completa:

```bash
docker compose exec app php artisan test
```

Las pruebas usan SQLite en memoria y no deben modificar la base SQLite real del
desarrollo. La suite incluye reglas de catálogo, fechas futuras, ciclo de vida
de citas, agendamiento público, atención oportuna, permisos, historial clínico
y auditoría. El 12/10/2026, después del cambio de concurrencia, la suite
completa pasó con **59 pruebas y 233 aserciones** en MySQL 8.4 en Docker
(`dentissa_test`). En SQLite en memoria (Windows PHP 8.3.33), el resultado fue
58 aprobadas y 1 omitida (la prueba de concurrencia requiere MySQL), con 228
aserciones. Tras añadir la garantía de auditoría atómica, el 13/10/2026 la suite
pasó en GitHub Actions con **62 pruebas y 245 aserciones** en SQLite (1 omitida)
y **63 pruebas y 250 aserciones** en MySQL 8.4. La creación y reagenda bloquean, dentro de su
transacción, la fila de configuración del día de semana antes de revisar las
citas existentes. Esto proporciona un registro común de exclusión aun cuando
todavía no haya citas; la consecuencia es serializar brevemente las reservas
que caigan en cualquier fecha del mismo día de semana. La prueba QA-24 valida
el límite secuencial; QA-34 comprobó con dos procesos PHP concurrentes en MySQL
8.4 que solo uno reserva el último cupo (1 prueba/5 aserciones, 12/10/2026).
SQLite no ofrece una prueba representativa de `lockForUpdate`.

La suite puede correrse también contra el schema MySQL desechable del entorno
Docker. El contenedor utiliza MySQL 8.4 para la aplicación y conserva SQLite
solo como opción aislada de prueba. Para elegir MySQL en la suite:

```bash
docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test
```

La inicialización de un volumen MySQL nuevo crea `dentissa_test` separado de
`dentissa`; `RefreshDatabase` puede reconstruir el schema de pruebas sin tocar
los datos demo de la aplicación. El 13/10/2026, la suite completa contra MySQL
8.4 pasó en GitHub Actions con 63 pruebas y 250 aserciones, incluyendo los
casos de rollback ante una falla de auditoría.

La prueba concurrente se ejecuta por separado porque requiere dos procesos PHP
y MySQL real:

```bash
docker compose run --build --rm --no-deps -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test --compact --filter=ConcurrenciaCitasMySQLTest
```

El caso crea fixtures en el schema desechable, libera dos procesos al mismo
tiempo y espera que exactamente uno guarde la cita. El 12/10/2026 aprobó con
1 prueba/5 aserciones en MySQL 8.4. Con SQLite se marca como omitido; no usar la
base `dentissa` para esta prueba.

Ejecutar una prueba específica:

```bash
docker compose exec app php artisan test --filter=CitaPublicaTest
```

GitHub Actions ejecuta la suite al subir commits a cualquier rama y al abrir o
actualizar un pull request hacia `master`. El job SQLite valida la suite
general; `test-mysql` usa MySQL 8.4 y ejecuta también QA-34. La ejecución #1
completó ambos jobs correctamente el 12/10/2026 ([ver ejecución](https://github.com/QuetzalTwT119/dentissa/actions/runs/38339542421)).
Revisa ambos jobs en la pestaña **Actions** antes de integrar un pull request.

## 7. Flujo Git recomendado

1. Actualizar `master`.
2. Crear una rama descriptiva (`feature/...`, `fix/...` o `docs/...`).
3. Hacer cambios pequeños y comprensibles.
4. Ejecutar las pruebas.
5. Crear un commit con mensaje claro.
6. Hacer `git push -u origin nombre-de-rama`.
7. Crear un Pull Request hacia `master`.
8. Fusionar y volver a sincronizar `master` local.

## 8. Capas principales

- **Rutas:** definen URLs públicas, autenticadas y administrativas.
- **Controladores:** validan la entrada y coordinan el caso de uso.
- **Modelos:** representan usuarios, pacientes, servicios, tratamientos,
  citas e historial.
- **Servicios:** contienen reglas reutilizables como disponibilidad,
  notificaciones y atención oportuna.
- **Vistas Blade/Alpine:** muestran formularios, estados y acciones.
- **Base de datos:** conserva los datos mediante migraciones y MySQL; las
  pruebas pueden usar SQLite en memoria o un schema MySQL aislado.

## 9. Regla de disponibilidad

`DisponibilidadService` consulta el horario de atención, la duración del
tratamiento, el margen entre citas, la capacidad simultánea y las citas ya
registradas. Antes de guardar una cita vuelve a validar el horario dentro de
una transacción para evitar reservas duplicadas.

`CitaReglasService` centraliza las validaciones compartidas por los flujos
interno, público y asistente: catálogo activo, relación servicio-tratamiento y
fecha/hora futura. Esto evita que un formulario aplique reglas distintas a
otro.

La acción `citas.no-show` solo permite marcar una cita confirmada como
**No asistió** cuando llega su `fecha_hora` programada o después. La validación
se realiza en el servidor, por lo que también protege solicitudes directas que
no provienen de la interfaz.

## 10. Atención oportuna e historial

Las solicitudes urgentes y de lista de espera se conservan en la base de
datos. El panel administrativo muestra por separado los pendientes y el
historial de seguimiento, con filtros de estado y fechas. La transición a
**Contactada** o **Cerrada** no elimina datos.

Las notificaciones de cita guardan también `id_cita`, lo que permite enlazar
un aviso con la cita que lo originó sin depender únicamente del texto del
mensaje.

## 11. Auditoría de operaciones sensibles

`AuditoriaService` centraliza el registro de acciones relevantes en
`logs_auditoria`. Se auditan las altas y transiciones de citas, los cambios del
historial clínico, los adjuntos clínicos, los datos oficiales del paciente y el
seguimiento de atención oportuna.

Los adjuntos del historial se sirven mediante una ruta autenticada que permite
su lectura solo al rol `admin` y rechaza registros de imagen que no pertenezcan
al historial clínico. Las pruebas `HistorialImagenAccessTest` cubren el acceso
del administrador, el rechazo al asistente y a visitantes, y el tipo de imagen.

Cada entrada conserva usuario (cuando existe), acción, tabla, registro, fecha,
IP y agente de usuario. Para proteger la privacidad, los cambios de historial
guardan metadatos —por ejemplo paciente, cita, fecha y tipo de consulta—, pero
no copian diagnóstico, medicamentos ni notas clínicas.

Las escrituras de negocio y su entrada de auditoría se ejecutan en una misma
transacción de base de datos. Si la bitácora no puede guardarse, la excepción
revierte también el cambio de negocio y la aplicación muestra una respuesta
503 indicando que no se guardaron los cambios y que se intente de nuevo más
tarde. Las notificaciones externas se envían después de confirmar la
transacción; un fallo al enviar un aviso no deshace una cita válida.

## 12. Autorización por rol

Las rutas autenticadas de agenda usan `auth`; las rutas bajo `/admin` añaden el
middleware `role:admin`. `CitaPolicy` permite las operaciones internas de cita
al personal con rol `admin` o `asistente`, mientras que los adjuntos del
historial clínico están reservados a `admin`. La prueba
`PermisosPorRolTest` verifica que el asistente reciba 403 en 12 páginas
administrativas y al intentar modificar horarios, y que un visitante sea
redirigido al login. Las 3 pruebas (15 aserciones) y la suite completa pasaron
en SQLite en memoria y en MySQL 8.4 con el schema aislado `dentissa_test`.

## 13. Respaldos y recuperación

La automatización de respaldos y restauración se ha diferido y no forma parte
de esta versión del proyecto. Antes de utilizar datos clínicos reales, el equipo
debe definir, implementar y probar un procedimiento que cubra la base de datos,
los archivos subidos, el cifrado, la retención y la recuperación.
