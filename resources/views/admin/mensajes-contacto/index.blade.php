<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Atención y Contacto"
            title="Mensajes de contacto"
            subtitle="Buzón de dudas, inquietudes y solicitudes de información de la página web."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>
                <x-admin.card-header
                    title="Bandeja de entrada"
                    description="Mensajes enviados desde el formulario de contacto del sitio público."
                >
                    <x-slot:actions>
                        <form method="GET" action="{{ route('admin.mensajes.index') }}" class="flex items-center gap-2.5">
                            <label for="filtro_leido" class="text-xs font-semibold uppercase tracking-label text-muted">Filtrar:</label>
                            <select id="filtro_leido" name="leido" onchange="this.form.submit()" aria-label="Filtrar por estado del mensaje"
                                    class="rounded-xl border border-field bg-white px-3 py-1.5 text-sm text-ink shadow-xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="">Todos</option>
                                <option value="0" @selected(($filtros['leido'] ?? '') === '0')>Sin leer</option>
                                <option value="1" @selected(($filtros['leido'] ?? '') === '1')>Leídos</option>
                            </select>
                        </form>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[760px]">
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
                                <div class="flex items-center justify-end gap-1.5">
                                    <form method="POST" action="{{ route('admin.mensajes.toggleLeido', $mensaje) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" title="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}" aria-label="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}"
                                                class="rounded-xl p-2 text-sage-600 transition hover:bg-sage-50 hover:text-sage-700">
                                            <x-icon name="check" />
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.mensajes.destroy', $mensaje) }}"
                                          onsubmit="return confirm('¿Eliminar este mensaje? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar mensaje"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                </div>
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

                <x-pagination-summary :items="$mensajes" label="mensajes" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
