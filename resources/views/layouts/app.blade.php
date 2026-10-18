<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.pwa-meta')

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" @auth data-notifications-updates-url="{{ route('notificaciones.actualizaciones') }}" data-notifications-cursor="{{ auth()->user()->notificacionesSistema()->max('id') ?? 0 }}" data-realtime-user-id="{{ auth()->id() }}" data-realtime-key="{{ config('broadcasting.connections.reverb.key') }}" data-realtime-host="{{ config('reverb.client.host') }}" data-realtime-port="{{ config('reverb.client.port') }}" data-realtime-scheme="{{ config('reverb.client.scheme') }}" @endauth>
        <div class="min-h-screen bg-brand-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
