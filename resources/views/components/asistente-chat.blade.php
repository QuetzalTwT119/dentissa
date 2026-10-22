<div class="fixed bottom-6 right-4 z-50 flex w-auto max-w-none flex-col items-end gap-2 sm:bottom-5 sm:right-5"
     :style="`bottom: ${desplazamientoBoton}px`"
     x-data="{
            abierto: false,
            consulta: '',
            conversacionId: '',
            desplazamientoBoton: window.innerWidth <= 639 ? 24 : 20,
            observadorZona: null,
            zonasIntersectadas: new Set(),
            manejadorPosicion: null,
            framePosicion: null,
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
                this.manejadorPosicion = () => this.recalcularPosicion();
                window.addEventListener('scroll', this.manejadorPosicion, { passive: true });
                window.addEventListener('resize', this.manejadorPosicion, { passive: true });

                if ('IntersectionObserver' in window) {
                    const zonasReservadas = document.querySelectorAll('[data-asistente-zona-reservada]');
                    if (zonasReservadas.length) {
                        this.observadorZona = new IntersectionObserver((entradas) => {
                            entradas.forEach(entrada => {
                                if (entrada.isIntersecting) {
                                    this.zonasIntersectadas.add(entrada.target);
                                } else {
                                    this.zonasIntersectadas.delete(entrada.target);
                                }
                            });
                            this.recalcularPosicion();
                        }, { threshold: 0 });
                        zonasReservadas.forEach(zona => this.observadorZona.observe(zona));
                    }
                }

                this.$nextTick(() => this.recalcularPosicion());

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
            destroy() {
                this.observadorZona?.disconnect();
                if (this.manejadorPosicion) {
                    window.removeEventListener('scroll', this.manejadorPosicion);
                    window.removeEventListener('resize', this.manejadorPosicion);
                }
                if (this.framePosicion) {
                    window.cancelAnimationFrame(this.framePosicion);
                }
            },
            recalcularPosicion() {
                if (this.framePosicion) {
                    window.cancelAnimationFrame(this.framePosicion);
                }

                this.framePosicion = window.requestAnimationFrame(() => {
                    const boton = this.$refs.botonAsistente;
                    if (!boton) return;

                    const espacioBase = window.innerWidth <= 639 ? 24 : 20;
                    const botonRect = boton.getBoundingClientRect();

                    let botonTop = window.innerHeight - espacioBase - botonRect.height;
                    const zonasVisibles = [...this.zonasIntersectadas]
                        .map(zona => zona.getBoundingClientRect())
                        .filter(zonaRect => zonaRect.left < botonRect.right && zonaRect.right > botonRect.left);

                    // Si al subir para evitar un campo el botón encuentra otro,
                    // continúa desplazándolo hasta quedar fuera de todos.
                    for (let iteracion = 0; iteracion < zonasVisibles.length; iteracion++) {
                        const zonaEnConflicto = zonasVisibles.find(zonaRect =>
                            zonaRect.top < botonTop + botonRect.height && zonaRect.bottom > botonTop
                        );

                        if (!zonaEnConflicto) break;

                        botonTop = Math.max(8, zonaEnConflicto.top - botonRect.height - 16);
                    }

                    this.desplazamientoBoton = window.innerHeight - botonTop;
                });
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
                <div>
                    <h2 class="text-lg font-semibold text-brand-700">Dentissa Asistente</h2>
                    <p class="mt-1 text-sm text-gray-700">Dudas del consultorio y consulta de horarios disponibles.</p>
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
    <button x-ref="botonAsistente" type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()" aria-label="Abrir o cerrar Dentissa Asistente" title="Dentissa Asistente"
            class="group inline-flex items-center gap-2 rounded-full border border-brand-100 bg-white py-1.5 pl-1.5 pr-4 text-sm font-semibold text-brand-700 shadow-lg shadow-brand-100/70 transition hover:border-brand-200 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-300">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm transition group-hover:bg-brand-700">
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3.25c-2.25-1.1-5.18-.1-6.55 1.83-1.47 2.08-.6 5.6.2 7.87.74 2.1 1.68 4.4 2.93 6.06.35.46.74.69 1.14.69.9 0 1.31-1.11 1.65-2.32.28-1.03.55-2.13 1.04-2.13.49 0 .76 1.1 1.04 2.13.34 1.21.75 2.32 1.65 2.32.4 0 .79-.23 1.14-.69 1.25-1.66 2.19-3.96 2.93-6.06.8-2.27 1.67-5.79.2-7.87C17.18 3.15 14.25 2.15 12 3.25Z" />
            </svg>
        </span>
        <span class="whitespace-nowrap leading-none">Dentissa Asistente</span>
    </button>
</div>
