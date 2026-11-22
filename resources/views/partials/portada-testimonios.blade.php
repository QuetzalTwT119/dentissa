{{--
    Testimonios en la portada. Antes mostraba tres tarjetas fijas ("Testimonio
    pendiente…") y nunca los aprobados. Ahora: los APROBADOS por la clínica y,
    debajo, el formulario para que el paciente comparta el suyo (entra pendiente;
    TestimonioPublicoController). Se publica con nombre e inicial del apellido.
--}}
<section id="testimonios" class="border-y border-line/80 bg-peach-50/60">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow justify-center">Experiencias de pacientes</p>
            <h2 class="mt-4 text-title-1 text-ink">Sonrisas que hablan por nosotros</h2>
            <p class="mt-4 text-callout text-muted">Opiniones reales de pacientes, publicadas con su autorización y revisadas por la clínica.</p>
        </div>

        @if ($testimonios->isNotEmpty())
            {{-- Mismo carrusel que las especialidades (carruselServicios): tarjetas compactas. --}}
            <div x-data="carruselServicios" class="relative mt-10" role="region" aria-roledescription="carrusel" aria-label="Opiniones de pacientes" data-carrusel-testimonios>
                <button type="button" x-show="posiciones > 1" x-on:click="anterior()" class="carrusel-flecha carrusel-flecha-izq focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Opiniones anteriores">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <div x-ref="pista" x-on:scroll.passive="alDesplazar()" tabindex="0" @class(['carrusel-pista focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400', 'carrusel-pista--pocas' => $testimonios->count() <= 2]) aria-label="Opiniones; desliza para ver más">
                    @foreach ($testimonios as $testimonio)
                        @php
                            $paciente = $testimonio->paciente;
                            $nombre = $paciente ? trim($paciente->nombre.' '.mb_substr((string) $paciente->apellido, 0, 1).($paciente->apellido ? '.' : '')) : 'Paciente';
                            $iniciales = $paciente ? mb_strtoupper(mb_substr($paciente->nombre, 0, 1).mb_substr((string) $paciente->apellido, 0, 1)) : 'P';
                            $estrellas = max(1, min(5, (int) ($testimonio->calificacion ?: 5)));
                        @endphp
                        <figure class="card-boutique flex h-full flex-col p-5 sm:p-6" data-testimonio-publico>
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm tracking-label text-amber-500" aria-label="{{ $estrellas }} de 5 estrellas">{{ str_repeat('★', $estrellas) }}<span class="text-line">{{ str_repeat('★', 5 - $estrellas) }}</span></div>
                                <svg class="h-5 w-5 text-peach-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.6 6C6.5 7.3 4.5 10 4.5 13.4V18h6v-6H7.6c.2-1.9 1.4-3.4 3.2-4.2L9.6 6zm9 0c-3.1 1.3-5.1 4-5.1 7.4V18h6v-6h-2.9c.2-1.9 1.4-3.4 3.2-4.2L18.6 6z"/></svg>
                            </div>
                            <blockquote class="mt-3 flex-1 text-callout italic leading-relaxed text-ink line-clamp-5" title="{{ $testimonio->comentario }}">“{{ $testimonio->comentario }}”</blockquote>
                            <figcaption class="mt-4 flex items-center gap-3 border-t border-line/80 pt-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-peach-100 to-brand-100 text-xs font-semibold text-brand-800" aria-hidden="true">{{ $iniciales }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ink">{{ $nombre }}</span>
                                    <span class="block truncate text-xs text-muted">Paciente de Dentissa</span>
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                <button type="button" x-show="posiciones > 1" x-on:click="siguiente()" class="carrusel-flecha carrusel-flecha-der focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Más opiniones">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div x-show="posiciones > 1" class="mt-5 flex items-center justify-center" aria-hidden="true">
                    <template x-for="i in posiciones" :key="i">
                        <button type="button" tabindex="-1" x-on:click="ir(i - 1)" class="flex h-6 items-center px-1">
                            <span class="h-2 rounded-full transition-[width,background-color,opacity] duration-200 ease-out"
                                  :class="actual === i - 1 ? 'w-[1.375rem] bg-brand-600' : 'w-2 bg-peach-300 opacity-70 hover:opacity-100'"></span>
                        </button>
                    </template>
                </div>
            </div>
        @else
            <p class="mx-auto mt-10 max-w-md text-center text-callout text-muted">Aún no hay opiniones publicadas. ¡Tu experiencia puede ser la primera!</p>
        @endif

        {{-- Formulario: el testimonio entra PENDIENTE hasta que la clínica lo aprueba. --}}
        <div class="mx-auto mt-14 max-w-2xl rounded-3xl border border-line bg-white p-6 shadow-soft sm:p-8" x-data="{ estrellas: {{ (int) old('calificacion', 0) }} }">
            <h3 class="font-serif text-title-3 text-ink">Comparte tu experiencia</h3>
            <p class="mt-1 text-sm text-muted">Si ya te atendimos, cuéntanos cómo te fue. Lo publicaremos después de revisarlo.</p>

            @if (session('testimonio_enviado'))
                <x-alert type="success" :message="session('testimonio_enviado')" class="mt-5" />
            @else
                <form method="POST" action="{{ route('testimonios.publico.store') }}" class="mt-5 space-y-5" data-formulario-testimonio>
                    @csrf
                    {{-- Trampa para robots: invisible para personas. --}}
                    <div class="absolute -left-[9999px]" aria-hidden="true">
                        <label for="sitio_web">No llenes este campo</label>
                        <input id="sitio_web" name="sitio_web" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <fieldset>
                        <legend class="block text-sm font-semibold text-ink">Tu calificación <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span></legend>
                        <div class="mt-1.5 flex gap-1" role="radiogroup">
                            @foreach (range(1, 5) as $n)
                                <label class="cursor-pointer">
                                    <input type="radio" name="calificacion" value="{{ $n }}" class="peer sr-only" x-model.number="estrellas" required @checked((int) old('calificacion') === $n)>
                                    <span class="grid h-11 w-11 place-items-center rounded-full text-2xl transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300"
                                          :class="estrellas >= {{ $n }} ? 'text-amber-500' : 'text-line hover:text-amber-300'" aria-hidden="true">★</span>
                                    <span class="sr-only">{{ $n }} {{ $n === 1 ? 'estrella' : 'estrellas' }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->testimonio->get('calificacion')" class="mt-1" />
                    </fieldset>

                    <div>
                        <x-input-label :required="true" for="comentario" value="Tu experiencia" />
                        <textarea id="comentario" name="comentario" rows="3" minlength="15" maxlength="600" required
                                  class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('comentario') }}</textarea>
                        <x-input-error :messages="$errors->testimonio->get('comentario')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :required="true" for="contacto" value="Teléfono o correo con el que agendaste" />
                        <x-text-input id="contacto" name="contacto" class="mt-1.5 block w-full" :value="old('contacto')" autocomplete="email" required />
                        <p class="mt-1 text-xs text-muted">Solo sirve para confirmar que eres paciente; no se publica.</p>
                        <x-input-error :messages="$errors->testimonio->get('contacto')" class="mt-2" />
                    </div>

                    <label for="autorizacion" class="flex cursor-pointer items-start gap-3 text-sm text-ink-soft">
                        <x-checkbox id="autorizacion" name="autorizacion" value="1" required class="mt-0.5" />
                        <span>Autorizo que se publique mi opinión con mi nombre y la inicial de mi apellido.</span>
                    </label>
                    <x-input-error :messages="$errors->testimonio->get('autorizacion')" class="-mt-3" />

                    <button type="submit" class="btn-brand w-full py-3 sm:w-auto sm:px-8">Enviar mi opinión</button>
                </form>
            @endif
        </div>
    </div>
</section>
