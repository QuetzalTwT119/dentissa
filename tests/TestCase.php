<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('CACHE_STORE=array');
        putenv('SESSION_DRIVER=array');
        putenv('QUEUE_CONNECTION=sync');
        putenv('MAIL_MAILER=array');

        $app = parent::createApplication();

        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Los tests simulan formularios directamente; la protección CSRF
        // sigue activa en la aplicación real.
        $this->withoutMiddleware(PreventRequestForgery::class);
        Cache::flush();
    }
}
