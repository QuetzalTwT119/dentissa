<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dentissa') }} — Odontología Integral</title>

        @include('partials.pwa-meta')

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-[#FAF6F6] text-[#2D2526] selection:bg-[#f2b0a6] selection:text-[#2D2526]" @auth data-notifications-updates-url="{{ route('notificaciones.actualizaciones', [], false) }}" data-notifications-cursor="{{ auth()->user()->notificacionesSistema()->max('id') ?? 0 }}" data-notifications-read-url="{{ route('notificaciones.leida', ['notificacion' => '__NOTIFICATION_ID__'], false) }}" data-notifications-mark-seen-url="{{ route('notificaciones.vistas', [], false) }}" data-realtime-user-id="{{ auth()->id() }}" data-realtime-key="{{ config('broadcasting.connections.reverb.key') }}" data-realtime-host="{{ config('reverb.client.host') }}" data-realtime-port="{{ config('reverb.client.port') }}" data-realtime-scheme="{{ config('reverb.client.scheme') }}" @endauth>
        <!-- Acento superior de marca Dentissa -->
        <div class="h-1 bg-gradient-to-r from-[#d75078] via-[#f2b0a6] to-[#4E8D72] w-full sticky top-0 z-50"></div>

        <div class="min-h-full flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-[#e9dfdb]/80 bg-white/80 backdrop-blur-md sticky top-1 z-30 shadow-[0_2px_8px_-2px_rgba(45,37,38,0.03)] transition-all">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 pb-16">
                {{ $slot }}
            </main>

        </div>
    </body>
</html>
