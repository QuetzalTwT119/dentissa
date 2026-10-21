# Dentissa

Sistema web de gestión para un consultorio dental, desarrollado con Laravel.
Este repositorio corresponde al proyecto Dentissa; la información que aparece
en los datos de demostración no es información oficial de una clínica.

## Trabajo conjunto: Antigravity + Dentissa

Antigravity trabaja directamente en `C:\laragon\www\dentissa`, la misma carpeta de Dentissa. Puede rediseñar las vistas y recursos de interfaz delimitados en `ANTIGRAVITY.md`; el backend, Docker y los datos clínicos quedan fuera de su alcance.

Cuando Antigravity termine una propuesta, abre PowerShell en la carpeta del proyecto y ejecuta:

```powershell
.\scripts\antigravity.ps1
```

El comando valida el frontend en un contenedor aislado y, si aprueba, reconstruye la aplicación en `http://localhost:8000`. No se copian archivos entre carpetas. Para validar sin reconstruir, agrega `-ValidarSolo`.

## Respaldos cifrados locales

El disco externo `E:\DentissaBackups` contiene el repositorio cifrado de Restic. Para crear un respaldo de MySQL y `storage/app`, sin guardar la contraseña en archivos, ejecuta:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\respaldo-dentissa.ps1
```

La consola pide la contraseña de forma oculta, crea un snapshot cifrado y verifica una fracción de sus datos. No elimina copias anteriores. Para ensayar una recuperación fuera de la aplicación activa:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\restaurar-prueba-dentissa.ps1
```

La recuperación sólo crea una carpeta nueva bajo `E:\DentissaBackups\restauraciones-prueba`; nunca restaura sobre la base MySQL activa.

## Requisitos

- Windows con Docker Desktop y backend WSL 2 habilitado, o Docker Engine y
  Docker Compose dentro de WSL/Linux.
- Git para clonar el repositorio.
- Puertos locales `8000` (aplicación) y `3307` (MySQL) disponibles.

No es necesario instalar PHP, Composer, Node.js ni MySQL en Windows: se ejecutan
dentro de los contenedores Docker.

## Instalación inicial

En PowerShell, desde la carpeta del proyecto:

```powershell
Copy-Item .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate --force
docker compose up -d
docker compose ps
docker compose exec app php artisan db:seed
```

En la salida de `docker compose ps`, espera a que MySQL indique `healthy` y la
aplicación `Up`. Abre <http://localhost:8000>. La aplicación ejecuta las
migraciones al iniciar. El comando `db:seed` crea los datos demo repetibles;
no uses datos clínicos reales.

Para arrancar más adelante:

```powershell
docker compose up -d
docker compose ps
```

Para trabajar con actualización automática del contenedor local:

```powershell
docker compose up --build --watch app
```

Deja esa terminal abierta mientras desarrollas. Al guardar cambios PHP, rutas,
configuración o vistas, Compose los sincroniza con el contenedor; al cambiar
CSS, JavaScript o dependencias, reconstruye la imagen y los assets de Vite.
Detén el modo de desarrollo con `Ctrl+C`. No borra la base de datos ni su
volumen.

Para comparar la versión local con producción, abre `/version` en ambos sitios.
Solo son iguales cuando el campo `commit` coincide y `working_tree_clean` en
local es `true`; si es `false`, hay cambios locales aún no incluidos en un
commit.

El bot de Telegram es opcional y se inicia cuando ya configuraste
`DENTISSA_TELEGRAM_BOT_TOKEN` en `.env`, con
`docker compose --profile telegram up -d`. Consulta la [guía de Telegram](docs/CONFIGURACION-TELEGRAM.md)
para probarlo y administrar el proceso.

Para detener los contenedores sin borrar los datos:

```powershell
docker compose down
```

No agregues `-v` a `docker compose down`: esa opción elimina el volumen donde
MySQL conserva la base local.

## Pruebas

Suite general aislada en SQLite:

```powershell
docker compose exec app php artisan test --compact
```

Suite completa en el schema de pruebas MySQL (`dentissa_test`), separado de la
base de desarrollo:

```powershell
docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test --compact
```

### Validar una entrega de frontend

Cuando se integre una propuesta visual externa, ejecuta este control antes de
probarla manualmente. Construye los recursos de Vite, compila las vistas Blade y
ejecuta pruebas de portada, imágenes, PWA y diseño responsivo con SQLite
efímero; no modifica la base local ni publica servicios.

```powershell
docker compose --profile qa run --rm --build qa
```

Última ejecución local registrada (14/10/2026): **95 pruebas y 379 aserciones
aprobadas** en MySQL 8.4. En SQLite: **94 aprobadas, 1 omitida y 374
aserciones**; la prueba omitida requiere concurrencia real en MySQL. GitHub
Actions también valida cambios enviados y pull requests; revisa sus dos jobs
en la pestaña **Actions** antes de integrar.

## Documentación

- [Manual técnico](docs/MANUAL-TECNICO.md)
- [Manual de usuario](docs/MANUAL-USUARIO.md)
- [Matriz QA del MVP](docs/MATRIZ-QA-MVP.md)
- [Plan de entrega](docs/PLAN-ENTREGA.md)
- [Revisión de arquitectura](docs/REVISION-ARQUITECTURA.md)
- [Preparación de despliegue en Railway](docs/DESPLIEGUE-RAILWAY.md)
- [Diagramas y flujos](docs/DIAGRAMAS-FLUJOS.md)
- [Configuración de WhatsApp](docs/CONFIGURACION-WHATSAPP.md)
- [Configuración de Telegram](docs/CONFIGURACION-TELEGRAM.md)
- [Migración del sistema dental](docs/MIGRACION-SISTEMA-DENTAL.md)

## Seguridad y estado del proyecto

Cada integrante debe mantener sus secretos en su `.env` local. No subas `.env`,
tokens, contraseñas ni datos personales o clínicos a GitHub. El respaldo y la
restauración están diferidos; por ahora usa exclusivamente datos ficticios.
Railway es el destino elegido para una demostración escolar con datos
ficticios. La preparación local y el procedimiento pendiente están en
[la guía de despliegue](docs/DESPLIEGUE-RAILWAY.md); todavía no se ha creado un
servicio. Consulta [el plan de entrega](docs/PLAN-ENTREGA.md) para el estado
actual del MVP.
