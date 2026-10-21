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
    <body class="h-full font-sans antialiased bg-[#FAF6F6] text-[#2D2526] selection:bg-[#f2b0a6] selection:text-[#2D2526]" @auth data-notifications-updates-url="{{ route('notificaciones.actualizaciones') }}" data-notifications-cursor="{{ auth()->user()->notificacionesSistema()->max('id') ?? 0 }}" data-realtime-user-id="{{ auth()->id() }}" data-realtime-key="{{ config('broadcasting.connections.reverb.key') }}" data-realtime-host="{{ config('reverb.client.host') }}" data-realtime-port="{{ config('reverb.client.port') }}" data-realtime-scheme="{{ config('reverb.client.scheme') }}" @endauth>
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

            <!-- Footer administrativo sutil -->
            <footer class="border-t border-[#e9dfdb]/60 py-6 text-center text-xs text-[#8A7A7D] bg-white/40">
                <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="font-medium text-[#2D2526]/80">Dentissa · Odontología Integral</p>
                    <p class="text-[11px]">Sistema de Gestión Clínica · Dra. Melissa López N.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
