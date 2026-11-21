<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Atención y Contacto"
            title="Mensajes de contacto"
            subtitle="Buzón de dudas, inquietudes y solicitudes de información de la página web."
        />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>

                <x-pagination-summary :items="$mensajes" label="mensajes">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.mensajes.index')" :campos="['leido']">
                            <x-filtro.select name="leido" id="filtro_leido" etiqueta="Filtrar por estado del mensaje" todos="Todos los mensajes" :opciones="['0' => 'Sin leer', '1' => 'Leídos']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Fecha</th>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Correo</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Mensaje</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($mensajes as $mensaje)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $mensaje->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $mensaje->nombre }}</td>
                            <td class="px-6 py-4 text-ink-soft">{{ $mensaje->email }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $mensaje->telefono ?? '—' }}</td>
                            <td class="max-w-xs truncate px-6 py-4 text-ink" title="{{ $mensaje->mensaje }}">{{ $mensaje->mensaje }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge
                                    :active="$mensaje->leido"
                                    activeText="Leído"
                                    inactiveText="Sin leer"
                                />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-admin.acciones-fila :eliminar="route('admin.mensajes.destroy', $mensaje)" que="el mensaje" :nombre="$mensaje->nombre">
                                    <x-admin.boton-fila :action="route('admin.mensajes.toggleLeido', $mensaje)"
                                                        :icono="$mensaje->leido ? 'eye' : 'check'" :color="$mensaje->leido ? 'muted' : 'sage'"
                                                        :etiqueta="$mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído'"
                                                        :titulo="$mensaje->leido ? '¿Marcar el mensaje como sin leer?' : '¿Marcar el mensaje como leído?'"
                                                        :mensaje="'Mensaje de '.$mensaje->nombre.'. Puedes cambiarlo de nuevo cuando quieras.'"
                                                        :boton="$mensaje->leido ? 'Sí, sin leer' : 'Sí, leído'" />
                                    <span class="mx-0.5 h-5 w-px bg-line" aria-hidden="true"></span>
                                </x-admin.acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="7"
                            title="No hay mensajes de contacto todavía"
                            description="Cuando los visitantes envíen dudas o solicitudes desde el sitio web, aparecerán aquí."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$mensajes" label="mensajes" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
