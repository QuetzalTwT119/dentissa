<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Reasignación de Agenda"
            title="Reagendar cita"
            subtitle="Selecciona una nueva fecha y horario disponible para esta consulta."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- En PC: la ficha de la cita a la izquierda y el calendario a la derecha. --}}
            <x-admin.card class="p-5 sm:p-8 lg:grid lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start lg:gap-6 lg:p-6">
                <!-- Ficha informativa de la cita actual -->
                <div class="mb-6 lg:mb-0 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/40 via-white to-peach-50/30 p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 border border-brand-200 shadow-xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-label text-brand-600">Cita a reasignar</p>
                            <h3 class="text-headline font-semibold text-ink">{{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}</h3>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3 pt-3 border-t border-brand-100/60 text-xs text-ink">
                        <div>
                            <span class="font-semibold text-ink">Servicio:</span>
                            <span>{{ $cita->servicio->nombre ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-ink">Tratamiento:</span>
                            <span>{{ $cita->tratamiento->nombre ?? '—' }}</span>
                            <x-cita-oferta :cita="$cita" class="ml-1" />
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('citas.update', $cita) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los cambios de la cita?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" class="formulario-panel space-y-6"
                      x-data="{
                        idTratamiento: {{ $cita->id_tratamiento }},
                        fecha: @js(old('fecha', \Illuminate\Support\Carbon::parse($cita->fecha_hora)->toDateString())),
                        hora: @js(old('hora', null)),
                        horarios: [],
                        loadingHorarios: false,
                        async cargarHorarios() {
                            this.hora = null;
                            if (!this.idTratamiento || !this.fecha) { this.horarios = []; return; }
                            this.loadingHorarios = true;
                            this.horarios = await window.cargarHorariosDisponibles(this.idTratamiento, this.fecha, {{ $cita->id }});
                            this.loadingHorarios = false;
                        },
                      }"
                      x-init="cargarHorarios()"
                >
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label value="Nueva fecha y horario" class="mb-3" />
                        <input type="hidden" name="fecha" x-model="fecha">
                        <input type="hidden" name="hora" x-model="hora">
                        @include('partials.disponibilidad-picker', ['excluirCitaId' => $cita->id])
                        <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button x-bind:disabled="!hora">Guardar nuevo horario</x-primary-button>

                        <a href="{{ route('citas.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
