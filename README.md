# Dentissa

Sistema web de gestión para un consultorio dental, desarrollado con Laravel.
Este repositorio corresponde al proyecto Dentissa; la información que aparece
en los datos de demostración no es información oficial de una clínica.

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
