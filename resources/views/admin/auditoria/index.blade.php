<x-app-layout>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-600">Control administrativo</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Bitácora de actividad</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Consulta las acciones realizadas por el equipo. Para proteger a los pacientes, se registran los movimientos y campos modificados, nunca valores clínicos.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700">Solo administración</span>
        </div>

        <form method="GET" class="mt-7 grid gap-4 rounded-2xl border border-brand-100 bg-white p-5 shadow-sm md:grid-cols-4">
            <label class="block text-sm font-medium text-slate-700">Responsable
                <select name="usuario" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todo el equipo</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected(($filtros['usuario'] ?? null) == $usuario->id)>{{ $usuario->nombre }} {{ $usuario->apellido }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium text-slate-700">Acción
                <select name="accion" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    <option value="creado" @selected(($filtros['accion'] ?? null) === 'creado')>Creación</option>
                    <option value="actualizado" @selected(($filtros['accion'] ?? null) === 'actualizado')>Actualización</option>
                    <option value="eliminado" @selected(($filtros['accion'] ?? null) === 'eliminado')>Eliminación</option>
                </select>
            </label>
            <label class="block text-sm font-medium text-slate-700">Fecha
                <input type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
            </label>
            <div class="flex items-end gap-2">
                <button class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Filtrar</button>
                <a href="{{ route('admin.auditoria.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Limpiar</a>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-brand-100 text-left text-sm">
                    <thead class="bg-brand-50/60 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Responsable</th><th class="px-5 py-3">Acción</th><th class="px-5 py-3">Área</th><th class="px-5 py-3">Detalle seguro</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($registros as $registro)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $registro->fecha?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : 'Sistema' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $registro->accion === 'eliminado' ? 'bg-rose-50 text-rose-700' : 'bg-brand-50 text-brand-700' }}">{{ ucfirst($registro->accion) }}</span></td>
                                <td class="px-5 py-4">{{ $etiquetas[$registro->tabla] ?? ucfirst(str_replace('_', ' ', $registro->tabla)) }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ filled($registro->datos_nuevos['campos_modificados'] ?? null) ? 'Campos: '.implode(', ', $registro->datos_nuevos['campos_modificados']) : 'Movimiento registrado' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Aún no hay movimientos que coincidan con los filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($registros->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $registros->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
