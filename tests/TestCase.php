<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $testConnection = getenv('DENTISSA_TEST_DB_CONNECTION') ?: 'sqlite';
        $testDatabase = getenv('DENTISSA_TEST_DB_DATABASE') ?: 'dentissa_test';

        if (! in_array($testConnection, ['sqlite', 'mysql'], true)) {
            throw new \InvalidArgumentException('DENTISSA_TEST_DB_CONNECTION debe ser sqlite o mysql.');
        }

        putenv('APP_ENV=testing');
        putenv("DB_CONNECTION={$testConnection}");
        if ($testConnection === 'sqlite') {
            putenv('DB_DATABASE=:memory:');
        }
        putenv('CACHE_STORE=array');
        putenv('SESSION_DRIVER=array');
        putenv('QUEUE_CONNECTION=sync');
        putenv('MAIL_MAILER=array');

        $app = parent::createApplication();

        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', $testConnection);
        if ($testConnection === 'sqlite') {
            $app['config']->set('database.connections.sqlite.database', ':memory:');
        } else {
            // La suite puede ejecutarse contra un schema desechable independiente.
            $app['config']->set('database.connections.mysql.database', $testDatabase);
        }
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        // La IA se activa solo en pruebas que la simulan explícitamente.
        // Nunca dependemos del Ollama configurado en el .env del desarrollador.
        $app['config']->set('dentissa.asistente_ia.habilitada', false);

        // 22/11/2026: las pruebas NUNCA usan las credenciales reales del .env.
        // Con el token y el número de WhatsApp reales configurados, una prueba
        // sin Http::fake() mandó recordatorios de verdad a teléfonos inventados.
        // Las pruebas que necesitan estos servicios los configuran y simulan.
        foreach ([
            'dentissa.whatsapp.phone_number_id', 'dentissa.whatsapp.access_token',
            'services.telegram.bot_token', 'services.gmail.client_id',
            'services.gmail.client_secret', 'services.gmail.refresh_token',
        ] as $credencial) {
            $app['config']->set($credencial, null);
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Los tests simulan formularios directamente; la protección CSRF
        // sigue activa en la aplicación real.
        $this->withoutMiddleware(PreventRequestForgery::class);
        Cache::flush();

        // Ninguna prueba sale a internet: una llamada HTTP sin Http::fake() falla.
        Http::preventStrayRequests();
    }
}
