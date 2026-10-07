<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests simulan formularios directamente; la protección CSRF
        // sigue activa en la aplicación real.
        $this->withoutMiddleware(PreventRequestForgery::class);
        Cache::flush();
    }
}
