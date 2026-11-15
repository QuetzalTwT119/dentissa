<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dentissa') }} — Odontología Integral</title>

        @include('partials.pwa-meta')

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-cream font-sans text-ink antialiased" @auth data-notifications-updates-url="{{ route('notificaciones.actualizaciones', [], false) }}" data-notifications-cursor="{{ auth()->user()->notificacionesSistema()->max('id') ?? 0 }}" data-notifications-read-url="{{ route('notificaciones.leida', ['notificacion' => '__NOTIFICATION_ID__'], false) }}" data-notifications-mark-seen-url="{{ route('notificaciones.vistas', [], false) }}" data-realtime-user-id="{{ auth()->id() }}" data-realtime-key="{{ config('broadcasting.connections.reverb.key') }}" data-realtime-host="{{ config('reverb.client.host') }}" data-realtime-port="{{ config('reverb.client.port') }}" data-realtime-scheme="{{ config('reverb.client.scheme') }}" @endauth>

        <div class="min-h-full flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header>
                    <div class="mx-auto max-w-7xl px-4 pb-2 pt-8 sm:px-6 sm:pt-10 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 pb-16">
                {{ $slot }}
            </main>

        </div>
        <x-asistente-chat />
    </body>
</html>
