<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Reasignación de Agenda
                    </span>
                </div>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#2D2526]">
                    Reagendar cita
                </h2>
                <p class="mt-0.5 text-xs text-[#8A7A7D]">Selecciona una nueva fecha y horario disponible para esta consulta.</p>
            </div>
            <a href="{{ route('citas.index') }}" class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-[#e9dfdb] bg-white px-4 py-2 text-xs font-semibold text-[#403539] transition hover:bg-[#FAF6F6] hover:border-brand-300 hover:text-brand-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la agenda
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <!-- Ficha informativa de la cita actual -->
                <div class="mb-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/40 via-white to-peach-50/30 p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 border border-brand-200 shadow-xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Cita a reasignar</p>
                            <h3 class="text-base font-bold text-[#2D2526]">{{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}</h3>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-brand-100/60 text-xs text-[#403539]">
                        <div>
                            <span class="font-bold text-[#2D2526]">Servicio:</span> 
                            <span>{{ $cita->servicio->nombre ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="font-bold text-[#2D2526]">Tratamiento:</span> 
                            <span>{{ $cita->tratamiento->nombre ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('citas.update', $cita) }}" class="space-y-6"
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
                        <x-input-label for="fecha" value="Nueva fecha" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required
                                      x-model="fecha" @change="cargarHorarios()" />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Nuevo horario disponible" class="mb-2" />
                        <input type="hidden" name="hora" x-model="hora">
                        @include('partials.disponibilidad-picker')
                        <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button x-bind:disabled="!hora">Guardar nuevo horario</x-primary-button>

                        <a href="{{ route('citas.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
