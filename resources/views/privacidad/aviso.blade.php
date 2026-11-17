<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fbf7f3">
        @include('partials.favicon')

        <title>Aviso de privacidad — Dentissa Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-cream font-sans text-ink antialiased">
        @php
            $privacidad = config('dentissa.privacidad');
            // Un dato que la clínica aún no llena se ve resaltado, para que nadie
            // publique el aviso creyendo que está completo.
            $dato = fn (?string $valor, string $falta) => $valor
                ? e($valor)
                : '<mark class="rounded bg-amber-100 px-1 font-semibold text-amber-900">[Por completar: '.e($falta).']</mark>';
            $correo = $privacidad['correo'];
        @endphp

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-line bg-white px-4 text-sm font-semibold text-ink hover:text-brand-700">
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Volver al sitio
            </a>

            <article class="mt-6 rounded-4xl border border-line bg-white p-6 shadow-soft sm:p-10" data-aviso-version="{{ $privacidad['version'] }}">
                <p class="firma text-xl text-brand-600">Melissa López N.</p>
                <h1 class="mt-2 text-title-1">Aviso de privacidad integral</h1>
                <p class="mt-2 text-sm text-muted">Versión {{ $privacidad['version'] }}</p>

                @unless ($privacidad['revisado'])
                    <p role="note" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-callout text-amber-900" data-aviso-borrador>
                        <strong>Borrador pendiente de revisión legal.</strong> Este texto es una plantilla preparada por el equipo de desarrollo; debe revisarlo un abogado o la clínica antes de usar el sistema con pacientes reales.
                    </p>
                @endunless

                <div class="mt-8 space-y-8 text-body text-ink-soft [&_h2]:mb-2 [&_h2]:text-title-3 [&_h2]:font-medium [&_h2]:text-ink [&_li]:ml-5 [&_li]:list-disc">
                    <section>
                        <h2>1. Responsable de tus datos</h2>
                        <p>{!! $dato($privacidad['responsable'], 'nombre del responsable') !!}, con domicilio en {!! $dato($privacidad['domicilio'], 'domicilio') !!}, es responsable del tratamiento de tus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>
                    </section>

                    <section>
                        <h2>2. Datos que recabamos</h2>
                        <ul>
                            <li><strong>Identificación y contacto:</strong> nombre, apellidos, teléfono y correo electrónico, que capturas al agendar por el sitio o por Telegram.</li>
                            <li><strong>Datos de salud (datos sensibles):</strong> el motivo de tu visita, si lo escribes, y la información de tu historial clínico que registra la doctora durante tu atención.</li>
                            <li>Si lo proporcionas en el consultorio: CURP, RFC, clave de elector y datos de tu tutor o responsable.</li>
                        </ul>
                    </section>

                    <section>
                        <h2>3. Para qué usamos tus datos</h2>
                        <p><strong>Finalidades necesarias</strong> para darte el servicio:</p>
                        <ul>
                            <li>Agendar, confirmar, reprogramar y cancelar tus citas.</li>
                            <li>Enviarte avisos y el recordatorio de tu cita por correo y WhatsApp.</li>
                            <li>Integrar y conservar tu expediente clínico para tu atención dental.</li>
                        </ul>
                        <p class="mt-3">No usamos tus datos para publicidad ni los vendemos.</p>
                    </section>

                    <section>
                        <h2>4. Con quién se comparten</h2>
                        <p>No transferimos tus datos a terceros para fines propios de ellos. Para operar el sistema usamos proveedores que los tratan solo por cuenta de la clínica: el servicio de hospedaje del sitio, el correo electrónico de Google, WhatsApp de Meta y Telegram (solo si agendas por ese medio). También podremos compartirlos cuando una autoridad competente lo requiera conforme a la ley.</p>
                    </section>

                    <section>
                        <h2>5. Tus derechos ARCO y cómo revocar tu consentimiento</h2>
                        <p>Puedes <strong>acceder</strong> a tus datos, <strong>rectificarlos</strong>, <strong>cancelarlos</strong> u <strong>oponerte</strong> a su uso, así como revocar tu consentimiento, enviando una solicitud a {!! $correo ? '<a class="link" href="mailto:'.e($correo).'">'.e($correo).'</a>' : $dato(null, 'correo para solicitudes') !!} o llamando al <a class="link" href="{{ config('dentissa.contacto.telefono_enlace') }}">{{ config('dentissa.contacto.telefono') }}</a>. Indica tu nombre, cómo localizarte y qué solicitas. Te responderemos en los plazos que marca la ley.</p>
                        <p class="mt-3">El expediente clínico debe conservarse el tiempo que exige la normativa de salud aplicable, por lo que algunas solicitudes de cancelación pueden tener esa limitación.</p>
                    </section>

                    <section>
                        <h2>6. Consentimiento</h2>
                        <p>Al marcar la casilla del formulario para agendar, o al confirmar tu cita por Telegram, aceptas este aviso y otorgas tu consentimiento expreso para el tratamiento de tus datos, incluidos los de salud, para las finalidades descritas. El sistema guarda la fecha y la versión del aviso que aceptaste.</p>
                    </section>

                    <section>
                        <h2>7. Cambios a este aviso</h2>
                        <p>Cualquier cambio se publicará en esta misma página con una nueva versión.</p>
                    </section>
                </div>
            </article>
        </main>
    </body>
</html>
