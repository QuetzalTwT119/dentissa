<div class="asistente-flotante fixed bottom-6 right-4 z-50 mb-[env(safe-area-inset-bottom)] flex w-auto max-w-none flex-col items-end gap-2 sm:bottom-6 sm:right-6"
     x-data="{
            abierto: new URLSearchParams(window.location.search).has('asistente_abierto') || false,
            hover: new URLSearchParams(window.location.search).has('asistente_hover') || false,
            enfocado: false,
            menu: false,
            // En celular el botón se esconde al bajar y vuelve al subir, para no
            // tapar botones como Continuar o Agendar cita al final de la página.
            oculto: false,
            ultimoY: 0,
            alDesplazar() {
                const y = window.scrollY;
                if (this.abierto || window.innerWidth >= 640) {
                    this.oculto = false;
                    this.ultimoY = y;
                    return;
                }
                if (Math.abs(y - this.ultimoY) < 8) return;
                this.oculto = y > this.ultimoY && y > 80 && ! this.menu;
                this.ultimoY = y;
            },
            // 26/11/2026: en celular, si una barra de botones marcada con
            // [data-evitar-asistente] (la de Agendar cita) pasa por debajo de la
            // burbuja, la burbuja se esconde mientras estorba.
            tapaBotones: false,
            revisarBotones() {
                if (this.abierto || window.innerWidth >= 640) { this.tapaBotones = false; return; }
                const zona = this.$el.getBoundingClientRect();
                this.tapaBotones = [...document.querySelectorAll('[data-evitar-asistente]')].some((barra) => {
                    const r = barra.getBoundingClientRect();
                    return r.height > 0 && r.top < zona.bottom && r.bottom > zona.top;
                });
            },
            // El desplazamiento suave sigue moviendo la página después del último
            // evento: se revisa otra vez cuando termina.
            esperaRevision: null,
            revisarAlDesplazar() {
                this.revisarBotones();
                clearTimeout(this.esperaRevision);
                this.esperaRevision = setTimeout(() => this.revisarBotones(), 180);
            },
            consulta: '',
            conversacionId: '',
            mensajes: [{ autor: 'bot', texto: 'Hola, soy Dentissa Asistente. Puedo orientarte con información del consultorio o ayudarte a buscar horarios. No doy diagnósticos. ¿Qué necesitas saber?' }],
            cargando: false,
            crearConversacionId() {
                if (typeof window.crypto?.randomUUID === 'function') {
                    return window.crypto.randomUUID();
                }

                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, caracter => {
                    const aleatorio = Math.random() * 16 | 0;
                    const valor = caracter === 'x' ? aleatorio : (aleatorio & 0x3 | 0x8);
                    return valor.toString(16);
                });
            },
            init() {
                try {
                    const guardado = window.localStorage.getItem('dentissa_asistente_conversacion');
                    if (guardado) {
                        this.conversacionId = guardado;
                        return;
                    }
                } catch (error) {
                    // El chat puede seguir funcionando aunque el navegador bloquee el almacenamiento local.
                }

                this.conversacionId = this.crearConversacionId();
                try {
                    window.localStorage.setItem('dentissa_asistente_conversacion', this.conversacionId);
                } catch (error) {
                    // Se conserva el ID solo en memoria para esta visita.
                }
            },
            async preguntar() {
                if (this.consulta.trim().length < 3) return;
                const texto = this.consulta.trim();
                this.mensajes.push({ autor: 'persona', texto });
                this.consulta = '';
                this.cargando = true;
                try {
                    const respuesta = await fetch('{{ route('asistente-paciente.responder') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            // El token viaja con el componente: no depende de que la página incluya la meta csrf-token.
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ consulta: texto, conversacion_id: this.conversacionId }),
                    });
                    const datos = await respuesta.json();
                    this.mensajes.push({ autor: 'bot', texto: datos.respuesta ?? 'No fue posible responder en este momento.', accion: datos.accion ?? null });
                } catch (error) {
                    this.mensajes.push({ autor: 'bot', texto: 'No fue posible responder en este momento. Intenta nuevamente o comunícate con el consultorio.' });
                } finally {
                    this.cargando = false;
                    this.$nextTick(() => this.$refs.historial.scrollTop = this.$refs.historial.scrollHeight);
                }
            },
         }"
     x-init="revisarBotones()"
     @abrir-asistente.window="menu = false; abierto = true; oculto = false"
     @click.outside="menu = false"
     @keydown.escape.window="menu = false"
     @scroll.window.passive="alDesplazar(); revisarAlDesplazar()"
     @resize.window.passive="revisarBotones()"
     @click.window="setTimeout(() => revisarBotones(), 320)">
    <div x-show="abierto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-[0.97]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-out duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-1" x-cloak class="w-[calc(100vw-2rem)] max-w-sm origin-bottom-right overflow-hidden rounded-3xl border border-line bg-white p-4 shadow-lift sm:w-[calc(100vw-2.5rem)]">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-2xs border border-white">
                    <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3.6c-1.6-1.2-4.5-1.2-6.4.1C3.2 5.4 3.7 8.4 4.4 10.8c.8 2.8 2.8 8.1 4.5 8.1.8 0 1.1-1.5 1.5-2.9.4-1.3.7-2 1.6-2s1.2.7 1.6 2c.4 1.4.7 2.9 1.5 2.9 1.7 0 3.7-5.3 4.5-8.1.7-2.4 1.2-5.4-1.2-7.1-1.9-1.3-4.8-1.3-6.4-.1Z" fill="white" />
                        <circle cx="9" cy="9.3" r=".9" fill="#B73361" />
                        <circle cx="15" cy="9.3" r=".9" fill="#B73361" />
                        <path d="M9.3 11.4c.7.8 1.6 1.2 2.7 1.2s2-.4 2.7-1.2" stroke="#B73361" stroke-linecap="round" stroke-width="1.2" />
                        <path d="m18.5 2.2.5 1.4 1.4.5-1.4.5-.5 1.4-.5-1.4-1.4-.5 1.4-.5.5-1.4Z" fill="#F8C9D8" />
                    </svg>
                    <span aria-hidden="true" class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-white bg-sage-500"></span>
                </span>
                <div class="min-w-0">
                    {{-- 26/11/2026: <p> y no <h2>: el panel va antes del <main> y quedaba como primer título de la página. --}}
                    <p class="font-serif text-title-3 font-medium leading-tight tracking-[-0.015em] text-ink">Dentissa Asistente</p>
                    <p class="mt-0.5 text-xs text-muted">Aquí para orientarte</p>
                </div>
            </div>
            <button type="button" @click="abierto = false" aria-label="Cerrar asistente" class="flex h-11 w-11 items-center justify-center rounded-full text-xl text-muted transition-colors hover:bg-cream hover:text-ink">×</button>
        </div>
        <div x-ref="historial" class="mt-4 max-h-[min(18rem,50dvh)] space-y-3 overflow-y-auto overscroll-contain rounded-2xl bg-cream p-3">
            <template x-for="(mensaje, indice) in mensajes" :key="indice">
                <div class="flex flex-col" :class="mensaje.autor === 'persona' ? 'items-end' : 'items-start'">
                    <p class="max-w-[88%] whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm leading-5" :class="mensaje.autor === 'persona' ? 'rounded-br-md bg-brand-600 text-white' : 'rounded-bl-md bg-white text-ink ring-1 ring-inset ring-line'" x-text="mensaje.texto"></p>
                    <a x-show="mensaje.accion" :href="mensaje.accion?.url" class="mt-2 inline-flex min-h-11 items-center rounded-full bg-brand-600 px-4 text-xs font-semibold text-white transition-colors hover:bg-brand-700" x-text="mensaje.accion?.etiqueta"></a>
                </div>
            </template>
            <p x-show="cargando" class="text-sm text-muted">Dentissa Asistente está respondiendo…</p>
        </div>
        <form class="mt-3 flex gap-2" @submit.prevent="preguntar()">
            <label for="consulta-inicio" class="sr-only">Escribe tu pregunta</label>
            <input id="consulta-inicio" type="text" x-model="consulta" placeholder="Ej. Quiero una cita"
                   class="block min-h-11 min-w-0 flex-1 rounded-full border-field px-4 text-sm shadow-2xs focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15">
            <button type="submit" :disabled="cargando || consulta.trim().length < 3"
                    class="inline-flex min-h-11 items-center rounded-full bg-brand-600 px-5 text-sm font-semibold text-white transition-[background-color,transform] duration-150 hover:bg-brand-700 active:scale-[0.97] disabled:opacity-50">Enviar</button>
        </form>
    </div>

    {{-- 27/11/2026: un solo botón flotante (antes WhatsApp y el asistente, uno sobre otro).
         Al tocarlo se abren las tres formas de contacto. --}}
    @php($contactoAyuda = config('dentissa.contacto'))
    <div x-show="menu && ! abierto" x-cloak
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-out duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         id="menu-ayuda" class="w-60 origin-bottom-right overflow-hidden rounded-2xl border border-line bg-white p-1.5 shadow-lift" data-menu-ayuda>
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $contactoAyuda['whatsapp']) }}?text={{ rawurlencode('Hola, quisiera información para agendar una cita en el consultorio.') }}"
           target="_blank" rel="noopener noreferrer" @click="menu = false"
           class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-sm font-semibold text-ink transition-colors hover:bg-cream">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.05 2a9.9 9.9 0 0 0-8.53 14.92L2 22l5.22-1.37A9.9 9.9 0 1 0 12.05 2zm5.8 13.97c-.25.7-1.43 1.33-2 1.41-.51.07-1.16.1-1.87-.12-.43-.14-.98-.32-1.69-.63-2.98-1.29-4.93-4.29-5.08-4.49-.14-.2-1.21-1.62-1.21-3.08s.77-2.18 1.04-2.48c.27-.3.59-.37.79-.37h.57c.18.01.43-.07.67.51.25.59.84 2.05.92 2.2.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.32.38-.45.52-.15.14-.3.3-.13.6.17.3.77 1.28 1.66 2.07 1.14 1.02 2.1 1.33 2.4 1.48.3.15.47.13.64-.07.17-.19.74-.86.94-1.16.2-.3.4-.25.67-.15.27.1 1.73.82 2.03.97.3.15.5.22.57.35.08.12.08.71-.17 1.41z"/></svg>
            </span>
            Escribir por WhatsApp
            <span class="sr-only">(se abre en otra pestaña)</span>
        </a>
        <a href="{{ $contactoAyuda['telefono_enlace'] }}" @click="menu = false"
           class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-sm font-semibold text-ink transition-colors hover:bg-cream">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5.5A2.5 2.5 0 0 1 5.5 3h1.6a1 1 0 0 1 .95.68l1.2 3.6a1 1 0 0 1-.5 1.2l-1.8.9a11 11 0 0 0 5.6 5.6l.9-1.8a1 1 0 0 1 1.2-.5l3.6 1.2a1 1 0 0 1 .68.95v1.6A2.5 2.5 0 0 1 18.5 21h-.5C9.7 21 3 14.3 3 6v-.5z"/></svg>
            </span>
            <span>Llamar <span class="block text-xs font-normal text-muted tabular-nums">{{ $contactoAyuda['telefono'] }}</span></span>
        </a>
        <button type="button" @click="menu = false; abierto = true"
                class="flex min-h-12 w-full items-center gap-3 rounded-xl px-3 text-left text-sm font-semibold text-ink transition-colors hover:bg-cream">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5m-9 6 2.6-2.6A2 2 0 0 1 8 17h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14z"/></svg>
            </span>
            Preguntar al asistente
        </button>
    </div>

    <button type="button"
            @click="abierto ? (abierto = false) : (menu = ! menu)"
            @mouseenter="hover = true"
            @mouseleave="hover = false"
            @focus="enfocado = true; oculto = false"
            @blur="enfocado = false"
            :aria-expanded="(menu || abierto).toString()"
            aria-controls="menu-ayuda"
            aria-label="¿Necesitas ayuda? WhatsApp, llamar o asistente"
            title="¿Necesitas ayuda?"
            data-boton-ayuda
            :data-oculto="oculto.toString()"
            :class="[(hover || enfocado || abierto || menu) ? 'w-56 gap-3 px-3.5' : 'w-14 sm:w-16 justify-center p-0', (oculto || tapaBotones) ? 'translate-y-24 opacity-0 pointer-events-none' : '']"
            class="group relative inline-flex h-14 sm:h-16 shrink-0 items-center overflow-hidden rounded-full border-2 border-white bg-brand-600 text-white shadow-lg shadow-black/15 transition-[background-color,transform,opacity] duration-200 ease-out motion-reduce:transition-none hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
        <span class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center">
            <svg aria-hidden="true" class="h-9 w-9 sm:h-10 sm:w-10" viewBox="0 0 24 24" fill="none">
                <path d="M12 3.6c-1.6-1.2-4.5-1.2-6.4.1C3.2 5.4 3.7 8.4 4.4 10.8c.8 2.8 2.8 8.1 4.5 8.1.8 0 1.1-1.5 1.5-2.9.4-1.3.7-2 1.6-2s1.2.7 1.6 2c.4 1.4.7 2.9 1.5 2.9 1.7 0 3.7-5.3 4.5-8.1.7-2.4 1.2-5.4-1.2-7.1-1.9-1.3-4.8-1.3-6.4-.1Z" fill="white" />
                <circle cx="9" cy="9.3" r=".9" fill="#B73361" />
                <circle cx="15" cy="9.3" r=".9" fill="#B73361" />
                <path d="M9.3 11.4c.7.8 1.6 1.2 2.7 1.2s2-.4 2.7-1.2" stroke="#B73361" stroke-linecap="round" stroke-width="1.2" />
                <path d="m18.5 2.2.5 1.4 1.4.5-1.4.5-.5 1.4-.5-1.4-1.4-.5 1.4-.5.5-1.4Z" fill="#F8C9D8" />
            </svg>
        </span>
        <span x-show="hover || enfocado || abierto || menu"
              x-transition:enter="transition-opacity ease-out duration-150"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              class="whitespace-nowrap text-sm font-semibold tracking-wide" x-text="abierto ? 'Cerrar asistente' : (menu ? 'Cerrar' : '¿Necesitas ayuda?')">¿Necesitas ayuda?</span>
        <span aria-hidden="true" class="absolute top-1 right-1 sm:top-1.5 sm:right-1.5 h-3 w-3 rounded-full border-2 border-white bg-sage-500"></span>
    </button>
</div>
