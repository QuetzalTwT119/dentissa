<?php

use App\Exceptions\AuditoriaNoDisponible;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\NoCachearRespuesta;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'no-cache' => NoCachearRespuesta::class,
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
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
