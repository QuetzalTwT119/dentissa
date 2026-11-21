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

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Contenedor Principal de la Bitácora (Estructura Unificada como en Directorio de Pacientes) -->
            <x-admin.card>
                {{-- Nota breve (antes un bloque alto con el conteo repetido y filtros propios). --}}
                <p class="border-b border-line/80 px-6 py-2.5 text-xs text-muted lg:[@media(max-height:820px)]:hidden">
                    Solo lectura. Se registran acciones de cuentas del personal (no del portal ni Telegram); los detalles no incluyen contraseñas ni notas clínicas.
                </p>

                <!-- Tabla (Directamente integrada en el contenedor igual que Pacientes y Mensajes) -->
                @php
                    $opcionesUsuario = $usuarios->mapWithKeys(fn ($u) => [$u->id => trim($u->nombre.' '.$u->apellido)])->all();
                    $opcionesAccion = collect($acciones)->mapWithKeys(fn ($a) => [$a => $etiquetasAccion[$a] ?? str_replace('.', ' · ', ucfirst($a))])->all();
                @endphp
                <x-pagination-summary :items="$registros" label="eventos">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.auditoria.index')" :campos="['desde', 'hasta', 'usuario_id', 'accion']">
                            <x-filtro.fecha name="desde" etiqueta="Desde" :max="now()->toDateString()" />
                            <x-filtro.fecha name="hasta" etiqueta="Hasta" :max="now()->toDateString()" />
                            <x-filtro.select name="usuario_id" id="filtro_usuario" etiqueta="Filtrar por usuario" todos="Todos los usuarios" :opciones="$opcionesUsuario" />
                            <x-filtro.select name="accion" id="filtro_accion" etiqueta="Filtrar por acción" todos="Todas las acciones" :opciones="$opcionesAccion" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
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
                                <x-chip class="bg-brand-50 text-brand-800 border border-brand-200/70">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                    {{ $etiquetasAccion[$registro->accion] ?? str_replace('.', ' · ', ucfirst($registro->accion ?? 'Evento')) }}
                                </x-chip>
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
