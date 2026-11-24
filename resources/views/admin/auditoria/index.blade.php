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
    'historia_clinica.creada' => 'Historia clínica registrada',
    'historia_clinica.actualizada' => 'Historia clínica actualizada',
    'exploracion_clinica.creada' => 'Exploración clínica registrada',
    'exploracion_clinica.actualizada' => 'Exploración clínica actualizada',
    'historial.actualizado' => 'Consulta clínica actualizada',
    'historial.eliminado' => 'Consulta clínica eliminada',
    'historial.imagen_agregada' => 'Archivo clínico agregado',
    'historial.imagen_eliminada' => 'Archivo clínico eliminado',
    'paciente.datos_oficiales_actualizados' => 'Datos oficiales del paciente actualizados',
    'paciente.datos_actualizados' => 'Datos del paciente actualizados',
    'paciente.registrado' => 'Paciente registrado sin cita',
    'usuario.creado' => 'Usuario creado',
    'usuario.actualizado' => 'Usuario actualizado',
    'usuario.activado' => 'Cuenta activada',
    'usuario.desactivado' => 'Cuenta desactivada',
    'usuario.eliminado' => 'Usuario eliminado',
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
    'oferta.publicada' => 'Oferta publicada',
    'oferta.ocultada' => 'Oferta ocultada',
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
    'bloqueo.creado' => 'Día u hora bloqueado',
    'bloqueo.eliminado' => 'Bloqueo quitado',
    'consultorio.actualizado' => 'Datos del consultorio actualizados',
    'mensaje.marco_leido' => 'Mensaje marcado como leído',
    'mensaje.marco_no_leido' => 'Mensaje marcado como no leído',
    'mensaje.eliminado' => 'Mensaje de contacto eliminado',
    'servicio.reordenado' => 'Servicios reordenados',
    'tratamiento.estado_cambiado' => 'Tratamiento activado o desactivado',
    'servicio.estado_cambiado' => 'Servicio activado o desactivado',
    'caso.estado_cambiado' => 'Caso activado o desactivado',
    'galeria.estado_cambiado' => 'Foto de galería activada o desactivada',
    'faq.estado_cambiado' => 'Pregunta mostrada u ocultada',
    // Eventos sin detalle del observador (cambios que no pasan por AuditoriaService).
    'creado' => 'Registro creado',
    'actualizado' => 'Registro actualizado',
    'eliminado' => 'Registro eliminado',
];
$etiquetasTabla = [
    'citas' => 'Cita',
    'historial_clinico' => 'Consulta clínica',
    'historias_clinicas' => 'Historia clínica',
    'exploraciones_clinicas' => 'Exploración clínica',
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
    'bloqueos_agenda' => 'Bloqueo de agenda',
    'datos_consultorio' => 'Consultorio',
    'mensajes_contacto' => 'Mensaje de contacto',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Seguridad y control"
            title="Control de actividades"
            subtitle="Quién hizo cada cambio en el sistema y cuándo: citas, pacientes, ofertas, usuarios y configuración."
        />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Contenedor Principal de la Bitácora (Estructura Unificada como en Directorio de Pacientes) -->
            @php \App\Support\DetalleBitacora::reiniciar(); @endphp
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
                                            {{-- Nombre del rol (22/11/2026); "admin" es de antes de los 3 roles. --}}{{ (\App\Enums\RolUsuario::tryFrom((string) ($registro->actor_rol ?? $registro->usuario?->rol?->value)))?->etiqueta() ?? (['admin' => 'Administración'][$registro->actor_rol] ?? 'Personal') }}
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
                            {{--
                                22/11/2026: "Ver cambios" abre una VENTANA (decisión del usuario). Antes
                                se abría dentro de la celda (estiraba la fila) y luego como fila debajo.
                                Las ventanas van fuera de la tabla (abajo), para que la tabla que se
                                desliza de lado no las descoloque.
                            --}}
                            <td class="px-6 py-4 text-right">
                                @if (\App\Support\DetalleBitacora::filas($registro) !== [])
                                    <button type="button" x-on:click="$dispatch('open-modal', 'cambios-{{ $registro->id }}')" aria-haspopup="dialog" data-ver-cambios
                                            class="inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap rounded-full border border-brand-200/60 bg-brand-50 px-3.5 text-xs font-semibold text-brand-700 transition hover:border-brand-600 hover:bg-brand-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:min-h-9">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Ver cambios
                                    </button>
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
                                        No hay actividades para los filtros seleccionados.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-admin.table>

                {{-- Ventanas de "Ver cambios" (una por evento con detalle). --}}
                @foreach ($registros as $registro)
                    @php $filasDetalle = \App\Support\DetalleBitacora::filas($registro); @endphp
                    @continue($filasDetalle === [])
                    @php
                        $conAntes = collect($filasDetalle)->contains(fn ($f) => $f['antes'] !== null);
                        $conDespues = collect($filasDetalle)->contains(fn ($f) => $f['despues'] !== null);
                        $quien = $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : ($registro->actor_nombre ?? 'Usuario');
                    @endphp
                    <x-modal name="cambios-{{ $registro->id }}" maxWidth="2xl">
                        <div role="dialog" aria-modal="true" aria-labelledby="titulo-cambios-{{ $registro->id }}" data-ventana-cambios>
                            <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                                <div class="min-w-0">
                                    <h2 id="titulo-cambios-{{ $registro->id }}" class="font-serif text-title-3 font-medium text-ink">
                                        {{ $etiquetasAccion[$registro->accion] ?? str_replace('.', ' · ', ucfirst($registro->accion ?? 'Evento')) }}
                                    </h2>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $etiquetasTabla[$registro->tabla] ?? ucfirst(str_replace('_', ' ', $registro->tabla ?? 'Registro')) }} #{{ $registro->registro_id ?? '—' }}
                                        · {{ $quien }} · {{ $registro->fecha?->translatedFormat('j M Y, H:i') }} hrs
                                    </p>
                                </div>
                                <button type="button" x-on:click="$dispatch('close-modal', 'cambios-{{ $registro->id }}')" aria-label="Cerrar"
                                        class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-muted transition hover:bg-cream hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:h-9 lg:w-9">
                                    <x-icon name="x" />
                                </button>
                            </div>

                            <div class="max-h-[min(70vh,36rem)] overflow-y-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="sticky top-0 bg-shell text-caption font-semibold uppercase tracking-label text-ink-soft">
                                        <tr>
                                            <th class="px-5 py-2.5 sm:px-6">Dato</th>
                                            @if ($conAntes && $conDespues)
                                                <th class="px-4 py-2.5"><span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>Antes</span></th>
                                                <th class="px-4 py-2.5 sm:pr-6"><span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-sage-500" aria-hidden="true"></span>Después</span></th>
                                            @else
                                                <th class="px-4 py-2.5 sm:pr-6">{{ $conDespues ? 'Quedó así' : 'Tenía' }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line/60">
                                        @foreach ($filasDetalle as $fila)
                                            <tr>
                                                <th scope="row" class="w-1/3 px-5 py-3 align-top font-medium text-ink-soft sm:px-6">{{ $fila['campo'] }}</th>
                                                @if ($conAntes && $conDespues)
                                                    <td class="break-words px-4 py-3 align-top text-muted">{{ $fila['antes'] ?? '—' }}</td>
                                                    <td class="break-words px-4 py-3 align-top font-semibold text-ink sm:pr-6">{{ $fila['despues'] ?? '—' }}</td>
                                                @else
                                                    <td class="break-words px-4 py-3 align-top font-semibold text-ink sm:pr-6">{{ $fila['despues'] ?? $fila['antes'] }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-end border-t border-line px-5 py-3 sm:px-6">
                                <x-secondary-button x-on:click="$dispatch('close-modal', 'cambios-{{ $registro->id }}')">Cerrar</x-secondary-button>
                            </div>
                        </div>
                    </x-modal>
                @endforeach
                <x-pagination-summary :items="$registros" label="eventos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
