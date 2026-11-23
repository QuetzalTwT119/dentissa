<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
        <meta name="theme-color" content="#fbf7f3">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.favicon')

        <title>Acceso · Melissa López N. — Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-italic.woff2" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-cream font-sans text-ink antialiased">
        <main class="relative isolate flex min-h-[100dvh] items-center justify-center overflow-hidden bg-cream px-4 pb-8 pt-20 sm:px-6 lg:py-12">
            <div aria-hidden="true" class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-peach-200/50 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-brand-200/30 blur-3xl"></div>

            <a href="/" class="absolute left-4 top-[max(1rem,env(safe-area-inset-top))] z-10 inline-flex min-h-11 items-center gap-2 rounded-full border border-line bg-white/90 px-4 text-sm font-semibold text-ink shadow-2xs backdrop-blur transition-colors hover:border-peach-300 hover:text-brand-700 sm:left-8 sm:top-8">
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12" />
                </svg>
                Volver al sitio
            </a>

            <div class="relative grid w-full max-w-5xl overflow-hidden rounded-4xl border border-line bg-white shadow-lift lg:grid-cols-[0.95fr_1.05fr]">
                <aside class="relative flex flex-col justify-between overflow-hidden bg-[#2b1a20] p-5 text-white sm:min-h-56 sm:p-8 lg:min-h-[560px] lg:p-10">
                    <img src="/images/dentissa/hero-inicio.jpg" alt="Odontóloga conversando con una paciente en el consultorio" class="absolute inset-0 h-full w-full object-cover object-[62%_center]">
                    <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-[#2b1a20]/90 via-[#2b1a20]/40 to-[#2b1a20]/10"></div>

                    <x-marca tono="oscuro" tamano="grande" class="relative w-fit" />

                    <div class="relative mt-8 hidden max-w-lg sm:block">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-caption font-semibold uppercase tracking-label text-white backdrop-blur-sm">
                            <span class="h-1.5 w-1.5 rounded-full bg-peach-300" aria-hidden="true"></span>
                            Espacio de trabajo Dentissa
                        </p>
                        <h1 class="mt-4 text-title-1">Cada cita merece una atención bien coordinada.</h1>
                        <p class="mt-3 max-w-md text-callout text-white/85 sm:text-body">Agenda, pacientes y solicitudes del consultorio, organizados para el equipo.</p>
                    </div>

                    <p class="relative mt-5 hidden items-center gap-2 text-xs font-medium text-white/85 sm:inline-flex sm:text-sm">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Z" />
                        </svg>
                        Acceso privado del consultorio
                    </p>
                </aside>

                <section class="flex items-center px-5 py-8 sm:p-10 lg:px-14 lg:py-14">
                    <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
