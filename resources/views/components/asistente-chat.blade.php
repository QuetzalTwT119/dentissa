<div class="fixed bottom-6 right-4 z-50 flex w-auto max-w-none flex-col items-end gap-2 sm:bottom-6 sm:right-6"
     x-data="{
            abierto: new URLSearchParams(window.location.search).has('asistente_abierto') || false,
            hover: new URLSearchParams(window.location.search).has('asistente_hover') || false,
            enfocado: false,
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
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
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
         }">
    <div x-show="abierto" x-transition x-cloak class="w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-brand-100 bg-brand-50 p-4 shadow-2xl sm:w-[calc(100vw-2.5rem)]">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm border border-white">
                    <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3.6c-1.6-1.2-4.5-1.2-6.4.1C3.2 5.4 3.7 8.4 4.4 10.8c.8 2.8 2.8 8.1 4.5 8.1.8 0 1.1-1.5 1.5-2.9.4-1.3.7-2 1.6-2s1.2.7 1.6 2c.4 1.4.7 2.9 1.5 2.9 1.7 0 3.7-5.3 4.5-8.1.7-2.4 1.2-5.4-1.2-7.1-1.9-1.3-4.8-1.3-6.4-.1Z" fill="white" />
                        <circle cx="9" cy="9.3" r=".9" fill="#B73361" />
                        <circle cx="15" cy="9.3" r=".9" fill="#B73361" />
                        <path d="M9.3 11.4c.7.8 1.6 1.2 2.7 1.2s2-.4 2.7-1.2" stroke="#B73361" stroke-linecap="round" stroke-width="1.2" />
                        <path d="m18.5 2.2.5 1.4 1.4.5-1.4.5-.5 1.4-.5-1.4-1.4-.5 1.4-.5.5-1.4Z" fill="#F8C9D8" />
                    </svg>
                    <span aria-hidden="true" class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-white bg-[#4E8D72]"></span>
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold leading-tight text-brand-700">Dentissa Asistente</h2>
                    <p class="mt-1 text-xs text-gray-600">Aquí para orientarte</p>
                </div>
            </div>
            <button type="button" @click="abierto = false" aria-label="Cerrar asistente" class="rounded-md px-2 py-1 text-xl text-gray-500 hover:bg-white hover:text-gray-800">×</button>
        </div>
        <div x-ref="historial" class="mt-4 max-h-64 space-y-3 overflow-y-auto rounded-lg bg-white p-3">
            <template x-for="(mensaje, indice) in mensajes" :key="indice">
                <div class="flex flex-col" :class="mensaje.autor === 'persona' ? 'items-end' : 'items-start'">
                    <p class="max-w-[88%] whitespace-pre-line rounded-lg px-3 py-2 text-sm" :class="mensaje.autor === 'persona' ? 'bg-brand-500 text-white' : 'bg-gray-50 text-gray-800'" x-text="mensaje.texto"></p>
                    <a x-show="mensaje.accion" :href="mensaje.accion?.url" class="mt-2 inline-block rounded-md bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600" x-text="mensaje.accion?.etiqueta"></a>
                </div>
            </template>
            <p x-show="cargando" class="text-sm text-gray-500">Dentissa Asistente está respondiendo…</p>
        </div>
        <form class="mt-3 flex gap-2" @submit.prevent="preguntar()">
            <label for="consulta-inicio" class="sr-only">Escribe tu pregunta</label>
            <input id="consulta-inicio" type="text" x-model="consulta" placeholder="Ej. Quiero una cita"
                   class="block min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <button type="submit" :disabled="cargando || consulta.trim().length < 3"
                    class="rounded-md bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50">Enviar</button>
        </form>
    </div>

    <button type="button"
            @click="abierto = !abierto"
            @mouseenter="hover = true"
            @mouseleave="hover = false"
            @focus="enfocado = true"
            @blur="enfocado = false"
            :aria-expanded="abierto.toString()"
            aria-label="Abrir o cerrar Dentissa Asistente"
            title="Dentissa Asistente"
            :class="(hover || enfocado || abierto) ? 'w-56 gap-3 px-3.5' : 'w-14 sm:w-16 justify-center p-0'"
            class="group relative inline-flex h-14 sm:h-16 shrink-0 items-center overflow-hidden rounded-full border-2 border-white bg-brand-600 text-white shadow-lg shadow-black/15 transition-all duration-300 ease-out hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
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
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 translate-x-1"
              x-transition:enter-end="opacity-100 translate-x-0"
              class="whitespace-nowrap text-sm font-semibold tracking-wide">Dentissa Asistente</span>
        <span aria-hidden="true" class="absolute top-1 right-1 sm:top-1.5 sm:right-1.5 h-3 w-3 rounded-full border-2 border-white bg-[#4E8D72]"></span>
    </button>
</div>
