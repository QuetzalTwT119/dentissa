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
y auditoría. La última ejecución aprobada en Docker fue de **55 pruebas y 213
aserciones** en SQLite y MySQL. La suite valida el límite de capacidad de citas
de forma secuencial. No demuestra el comportamiento de dos reservas
simultáneas: antes de producción, esa garantía debe verificarse con el motor de
base de datos que se vaya a utilizar; SQLite no ofrece una prueba
representativa de los bloqueos `lockForUpdate`.

La suite puede correrse también contra el schema MySQL desechable del entorno
Docker. El contenedor utiliza MySQL 8.4 para la aplicación y conserva SQLite
solo como opción aislada de prueba. Para elegir MySQL en la suite:

```bash
docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test
```

La inicialización de un volumen MySQL nuevo crea `dentissa_test` separado de
`dentissa`; `RefreshDatabase` puede reconstruir el schema de pruebas sin tocar
los datos demo de la aplicación. La última ejecución pasó con 55 pruebas y 213
aserciones en cada motor. La prueba de capacidad sigue siendo secuencial y aún
no constituye una prueba de dos reservas realmente concurrentes.

Ejecutar una prueba específica:

```bash
docker compose exec app php artisan test --filter=CitaPublicaTest
```

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

## 12. Respaldo y restauración

Hay dos pares de scripts independientes. `backup.ps1` y `restore.ps1` trabajan
con SQLite y `storage/app`; no respaldan ni restauran MySQL. Para MySQL se usan
`backup-mysql.ps1` y `restore-mysql.ps1`. Estos últimos requieren Windows
PowerShell 5.1 o PowerShell 7, Docker Compose y que el servicio `mysql` esté
saludable.

Crear un respaldo MySQL desde PowerShell en la raíz del proyecto:

```powershell
./scripts/backup-mysql.ps1
```

El destino predeterminado es `backups/`, excluido de Git. Para escribirlo en
una unidad externa conectada, indica su ruta absoluta:

```powershell
./scripts/backup-mysql.ps1 -Destination 'E:\DentissaBackups'
```

Si la política local bloquea scripts, se puede usar una excepción limitada al
proceso iniciado, sin guardar un cambio permanente:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\backup-mysql.ps1 -Destination 'E:\DentissaBackups'
```

El ZIP incluye `database/dentissa.sql` y metadatos de origen, pero no incluye
`storage/app` ni `.env`. Es un archivo SQL sin cifrar; guárdalo en una ubicación
protegida, fuera de Git y de carpetas compartidas. No lo uses con datos clínicos
reales hasta que el destino y el cifrado estén aprobados.

Restaurar una copia de práctica:

```powershell
./scripts/restore-mysql.ps1 -Backup ./backups/dentissa-mysql-AAAAmmdd-HHmmss.zip
```

La restauración valida el formato y el origen, y crea una base nueva con prefijo
`dentissa_restore_`. Nunca sobrescribe la base activa ni una base existente; el
schema aislado queda disponible para inspección. Esta operación valida la
recuperación de la base de datos, pero no cambia la conexión activa de la app ni
restaura archivos subidos. Un procedimiento de sustitución/corte de la base
activa queda pendiente y requiere respaldo preventivo y aprobación explícita.
Restaura solo ZIPs propios y confiables: el dump es SQL ejecutable y el formato
no tiene firma criptográfica.

Las copias SQLite siguen disponibles con `./scripts/backup.ps1` y
`./scripts/restore.ps1 -Backup ./backups/dentissa-AAAAmmdd-HHmmss.zip`. La
restauración SQLite pide escribir `RESTAURAR`, guarda una copia preventiva y
reemplaza SQLite y `storage/app`; no es intercambiable con MySQL.

Los respaldos pueden contener datos personales y clínicos. Manténlos fuera de
Git y de carpetas compartidas sin protección. Esta solución crea respaldos
locales; no cubre fallas o pérdida del mismo equipo. Antes de usar datos reales,
el equipo debe seguir la [política de respaldos](POLITICA-RESPALDOS.md), elegir
almacenamiento externo con acceso restringido y cifrado, y ensayar la
restauración allí.
