# Dentissa

Sistema de gestión para consultorio dental.

## Documentación del proyecto

- [Manual técnico](docs/MANUAL-TECNICO.md)
- [Manual de usuario](docs/MANUAL-USUARIO.md)
- [Diagramas y flujos](docs/DIAGRAMAS-FLUJOS.md)
- [Plan de entrega](docs/PLAN-ENTREGA.md)
- [Matriz QA del MVP](docs/MATRIZ-QA-MVP.md)
- [Revisión de arquitectura](docs/REVISION-ARQUITECTURA.md)
- [Configuración de WhatsApp](docs/CONFIGURACION-WHATSAPP.md)
- [Preparación de despliegue en Railway](docs/DESPLIEGUE-RAILWAY.md)
- [Migración del sistema dental](docs/MIGRACION-SISTEMA-DENTAL.md)

## Inicio rápido con Docker

Primera instalación (PowerShell):

```powershell
Copy-Item .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate --force
```

Después, en cada arranque:

```bash
docker compose up -d
```

Docker Compose inicia MySQL en un volumen persistente y la aplicación ejecuta
las migraciones al arrancar. La aplicación queda disponible en
<http://localhost:8000>. Para revisar el estado de las migraciones y crear los
datos demo reproducibles:

```bash
docker compose exec app php artisan migrate:status
docker compose exec app php artisan db:seed
```

El servicio MySQL solo se publica en el equipo local, en el puerto `3307` (por
ejemplo, para conectarse con una herramienta de base de datos); entre los
contenedores se usa `mysql:3306`. Las credenciales de desarrollo se definen en
`compose.yaml` y se pueden sustituir con variables `MYSQL_*` en `.env`.

Para ejecutar las pruebas:

```bash
docker compose exec app php artisan test
```

Ese comando usa SQLite en memoria para aislar la suite. Para correr la misma
suite contra MySQL, el servicio crea un schema independiente llamado
`dentissa_test` en la primera inicialización del volumen:

```bash
docker compose exec -e DENTISSA_TEST_DB_CONNECTION=mysql -e DENTISSA_TEST_DB_DATABASE=dentissa_test app php artisan test
```

La implementación de respaldo y restauración está diferida y no forma parte
de esta versión. No uses datos clínicos reales hasta que el equipo apruebe y
pruebe un procedimiento de protección y recuperación.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
