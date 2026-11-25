{{--
    Registrar pago (24/11/2026): cuánto, cómo y cuándo. Si viene de una cita,
    llega elegida y con su precio estimado como monto sugerido.
--}}
@use('App\Models\Pago')
@php
    $montoSugerido = $citaElegida?->precioEstimado();
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.pacientes.historial.index', $paciente)" volver-texto="Volver al expediente"
                        title="Registrar pago — {{ $paciente->nombre_completo }}" subtitle="Lo que se cobró en el consultorio." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.pagos.store') }}" class="formulario-panel formulario-una-columna space-y-6"
                      data-confirmar="Quedará en el expediente y en los ingresos." data-confirmar-titulo="¿Registrar el pago?" data-confirmar-boton="Sí, registrar" data-confirmar-tono="normal" data-registrar-pago>
                    @csrf
                    <input type="hidden" name="id_paciente" value="{{ $paciente->id }}">
                    <x-form-obligatorios />

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div>
                            <x-input-label :required="true" for="monto" value="Monto (MXN)" />
                            <x-text-input id="monto" name="monto" type="number" min="1" step="0.01" inputmode="decimal" class="mt-1.5 block w-full" required
                                          :value="old('monto', $montoSugerido ? rtrim(rtrim(number_format($montoSugerido, 2, '.', ''), '0'), '.') : '')" />
                            @if ($montoSugerido)
                                <p class="mt-1 text-xs text-muted">Precio estimado de la cita: {{ Pago::dinero($montoSugerido) }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('monto')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label :required="true" for="metodo" value="Forma de pago" />
                            <x-select id="metodo" name="metodo" required class="mt-1.5">
                                @foreach (Pago::METODOS as $clave => $texto)
                                    <option value="{{ $clave }}" @selected(old('metodo', 'efectivo') === $clave)>{{ $texto }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('metodo')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label :required="true" for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required :max="now()->toDateString()" :value="old('fecha', now()->toDateString())" />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="id_cita" value="Cita (opcional)" />
                        <x-select id="id_cita" name="id_cita" class="mt-1.5">
                            <option value="">Sin cita (anticipo, abono, otro)</option>
                            @foreach ($citas as $cita)
                                <option value="{{ $cita->id }}" @selected((string) old('id_cita', $citaElegida?->id) === (string) $cita->id)>
                                    {{ $cita->fecha_hora->translatedFormat('j M Y, H:i') }} · {{ $cita->tratamiento?->nombre ?? 'Consulta' }}{{ $cita->precioEstimado() !== null ? ' · '.Pago::dinero($cita->precioEstimado()) : '' }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('id_cita')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="concepto" value="Concepto (opcional)" />
                        <x-text-input id="concepto" name="concepto" maxlength="150" class="mt-1.5 block w-full" :value="old('concepto')" placeholder="Anticipo de ortodoncia, abono…" />
                        <x-input-error :messages="$errors->get('concepto')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 border-t border-line pt-4">
                        <x-primary-button>Registrar pago</x-primary-button>
                        <x-boton-cancelar href="{{ route('admin.pacientes.historial.index', $paciente) }}" />
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
