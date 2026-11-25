{{--
    Días u horas bloqueados (24/11/2026): vacaciones, un imprevisto o mantenimiento.
    Mientras dure el bloqueo, esos horarios no se ofrecen en la agenda en línea ni
    en Nueva cita del panel. Sin citas activas en ese tiempo (el controlador lo revisa).
--}}
@php
    $todoElDiaInicial = old('todo_el_dia', old('fecha_inicio') === null ? '1' : null) ? 'true' : 'false';
@endphp
<section id="bloqueos" aria-labelledby="titulo-bloqueos" class="scroll-mt-24" data-bloqueos>
    <h2 id="titulo-bloqueos" class="text-title-3 text-ink">Días u horas bloqueados</h2>
    <p class="mt-1 text-sm text-muted">Para vacaciones, un imprevisto o mantenimiento. Esos horarios dejan de ofrecerse en la agenda en línea y en el panel.</p>

    <div class="mt-3 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:items-start">
        {{-- Nuevo bloqueo --}}
        <x-admin.card class="p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-ink">Bloquear</h3>
            <form method="POST" action="{{ route('admin.bloqueos.store') }}" class="mt-4 space-y-4"
                  x-data="{ todoElDia: {{ $todoElDiaInicial }}, inicio: @js(old('fecha_inicio', '')) }"
                  data-confirmar="Esos horarios dejarán de ofrecerse a los pacientes." data-confirmar-titulo="¿Bloquear este tiempo?" data-confirmar-boton="Sí, bloquear" data-confirmar-tono="normal">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :required="true" for="bloqueo_fecha_inicio" value="Desde" />
                        <x-text-input id="bloqueo_fecha_inicio" name="fecha_inicio" type="date" class="mt-1.5 block w-full" required
                                      :min="now()->toDateString()" :value="old('fecha_inicio')" x-model="inicio" />
                        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                    </div>
                    <div x-show="todoElDia">
                        <x-input-label for="bloqueo_fecha_fin" value="Hasta (opcional)" />
                        <x-text-input id="bloqueo_fecha_fin" name="fecha_fin" type="date" class="mt-1.5 block w-full"
                                      :value="old('fecha_fin')" x-bind:min="inicio || '{{ now()->toDateString() }}'" x-bind:disabled="! todoElDia" />
                        <p class="mt-1 text-xs text-muted">Vacío = solo ese día.</p>
                        <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
                    </div>
                </div>

                {{-- Interruptor como el de Horarios de atención. --}}
                <label class="inline-flex min-h-11 w-fit cursor-pointer items-center gap-3">
                    <input type="checkbox" name="todo_el_dia" value="1" role="switch" class="peer sr-only" x-model="todoElDia">
                    <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-line transition-colors duration-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 after:ease-out peer-checked:bg-sage-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300 motion-reduce:after:transition-none"></span>
                    <span class="text-sm font-medium text-ink">Todo el día</span>
                </label>

                <div x-show="! todoElDia" x-cloak class="grid grid-cols-2 gap-4" data-bloqueo-horas>
                    <div>
                        <x-input-label :required="true" for="bloqueo_hora_inicio" value="De" />
                        <x-text-input id="bloqueo_hora_inicio" name="hora_inicio" type="time" step="1800" class="mt-1.5 block w-full"
                                      :value="old('hora_inicio')" x-bind:disabled="todoElDia" />
                        <x-input-error :messages="$errors->get('hora_inicio')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :required="true" for="bloqueo_hora_fin" value="A" />
                        <x-text-input id="bloqueo_hora_fin" name="hora_fin" type="time" step="1800" class="mt-1.5 block w-full"
                                      :value="old('hora_fin')" x-bind:disabled="todoElDia" />
                        <x-input-error :messages="$errors->get('hora_fin')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label :required="true" for="bloqueo_motivo" value="Motivo" />
                    <x-text-input id="bloqueo_motivo" name="motivo" type="text" maxlength="120" class="mt-1.5 block w-full" required
                                  placeholder="Vacaciones, curso, mantenimiento…" :value="old('motivo')" />
                    <p class="mt-1 text-xs text-muted">Solo lo ve el personal.</p>
                    <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                </div>

                <div class="flex justify-end border-t border-line pt-4">
                    <x-primary-button class="w-full justify-center sm:w-auto">Bloquear</x-primary-button>
                </div>
            </form>
        </x-admin.card>

        {{-- Bloqueos que aún no terminan --}}
        <x-admin.card>
            <h3 class="border-b border-line px-5 py-4 text-sm font-semibold text-ink sm:px-6">Próximos bloqueos
                @if ($bloqueos->total() > 0)<span class="font-normal text-muted">({{ $bloqueos->total() }})</span>@endif
            </h3>
            @if ($bloqueos->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted sm:px-6" data-sin-bloqueos>No hay días ni horas bloqueados.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($bloqueos as $bloqueo)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6" data-bloqueo>
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $bloqueo->descripcion() }}</p>
                                <p class="mt-0.5 break-words text-sm text-ink-soft">{{ $bloqueo->motivo }}</p>
                                @if ($bloqueo->creadoPor)
                                    <p class="mt-0.5 text-xs text-muted">Lo registró {{ $bloqueo->creadoPor->nombre }} {{ $bloqueo->creadoPor->apellido }}</p>
                                @elseif ($bloqueo->esFestivo())
                                    <p class="mt-0.5 text-xs text-muted">Festivo puesto por el sistema. Si vas a trabajar ese día, quítalo con 🗑 y no se volverá a poner.</p>
                                @endif
                            </div>
                            <x-admin.acciones-fila :eliminar="route('admin.bloqueos.destroy', $bloqueo)" que="el bloqueo" :nombre="$bloqueo->descripcion()"
                                                   confirmar-titulo="¿Quitar el bloqueo del {{ $bloqueo->descripcion() }}?" />
                        </li>
                    @endforeach
                </ul>
                {{-- 25/11/2026: paginados de 5 en 5. --}}
                <x-pagination-summary :items="$bloqueos" label="bloqueos" posicion="abajo" data-paginacion-bloqueos />
            @endif
        </x-admin.card>
    </div>
</section>
