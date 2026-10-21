<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Mensajes de contacto
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Buzón de dudas, inquietudes y solicitudes de información de la página web.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Mensajes enviados desde el formulario de contacto del sitio público.
                    </p>

                    <form method="GET" action="{{ route('admin.mensajes.index') }}" class="flex items-center gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#8A7A7D]">Filtrar:</span>
                        <select name="leido" onchange="this.form.submit()"
                                class="rounded-xl border border-[#e9dfdb] bg-white px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Todos</option>
                            <option value="0" @selected(($filtros['leido'] ?? '') === '0')>Sin leer</option>
                            <option value="1" @selected(($filtros['leido'] ?? '') === '1')>Leídos</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Fecha</th>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Correo</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Mensaje</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($mensajes as $mensaje)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 text-[#6b5e62] whitespace-nowrap">{{ $mensaje->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $mensaje->nombre }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $mensaje->email }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $mensaje->telefono ?? '—' }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-[#2D2526]">{{ $mensaje->mensaje }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mensaje->leido ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-amber-50 text-amber-800 border border-amber-200/60' }}">
                                        {{ $mensaje->leido ? 'Leído' : 'Sin leer' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <form method="POST" action="{{ route('admin.mensajes.toggleLeido', $mensaje) }}">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}" aria-label="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}"
                                                    class="rounded-xl p-2 text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800">
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
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay mensajes de contacto todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
