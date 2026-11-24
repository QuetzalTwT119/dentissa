{{--
    Preguntas frecuentes (24/11/2026): las de Contenido → Preguntas frecuentes
    (activas, en su orden). Antes solo las usaba el asistente; la portada las
    recibía pero no las mostraba. Acordeón nativo (<details>): funciona sin
    JavaScript y con teclado.
--}}
@if (($faqs ?? collect())->isNotEmpty())
    <section id="preguntas" aria-labelledby="titulo-preguntas" class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8 lg:pt-28" data-preguntas>
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <p class="eyebrow">Preguntas frecuentes</p>
                <h2 id="titulo-preguntas" class="mt-4 text-title-1 text-ink">Resolvemos tus dudas</h2>
                <p class="mt-3 text-callout text-muted">¿No encuentras la tuya? Escríbenos por WhatsApp o pregúntale al asistente.</p>
            </div>
            <div class="divide-y divide-line/80 border-y border-line/80 lg:col-span-7">
                @foreach ($faqs as $faq)
                    <details class="group py-1" data-pregunta>
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 rounded-lg py-3 text-left font-semibold text-ink transition-colors hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq->pregunta }}</span>
                            <svg class="h-5 w-5 shrink-0 text-brand-600 transition-transform duration-200 group-open:rotate-45 motion-reduce:transition-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                        </summary>
                        <p class="pb-5 pr-9 text-callout text-ink-soft">{{ $faq->respuesta }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
