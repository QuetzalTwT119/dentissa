<div class="asistente-flotante fixed bottom-6 right-4 z-50 mb-[env(safe-area-inset-bottom)] flex w-auto max-w-none flex-col items-end gap-2 sm:bottom-6 sm:right-6"
     x-data="{
            abierto: new URLSearchParams(window.location.search).has('asistente_abierto') || false,
            hover: new URLSearchParams(window.location.search).has('asistente_hover') || false,
            enfocado: false,
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
                this.oculto = y > this.ultimoY && y > 80;
                this.ultimoY = y;
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
     @scroll.window.passive="alDesplazar()">
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

    <button type="button"
            @click="abierto = !abierto"
            @mouseenter="hover = true"
            @mouseleave="hover = false"
            @focus="enfocado = true; oculto = false"
            @blur="enfocado = false"
            :aria-expanded="abierto.toString()"
            aria-label="Abrir o cerrar Dentissa Asistente"
            title="Dentissa Asistente"
            :data-oculto="oculto.toString()"
            :class="[(hover || enfocado || abierto) ? 'w-56 gap-3 px-3.5' : 'w-14 sm:w-16 justify-center p-0', oculto ? 'translate-y-24 opacity-0 pointer-events-none' : '']"
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
        <span x-show="hover || enfocado || abierto"
              x-transition:enter="transition-opacity ease-out duration-150"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              class="whitespace-nowrap text-sm font-semibold tracking-wide">Dentissa Asistente</span>
        <span aria-hidden="true" class="absolute top-1 right-1 sm:top-1.5 sm:right-1.5 h-3 w-3 rounded-full border-2 border-white bg-sage-500"></span>
    </button>
</div>
