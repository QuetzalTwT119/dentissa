{{--
    "Tu primera visita" (24/11/2026): el proceso en 3 pasos, tal como funciona el
    sistema (agenda en línea → confirmación y recordatorio por WhatsApp →
    historia clínica en la consulta). Idea tomada de los sitios de referencia.
--}}
@php
    $pasos = [
        [
            'titulo' => 'Elige tu horario',
            'texto' => 'Escoge el tratamiento, el día y la hora en línea. Si prefieres, llámanos y lo agendamos contigo.',
            'icono' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'titulo' => 'Recibe la confirmación',
            'texto' => 'Te llega por WhatsApp. Antes de tu cita te recordamos y ahí mismo puedes confirmar, cancelar o cambiar el día.',
            'icono' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        ],
        [
            'titulo' => 'Te esperamos',
            'texto' => 'En tu primera visita llenamos juntos tu historia clínica y te explicamos tu diagnóstico con calma, sin presión.',
            'icono' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        ],
    ];
@endphp
<section id="primera-visita" aria-labelledby="titulo-primera-visita" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24" data-primera-visita>
    <div class="max-w-2xl">
        <p class="eyebrow">Tu primera visita</p>
        <h2 id="titulo-primera-visita" class="mt-4 text-title-1 text-ink">Un proceso claro desde el primer clic</h2>
    </div>

    <ol class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ($pasos as $paso)
            <li class="flex flex-col gap-4 rounded-3xl border border-line bg-white p-6 shadow-soft">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-peach-50 text-brand-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $paso['icono'] }}"/></svg>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-label text-brand-700">Paso {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <h3 class="font-serif text-title-3 font-medium text-ink">{{ $paso['titulo'] }}</h3>
                <p class="text-callout text-muted">{{ $paso['texto'] }}</p>
            </li>
        @endforeach
    </ol>
</section>
