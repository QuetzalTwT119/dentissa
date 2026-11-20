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
    'usuario.creado' => 'Usuario creado',
    'usuario.actualizado' => 'Usuario actualizado',
    'usuario.activado' => 'Cuenta activada',
    'usuario.desactivado' => 'Cuenta desactivada',
    'usuario.password_actualizada' => 'Contraseña actualizada',
    'usuario.cuenta_eliminada' => 'Cuenta eliminada por su titular',
    'usuario.desactivado_por_titular' => 'Cuenta desactivada por su titular',
    'perfil.actualizado' => 'Perfil actualizado',
    'perfil.foto_actualizada' => 'Foto de perfil actualizada',
    'perfil.foto_eliminada' => 'Foto de perfil eliminada',
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
        <x-admin.header
            badge="Seguridad y Control"
            title="Auditoría"
            subtitle="Trazabilidad de seguridad, cambios en datos clínicos y actividad del personal."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Contenedor Principal de la Bitácora (Estructura Unificada como en Directorio de Pacientes) -->
            <x-admin.card>
                <!-- Barra superior con contexto y filtros integrados -->
                <div class="border-b border-line/80 p-6 bg-gradient-to-r from-white via-white to-cream/50">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-2xl">
                            <h3 class="text-headline font-semibold text-ink">Actividad registrada</h3>
                            <p class="mt-1 text-sm text-ink-soft">
                                Consulta de solo lectura para administración. Solo se registran acciones hechas por cuentas
                                del personal. Las solicitudes del portal o Telegram no se muestran aquí. Los detalles no incluyen contraseñas ni notas clínicas.
                            </p>
                        </div>

                        <!-- Conteo de eventos accesible -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0 self-start lg:self-auto" role="status">
                            @if (!empty($filtros['desde']) || !empty($filtros['hasta']) || !empty($filtros['usuario_id']) || !empty($filtros['accion']))
                                <span class="inline-flex items-center gap-1 rounded-full bg-peach-50 px-2.5 py-0.5 text-caption font-semibold text-brand-600 border border-peach-300/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                    Filtros activos
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3 py-1 text-xs font-semibold text-ink-soft shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-sage-500" aria-hidden="true"></span>
                                {{ $registros->total() }} {{ $registros->total() === 1 ? 'evento' : 'eventos' }} encontrados
                            </span>
                        </div>
                    </div>

                    <!-- Fila de Filtros compactos integrados (estilo Citas / Pacientes) -->
                    <form method="GET" action="{{ route('admin.auditoria.index') }}" class="mt-5 pt-4 border-t border-line/70">
                        @if (request()->filled('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                            <!-- Desde -->
                            <div class="lg:col-span-2">
                                <label for="filtro_desde" class="block text-sm font-semibold text-ink mb-1.5">
                                    Desde
                                </label>
                                <input id="filtro_desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                                       aria-label="Fecha inicial desde"
                                       class="min-h-11 block w-full rounded-xl border border-field bg-white px-3 py-2 text-sm text-ink shadow-2xs transition hover:border-brand-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                            </div>

                            <!-- Hasta -->
                            <div class="lg:col-span-2">
                                <label for="filtro_hasta" class="block text-sm font-semibold text-ink mb-1.5">
                                    Hasta
                                </label>
                                <input id="filtro_hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                                       aria-label="Fecha final hasta"
                                       class="min-h-11 block w-full rounded-xl border border-field bg-white px-3 py-2 text-sm text-ink shadow-2xs transition hover:border-brand-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                            </div>

                            <!-- Usuario -->
                            <div class="lg:col-span-3">
                                <label for="filtro_usuario" class="block text-sm font-semibold text-ink mb-1.5">
                                    Usuario
                                </label>
                                <select id="filtro_usuario" name="usuario_id"
                                        aria-label="Filtrar por usuario"
                                        class="min-h-11 block w-full rounded-xl border border-field bg-white px-3 py-2 text-sm text-ink shadow-2xs transition hover:border-brand-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                                    <option value="">Todos los usuarios</option>
                                    @foreach ($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}" @selected((string) ($filtros['usuario_id'] ?? '') === (string) $usuario->id)>
                                            {{ $usuario->nombre }} {{ $usuario->apellido }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Acción -->
                            <div class="lg:col-span-3">
                                <label for="filtro_accion" class="block text-sm font-semibold text-ink mb-1.5">
                                    Acción
                                </label>
                                <select id="filtro_accion" name="accion"
                                        aria-label="Filtrar por acción"
                                        class="min-h-11 block w-full rounded-xl border border-field bg-white px-3 py-2 text-sm text-ink shadow-2xs transition hover:border-brand-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                                    <option value="">Todas las acciones</option>
                                    @foreach ($acciones as $accion)
                                        <option value="{{ $accion }}" @selected(($filtros['accion'] ?? '') === $accion)>
                                            {{ $etiquetasAccion[$accion] ?? str_replace('.', ' · ', ucfirst($accion)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Botones -->
                            <div class="sm:col-span-2 lg:col-span-2 flex items-center gap-2">
                                <button type="submit"
                                        class="btn-brand h-11 flex-1">
                                    Filtrar
                                </button>
                                <a href="{{ route('admin.auditoria.index', request()->filled('per_page') ? ['per_page' => request('per_page')] : []) }}"
                                   class="btn-secondary h-11 flex-1">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabla (Directamente integrada en el contenedor igual que Pacientes y Mensajes) -->
                <x-pagination-summary :items="$registros" label="eventos" />
                <x-admin.table minWidth="min-w-[850px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-4">Fecha y hora</th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">Acción</th>
                            <th class="px-6 py-4">Registro</th>
                            <th class="px-6 py-4">Detalles</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($registros as $registro)
                        <tr class="transition-colors hover:bg-brand-50/20 group">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-soft">
                                <p class="font-medium text-ink">{{ $registro->fecha?->translatedFormat('d M Y') ?? '—' }}</p>
                                <p class="text-xs font-mono text-muted">{{ $registro->fecha?->translatedFormat('H:i') ?? '' }} hrs</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $nombreUsuario = $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : ($registro->actor_nombre ?? 'Usuario');
                                    @endphp
                                    <x-admin.user-avatar :name="$nombreUsuario" />
                                    <div>
                                        <p class="font-semibold text-ink group-hover:text-brand-700 transition-colors">
                                            {{ $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : ($registro->actor_nombre ?? 'Usuario no disponible') }}
                                        </p>
                                        <p class="text-caption text-muted">
                                            {{ $registro->actor_rol ? ucfirst($registro->actor_rol) : ($registro->usuario ? ucfirst($registro->usuario->rol?->value ?? 'Personal') : 'Personal') }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200/70">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                    {{ $etiquetasAccion[$registro->accion] ?? str_replace('.', ' · ', ucfirst($registro->accion ?? 'Evento')) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 text-sm text-ink">
                                    <span class="font-medium">{{ $etiquetasTabla[$registro->tabla] ?? ucfirst(str_replace('_', ' ', $registro->tabla ?? 'Registro')) }}</span>
                                    <span class="rounded-lg bg-cream px-1.5 py-0.5 text-xs font-mono font-semibold text-muted border border-line">
                                        #{{ $registro->registro_id ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($registro->datos_anteriores || $registro->datos_nuevos)
                                    <details class="group relative">
                                        <summary aria-label="Ver cambios detallados del evento"
                                                 class="inline-flex cursor-pointer list-none items-center gap-1 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 border border-brand-200/60 transition hover:bg-brand-600 hover:text-white hover:border-brand-600 shadow-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/25 [&::-webkit-details-marker]:hidden">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <span>Ver cambios</span>
                                            <svg class="h-3 w-3 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                                            </svg>
                                        </summary>
                                        <div class="mt-2.5 min-w-72 max-w-md space-y-2.5 rounded-2xl border border-line bg-white p-3.5 shadow-soft ring-1 ring-black/[0.03]">
                                            @foreach (['Antes' => $registro->datos_anteriores, 'Después' => $registro->datos_nuevos] as $titulo => $datos)
                                                @if ($datos)
                                                    <div class="rounded-xl border {{ $titulo === 'Antes' ? 'border-amber-200/70 bg-amber-50/40' : 'border-sage-500/20 bg-sage-50/40' }} p-2.5">
                                                        <div class="flex items-center gap-1.5 mb-1.5">
                                                            <span class="h-1.5 w-1.5 rounded-full {{ $titulo === 'Antes' ? 'bg-amber-500' : 'bg-sage-500' }}"></span>
                                                            <h4 class="text-caption font-semibold uppercase tracking-label {{ $titulo === 'Antes' ? 'text-amber-800' : 'text-sage-500' }}">{{ $titulo }}</h4>
                                                        </div>
                                                        <dl class="space-y-1 text-xs">
                                                            @foreach ($datos as $campo => $valor)
                                                                <div class="grid grid-cols-[minmax(6.5rem,auto)_1fr] gap-2 py-0.5 border-b border-line/40 last:border-b-0">
                                                                    <dt class="font-medium text-ink-soft">{{ ucfirst(str_replace('_', ' ', $campo)) }}</dt>
                                                                    <dd class="break-words font-mono text-ink font-semibold">{{ is_scalar($valor) ? ($valor === '' ? '—' : $valor) : json_encode($valor, JSON_UNESCAPED_UNICODE) }}</dd>
                                                                </div>
                                                            @endforeach
                                                        </dl>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="text-xs text-muted">Sin detalle</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-200/50 mb-3 shadow-2xs">
                                        <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-ink">No se encontraron eventos</p>
                                    <p class="mt-1 text-xs text-muted">
                                        No hay eventos de auditoría para los filtros seleccionados.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-admin.table>
                <x-pagination-summary :items="$registros" label="eventos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
