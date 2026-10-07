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
- SQLite para desarrollo local.
- Docker Compose para ejecutar la aplicación.
- Git y GitHub para control de versiones.
- PHPUnit/Pest mediante `php artisan test`.

No se requiere Docker Desktop: el proyecto se ejecuta con Docker Engine dentro
de WSL.

## 3. Arranque del proyecto

Desde WSL:

```bash
cd /mnt/c/laragon/www/dentissa
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
`.gitignore` y `.dockerignore`. Nunca se deben subir contraseñas, tokens de
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

La base SQLite y `storage/` están montados como volúmenes para conservar los
datos entre recreaciones del contenedor. El código de la aplicación se copia
dentro de la imagen, por eso se debe reconstruir después de cambiar archivos
de código que no estén montados como volumen.

## 6. Pruebas automatizadas

Ejecutar la suite completa:

```bash
docker compose exec app php artisan test
```

La última validación registrada fue de **33 pruebas aprobadas y 103
aserciones**. Las pruebas usan SQLite en memoria y no deben modificar la base
SQLite real del desarrollo.

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
- **Base de datos:** conserva los datos mediante migraciones y SQLite.

## 9. Regla de disponibilidad

`DisponibilidadService` consulta el horario de atención, la duración del
tratamiento, el margen entre citas, la capacidad simultánea y las citas ya
registradas. Antes de guardar una cita vuelve a validar el horario dentro de
una transacción para evitar reservas duplicadas.
