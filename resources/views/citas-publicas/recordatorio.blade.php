<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
        <meta name="theme-color" content="#fbf7f3">
        {{-- El enlace firmado funciona como una llave: que no lo indexen ni lo
             compartan los buscadores. --}}
        <meta name="robots" content="noindex, nofollow">
        @include('partials.favicon')

        <title>Tu cita — Dentissa Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-cream pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)] font-sans text-ink antialiased">
        @use('App\Services\RespuestaRecordatorioService', 'Respuesta')
        @php
            // APP_LOCALE sigue en inglés: se fuerza español como en la agenda pública.
            $fecha = ucfirst($cita->fecha_hora->copy()->locale('es')->translatedFormat('l j \d\e F \d\e Y'));
            $hora = $cita->fecha_hora->format('H:i');

            // Textos de la pregunta, antes de que el paciente confirme.
            $preguntas = [
                Respuesta::CONFIRMAR => ['¿Confirmas que asistirás?', 'Avisaremos al consultorio que te esperamos.', 'Sí, asistiré', 'btn-brand'],
                Respuesta::REAGENDAR => ['¿Quieres cambiar tu cita?', 'Liberaremos este horario y podrás elegir otro día y hora en la agenda en línea.', 'Liberar horario y reagendar', 'btn-brand'],
                Respuesta::CANCELAR => ['¿Cancelar tu cita?', 'El horario quedará libre para otro paciente. Puedes agendar de nuevo cuando quieras.', 'Sí, cancelar mi cita', 'btn-secondary'],
            ];

            // Textos del resultado, después de responder.
            $resultados = [
                Respuesta::CONFIRMAR => ['¡Gracias! Te esperamos.', 'Registramos que vas a asistir. Si algo cambia, comunícate con el consultorio.'],
                Respuesta::REAGENDAR => ['Liberamos tu horario.', 'Elige ahora el nuevo día y hora que te acomode.'],
                Respuesta::CANCELAR => ['Tu cita fue cancelada.', 'Gracias por avisarnos. Cuando quieras volver, puedes agendar en línea.'],
            ];

            $otras = array_diff(Respuesta::ACCIONES, [$accion]);
            $enlaces = \App\Mail\RecordatorioCita::enlaces($cita);
            $etiquetasOtras = [
                Respuesta::CONFIRMAR => 'Sí voy a asistir',
                Respuesta::REAGENDAR => 'Prefiero reagendar',
                Respuesta::CANCELAR => 'Prefiero cancelar',
            ];
        @endphp

        <main class="flex min-h-[100dvh] items-center justify-center px-4 py-10">
            <div class="w-full max-w-md rounded-4xl border border-line bg-white p-8 text-center shadow-lift sm:p-10" data-recordatorio-estado="{{ $resultado ?? ($disponible ? 'pregunta' : Respuesta::NO_DISPONIBLE) }}">
                <p class="firma text-2xl text-brand-600">Melissa López N.</p>

                @if ($resultado && $resultado !== Respuesta::NO_DISPONIBLE)
                    <h1 class="mt-3 text-title-2 text-ink">{{ $resultados[$resultado][0] }}</h1>
                @elseif ($resultado === Respuesta::NO_DISPONIBLE || ! $disponible)
                    <h1 class="mt-3 text-title-2 text-ink">Esta cita ya no admite cambios.</h1>
                @else
                    <h1 class="mt-3 text-title-2 text-ink">{{ $preguntas[$accion][0] }}</h1>
                @endif

                <dl class="mt-6 divide-y divide-line/80 rounded-2xl border border-line bg-cream text-left text-sm">
                    <div class="px-5 py-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-label text-muted">Tratamiento</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ $cita->tratamiento?->nombre ?? 'Consulta' }}</dd>
                    </div>
                    <div class="px-5 py-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-label text-muted">Fecha y hora</dt>
                        <dd @class(['mt-1 font-semibold', 'text-brand-700' => $disponible, 'text-muted line-through' => ! $disponible && $cita->estado === \App\Enums\EstadoCita::Cancelada])>{{ $fecha }} · {{ $hora }} hrs</dd>
                    </div>
                </dl>

                @if ($resultado && $resultado !== Respuesta::NO_DISPONIBLE)
                    <p class="mt-5 text-callout text-muted">{{ $resultados[$resultado][1] }}</p>
                    @if (in_array($resultado, [Respuesta::REAGENDAR, Respuesta::CANCELAR], true))
                        <a href="{{ route('citas.publica.create') }}" class="btn-brand mt-8 w-full py-3">
                            Elegir un nuevo horario
                        </a>
                    @else
                        <a href="/" class="btn-secondary mt-8 w-full py-3">Volver al inicio</a>
                    @endif
                @elseif ($resultado === Respuesta::NO_DISPONIBLE || ! $disponible)
                    <p class="mt-5 text-callout text-muted">
                        La cita ya pasó, se canceló o ya fue atendida. Si necesitas ayuda, llama al consultorio al
                        <a class="link" href="{{ config('dentissa.contacto.telefono_enlace') }}">{{ config('dentissa.contacto.telefono') }}</a>.
                    </p>
                    <a href="{{ route('citas.publica.create') }}" class="btn-brand mt-8 w-full py-3">Agendar una cita</a>
                @else
                    <p class="mt-5 text-callout text-muted">{{ $preguntas[$accion][1] }}</p>

                    <form method="POST" action="{{ $urlFormulario }}" class="mt-8">
                        @csrf
                        <button type="submit" class="{{ $preguntas[$accion][3] }} w-full py-3">{{ $preguntas[$accion][2] }}</button>
                    </form>

                    <p class="mt-6 text-xs font-semibold uppercase tracking-label text-muted">¿Otra respuesta?</p>
                    <div class="mt-2 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm">
                        @foreach ($otras as $otra)
                            <a href="{{ $enlaces[$otra] }}" class="link inline-flex min-h-11 items-center">{{ $etiquetasOtras[$otra] }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </body>
</html>
