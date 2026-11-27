{{--
    Preguntas frecuentes (24/11/2026): las de Contenido → Preguntas frecuentes
    (activas, en su orden). Antes solo las usaba el asistente; la portada las
    recibía pero no las mostraba. Acordeón nativo (<details>): funciona sin
    JavaScript y con teclado.
--}}
@if (($faqs ?? collect())->isNotEmpty())
    <section id="preguntas" aria-labelledby="titulo-preguntas" class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8 lg:pt-28" data-preguntas>
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
            {{-- 27/11/2026: en escritorio el título se queda a la vista mientras bajan las
                 preguntas (antes quedaba un hueco grande a la izquierda) y lleva los dos
                 caminos de ayuda como botones, no solo como texto. --}}
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-28" data-preguntas-encabezado>
                    <p class="eyebrow">Preguntas frecuentes</p>
                    <h2 id="titulo-preguntas" class="mt-4 text-title-1 text-ink">Resolvemos tus dudas</h2>
                    <p class="mt-3 text-callout text-muted">¿No encuentras la tuya? Escríbenos por WhatsApp o pregúntale al asistente.</p>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $contacto['whatsapp']) }}?text={{ rawurlencode('Hola, tengo una duda sobre el consultorio.') }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-sage-500 px-5 text-sm font-semibold text-white transition-colors hover:bg-sage-600" data-preguntas-whatsapp>
                            Escribir por WhatsApp
                            <span class="sr-only">(se abre en otra pestaña)</span>
                        </a>
                        <button type="button" x-data x-on:click="$dispatch('abrir-asistente')"
                                class="inline-flex min-h-11 items-center justify-center rounded-full border border-line bg-white px-5 text-sm font-semibold text-ink transition-colors hover:border-peach-300 hover:text-brand-700" data-preguntas-asistente>
                            Preguntar al asistente
                        </button>
                    </div>
                </div>
            </div>
            {{-- 27/11/2026: cada pregunta en su tarjeta redondeada (antes una lista con líneas),
                 más fácil de distinguir y de tocar en el celular. --}}
            <div class="space-y-3 lg:col-span-7">
                @foreach ($faqs as $faq)
                    <details class="group rounded-2xl border border-line bg-white shadow-2xs transition-colors open:border-brand-200 open:bg-brand-50/30 hover:border-peach-300" data-pregunta>
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 rounded-2xl px-5 py-4 text-left font-semibold text-ink transition-colors hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq->pregunta }}</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 transition-colors group-open:bg-brand-600 group-open:text-white" aria-hidden="true">
                                <svg class="h-4 w-4 transition-transform duration-200 group-open:rotate-45 motion-reduce:transition-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 5v14m7-7H5"/></svg>
                            </span>
                        </summary>
                        <p class="px-5 pb-5 pr-14 text-callout text-ink-soft">{{ $faq->respuesta }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
