<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Mensajes de contacto
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                    <p class="text-sm text-gray-600">
                        Mensajes enviados desde el formulario de contacto del sitio público.
                    </p>

                    <form method="GET" action="{{ route('admin.mensajes.index') }}" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">Filtrar:</span>
                        <select name="leido" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Todos</option>
                            <option value="0" @selected(($filtros['leido'] ?? '') === '0')>Sin leer</option>
                            <option value="1" @selected(($filtros['leido'] ?? '') === '1')>Leídos</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Mensaje</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mensajes as $mensaje)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 text-gray-600">{{ $mensaje->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $mensaje->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $mensaje->email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $mensaje->telefono ?? '—' }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-gray-600">{{ $mensaje->mensaje }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $mensaje->leido ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $mensaje->leido ? 'Leído' : 'Sin leer' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <form method="POST" action="{{ route('admin.mensajes.toggleLeido', $mensaje) }}">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}" aria-label="{{ $mensaje->leido ? 'Marcar como sin leer' : 'Marcar como leído' }}"
                                                    class="rounded p-1.5 text-green-600 transition hover:bg-green-50 hover:text-green-900">
                                                <x-icon name="check" />
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.mensajes.destroy', $mensaje) }}"
                                              onsubmit="return confirm('¿Eliminar este mensaje? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar mensaje"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
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
