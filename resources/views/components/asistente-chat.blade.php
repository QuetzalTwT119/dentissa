<div class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3"
     x-data="{
            abierto: false,
            consulta: '',
            conversacionId: localStorage.getItem('dentissa_asistente_conversacion') ?? crypto.randomUUID(),
            mensajes: [{ autor: 'bot', texto: 'Hola, soy Dentissa Asistente. Puedo responder preguntas aprobadas o ayudarte a buscar horarios. Escribe, por ejemplo: Quiero una cita.' }],
            cargando: false,
            init() { localStorage.setItem('dentissa_asistente_conversacion', this.conversacionId); },
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
    <div x-show="abierto" x-transition x-cloak class="w-[calc(100vw-2.5rem)] max-w-md overflow-hidden rounded-2xl border border-brand-100 bg-brand-50 p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-brand-700">Dentissa Asistente</h2>
                    <p class="mt-1 text-sm text-gray-700">Dudas, horarios, urgencias y lista de espera.</p>
                </div>
                <button type="button" @click="abierto = false" aria-label="Cerrar asistente" class="rounded-md px-2 py-1 text-xl text-gray-500 hover:bg-white hover:text-gray-800">×</button>
            </div>
            <div x-ref="historial" class="mt-5 max-h-72 space-y-3 overflow-y-auto rounded-lg bg-white p-3">
                <template x-for="(mensaje, indice) in mensajes" :key="indice">
                    <div class="flex flex-col" :class="mensaje.autor === 'persona' ? 'items-end' : 'items-start'">
                        <p class="max-w-[88%] whitespace-pre-line rounded-lg px-3 py-2 text-sm" :class="mensaje.autor === 'persona' ? 'bg-brand-500 text-white' : 'bg-gray-50 text-gray-800'" x-text="mensaje.texto"></p>
                        <a x-show="mensaje.accion" :href="mensaje.accion?.url" class="mt-2 inline-block rounded-md bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600" x-text="mensaje.accion?.etiqueta"></a>
                    </div>
                </template>
                <p x-show="cargando" class="text-sm text-gray-500">Dentissa Asistente está respondiendo…</p>
            </div>
            <form class="mt-4 flex gap-2" @submit.prevent="preguntar()">
                <label for="consulta-inicio" class="sr-only">Escribe tu pregunta</label>
                <input id="consulta-inicio" type="text" x-model="consulta" placeholder="Ej. Quiero una cita"
                       class="block min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" :disabled="cargando || consulta.trim().length < 3"
                        class="rounded-md bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50">Enviar</button>
            </form>
    </div>
    <button type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()"
            class="flex h-14 items-center gap-2 rounded-full bg-brand-600 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-300 transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300">
        <span class="text-xl" x-text="abierto ? '×' : '💬'"></span>
        <span x-text="abierto ? 'Cerrar' : 'Dentissa Asistente'"></span>
    </button>
</div>
