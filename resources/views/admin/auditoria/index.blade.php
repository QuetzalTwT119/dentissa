@php
$etiquetasAccion = [
    'cita.creada' => 'Cita creada',
    'cita.reagendada' => 'Cita reagendada',
    'cita.cancelada' => 'Cita cancelada',
    'cita.confirmada' => 'Cita confirmada',
    'cita.iniciada' => 'Atención iniciada',
    'cita.no_asistio' => 'Inasistencia registrada',
    'cita.completada' => 'Cita completada',
    'historial.creado' => 'Consulta clínica creada',
    'historial.actualizado' => 'Consulta clínica actualizada',
    'historial.eliminado' => 'Consulta clínica eliminada',
    'historial.imagen_agregada' => 'Archivo clínico agregado',
    'historial.imagen_eliminada' => 'Archivo clínico eliminado',
    'paciente.datos_oficiales_actualizados' => 'Datos oficiales del paciente actualizados',
    'paciente.datos_actualizados' => 'Datos del paciente actualizados',
    'urgencia.contactada' => 'Solicitud urgente contactada',
    'lista_espera.contactada' => 'Paciente de lista de espera contactado',
    'usuario.creado' => 'Usuario creado',
    'usuario.actualizado' => 'Usuario actualizado',
    'usuario.activado' => 'Cuenta activada',
    'usuario.desactivado' => 'Cuenta desactivada',
    'usuario.password_actualizada' => 'Contraseña actualizada',
    'usuario.cuenta_eliminada' => 'Cuenta eliminada por su titular',
    'perfil.actualizado' => 'Perfil actualizado',
    'servicio.creado' => 'Servicio creado',
    'servicio.actualizado' => 'Servicio actualizado',
    'servicio.eliminado' => 'Servicio eliminado',
    'servicio.tratamientos_actualizados' => 'Tratamientos de servicio actualizados',
    'tratamiento.creado' => 'Tratamiento creado',
    'tratamiento.actualizado' => 'Tratamiento actualizado',
    'tratamiento.eliminado' => 'Tratamiento eliminado',
    'oferta.creada' => 'Oferta creada',
    'oferta.actualizada' => 'Oferta actualizada',
    'oferta.eliminada' => 'Oferta eliminada',
    'galeria.imagen_agregada' => 'Imagen agregada a galería',
    'galeria.imagen_actualizada' => 'Imagen de galería actualizada',
    'galeria.imagen_eliminada' => 'Imagen de galería eliminada',
    'caso.creado' => 'Caso clínico publicado',
    'caso.actualizado' => 'Caso clínico actualizado',
    'caso.eliminado' => 'Caso clínico eliminado',
    'caso.imagen_agregada' => 'Imagen agregada a caso clínico',
    'caso.imagen_eliminada' => 'Imagen eliminada de caso clínico',
    'testimonio.creado' => 'Testimonio registrado',
    'testimonio.actualizado' => 'Testimonio actualizado',
    'testimonio.aprobado' => 'Testimonio aprobado',
    'testimonio.rechazado' => 'Testimonio rechazado',
    'testimonio.eliminado' => 'Testimonio eliminado',
    'faq.creada' => 'Pregunta frecuente creada',
    'faq.actualizada' => 'Pregunta frecuente actualizada',
    'faq.eliminada' => 'Pregunta frecuente eliminada',
    'horario.actualizado' => 'Horario de atención actualizado',
    'mensaje.marco_leido' => 'Mensaje marcado como leído',
    'mensaje.marco_no_leido' => 'Mensaje marcado como no leído',
    'mensaje.eliminado' => 'Mensaje de contacto eliminado',
];
$etiquetasTabla = [
    'citas' => 'Cita',
    'historial_clinico' => 'Consulta clínica',
    'imagenes' => 'Archivo clínico',
    'pacientes' => 'Paciente',
    'solicitudes_urgencia' => 'Solicitud urgente',
    'lista_espera' => 'Lista de espera',
    'users' => 'Usuario',
    'servicios' => 'Servicio',
    'tratamientos' => 'Tratamiento',
    'ofertas' => 'Oferta',
    'casos' => 'Caso clínico',
    'testimonios' => 'Testimonio',
    'preguntas_frecuentes' => 'Pregunta frecuente',
    'horarios_atencion' => 'Horario',
    'mensajes_contacto' => 'Mensaje de contacto',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">Auditoría</h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Trazabilidad de seguridad, cambios en datos clínicos y actividad del personal.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft" aria-labelledby="auditoria-intro">
                <h3 id="auditoria-intro" class="text-base font-semibold text-[#2D2526]">Actividad registrada</h3>
                <p class="mt-2 text-sm leading-relaxed text-[#6b5e62]">
                    Consulta de solo lectura para administración. Solo se registran acciones hechas por cuentas
                    del administrador o de las asistentes. Las solicitudes iniciadas por pacientes en el portal o Telegram no se
                    muestran aquí. Los detalles no incluyen contraseñas ni notas clínicas.
                </p>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft" aria-label="Filtros de auditoría">
                <form method="GET" action="{{ route('admin.auditoria.index') }}" class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    <div>
                        <x-input-label for="filtro_desde" value="Desde" />
                        <input id="filtro_desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                               class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <x-input-label for="filtro_hasta" value="Hasta" />
                        <input id="filtro_hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                               class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <x-input-label for="filtro_usuario" value="Usuario" />
                        <select id="filtro_usuario" name="usuario_id"
                                class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" @selected((string) ($filtros['usuario_id'] ?? '') === (string) $usuario->id)>
                                    {{ $usuario->nombre }} {{ $usuario->apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filtro_accion" value="Acción" />
                        <select id="filtro_accion" name="accion"
                                class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" @selected(($filtros['accion'] ?? '') === $accion)>
                                    {{ $etiquetasAccion[$accion] ?? str_replace('.', ' · ', ucfirst($accion)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button>Filtrar</x-primary-button>
                        <a href="{{ route('admin.auditoria.index') }}"
                           class="inline-flex items-center rounded-xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-[#6b5e62] shadow-sm transition hover:bg-[#FAF6F6] hover:text-[#2D2526] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            Limpiar
                        </a>
                    </div>
                </form>
                <div class="border-t border-[#e9dfdb]/80 bg-[#FAF6F6]/50 px-6 py-3 text-xs font-medium text-[#6b5e62]" role="status">
                    {{ $registros->total() }} {{ $registros->total() === 1 ? 'evento' : 'eventos' }} encontrados
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft" aria-label="Eventos de auditoría">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-6 py-3.5">Fecha y hora</th>
                                <th scope="col" class="px-6 py-3.5">Usuario</th>
                                <th scope="col" class="px-6 py-3.5">Acción</th>
                                <th scope="col" class="px-6 py-3.5">Registro</th>
                                <th scope="col" class="px-6 py-3.5">Detalles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e9dfdb]/60">
                            @forelse ($registros as $registro)
                                <tr class="transition hover:bg-[#FAF6F6]/60 align-top">
                                    <td class="whitespace-nowrap px-6 py-4 text-[#6b5e62]">
                                        {{ $registro->fecha?->translatedFormat('d M Y, H:i') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-[#2D2526] font-medium">
                                        {{ $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : ($registro->actor_nombre ?? 'Usuario no disponible') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-[#2D2526]">
                                        {{ $etiquetasAccion[$registro->accion] ?? str_replace('.', ' · ', ucfirst($registro->accion ?? 'Evento')) }}
                                    </td>
                                    <td class="px-6 py-4 text-[#6b5e62]">
                                        {{ $etiquetasTabla[$registro->tabla] ?? ucfirst(str_replace('_', ' ', $registro->tabla ?? 'Registro')) }}
                                        <span class="text-[#8A7A7D]">#{{ $registro->registro_id ?? '—' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($registro->datos_anteriores || $registro->datos_nuevos)
                                            <details class="group">
                                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 rounded-xl px-2.5 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                                    Ver cambios
                                                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </summary>
                                                <div class="mt-2 min-w-64 space-y-3 rounded-xl border border-[#e9dfdb] bg-[#FAF6F6] p-3 text-xs">
                                                    @foreach (['Antes' => $registro->datos_anteriores, 'Después' => $registro->datos_nuevos] as $titulo => $datos)
                                                        @if ($datos)
                                                            <div>
                                                                <h4 class="text-[10px] font-semibold uppercase tracking-wider text-[#8A7A7D]">{{ $titulo }}</h4>
                                                                <dl class="mt-1 space-y-1">
                                                                    @foreach ($datos as $campo => $valor)
                                                                        <div class="grid grid-cols-[minmax(7rem,auto)_1fr] gap-2">
                                                                            <dt class="font-medium text-[#6b5e62]">{{ ucfirst(str_replace('_', ' ', $campo)) }}</dt>
                                                                            <dd class="break-words text-[#2D2526] font-mono">{{ is_scalar($valor) ? ($valor === '' ? '—' : $valor) : json_encode($valor, JSON_UNESCAPED_UNICODE) }}</dd>
                                                                        </div>
                                                                    @endforeach
                                                                </dl>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-xs text-[#8A7A7D]">Sin detalle</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                        No hay eventos de auditoría para los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($registros->hasPages())
                    <div class="border-t border-[#e9dfdb] px-6 py-4">
                        {{ $registros->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
