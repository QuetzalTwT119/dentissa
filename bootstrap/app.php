<?php

use App\Exceptions\AuditoriaNoDisponible;
use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\CerrarSesionUsuarioInactivo;
use App\Http\Middleware\VerificarPermiso;
use App\Http\Middleware\ExigirDosPasos;
use App\Http\Middleware\NoCachearRespuesta;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway termina HTTPS en su proxy y reenvía la petición a PHP.
        // Confiar en el encabezado X-Forwarded-Proto permite generar URLs HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            // Permiso por módulo según config/permisos.php (22/11/2026; antes "role:admin").
            'permiso' => VerificarPermiso::class,
            'no-cache' => NoCachearRespuesta::class,
        ]);

        // Meta no envía token CSRF: el webhook valida la firma HMAC de la app.
        $middleware->validateCsrfTokens(except: ['webhooks/whatsapp']);

        // Seguridad del personal (Manual Técnico, sección 24).
        $middleware->web(append: [
            CabecerasSeguridad::class,
            CerrarSesionUsuarioInactivo::class,
            ExigirDosPasos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuditoriaNoDisponible $exception, Request $request) {
            $mensaje = 'No se guardaron los cambios porque no fue posible registrar la auditoría. Inténtalo de nuevo más tarde.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $mensaje], 503);
            }

            return response()->view('errors.auditoria-no-disponible', status: 503);
        });

        $exceptions->shouldRenderJsonWhen(
            // /disponibilidad* solo la consulta JavaScript: un error debe llegar
            // como JSON 422, no como redirección a una página HTML.
            fn (Request $request) => $request->is('disponibilidad', 'disponibilidad/*'),
        );
    })->create();

