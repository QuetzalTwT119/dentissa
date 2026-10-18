<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Centro de actividad</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-gray-900">Notificaciones</h2>
                <p class="mt-1 text-sm text-gray-500">Avisos internos de citas para el equipo de la clínica.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-notification-sound-toggle data-label-on="Silenciar sonido" data-label-off="Activar sonido" aria-pressed="false" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-brand-200 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-primary" aria-label="Activar sonido de notificaciones" title="Activar sonido de notificaciones">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm4.5 3.5a5 5 0 0 1 0 7m3-10a9 9 0 0 1 0 13" />
                    </svg>
                    <span data-notification-sound-label>Activar sonido</span>
                </button>

                @if ($noLeidas > 0)
                    <form method="POST" action="{{ route('notificaciones.leidas') }}">
                        @csrf
                        @method('PATCH')
                        <button class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                            Marcar todas como leídas
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div role="status" class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06l-3.47 3.47-1.47-1.47a.75.75 0 0 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen de notificaciones">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total de avisos</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $total }}</p>
                </article>
                <article class="rounded-xl border border-rose-100 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Pendientes</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $noLeidas }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Leídas</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-700">{{ $leidas }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" aria-label="Filtros de notificaciones">
                <nav class="flex gap-1 overflow-x-auto border-b border-gray-200 px-3 pt-3" aria-label="Filtrar notificaciones">
                    @foreach ([
                        'todas' => ['label' => 'Todas', 'count' => $total],
                        'pendientes' => ['label' => 'Pendientes', 'count' => $noLeidas],
                        'leidas' => ['label' => 'Leídas', 'count' => $leidas],
                    ] as $estado => $filtro)
                        <a href="{{ route('notificaciones.index', ['estado' => $estado]) }}" @class([
                            'inline-flex shrink-0 items-center gap-2 rounded-t-lg border-b-2 px-4 py-3 text-sm font-semibold transition',
                            'border-brand-primary text-brand-primary' => $estadoActual === $estado,
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' => $estadoActual !== $estado,
                        ]) @if ($estadoActual === $estado) aria-current="page" @endif>
                            {{ $filtro['label'] }}
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs',
                                'bg-brand-50 text-brand-primary' => $estadoActual === $estado,
                                'bg-gray-100 text-gray-600' => $estadoActual !== $estado,
                            ])>{{ $filtro['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>

            <section class="space-y-3" aria-label="Lista de notificaciones" aria-live="polite">
                @forelse ($notificaciones as $notificacion)
                    @php($pendiente = is_null($notificacion->leida_en))
                    <article class="relative overflow-hidden rounded-xl border bg-white shadow-sm transition hover:shadow-md {{ $pendiente ? 'border-rose-200' : 'border-gray-200' }}">
                        @if ($pendiente)
                            <span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" aria-hidden="true"></span>
                        @endif

                        <div class="flex flex-col gap-4 p-4 pl-5 sm:flex-row sm:items-center sm:justify-between sm:p-5 sm:pl-6">
                            <div class="flex min-w-0 items-start gap-3">
                                <span @class([
                                    'mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                    'bg-rose-50 text-brand-primary' => $pendiente,
                                    'bg-gray-100 text-gray-500' => ! $pendiente,
                                ]) aria-hidden="true">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.85 23.85 0 0 0 20.31 15.77 8.97 8.97 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.453 1.312m5.714 0a24.25 24.25 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                    </svg>
                                </span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-gray-900">{{ $notificacion->titulo }}</h3>
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-rose-50 text-rose-700' => $pendiente,
                                            'bg-gray-100 text-gray-600' => ! $pendiente,
                                        ])>{{ $pendiente ? 'Pendiente' : 'Leída' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-gray-600">{{ $notificacion->mensaje }}</p>
                                    <time class="mt-2 block text-xs text-gray-500" datetime="{{ $notificacion->created_at->toIso8601String() }}" title="{{ $notificacion->created_at->translatedFormat('l d F Y, H:i') }}">
                                        {{ $notificacion->created_at->diffForHumans() }} · {{ $notificacion->created_at->translatedFormat('d M Y, H:i') }}
                                    </time>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2 pl-[3.25rem] sm:pl-0">
                                <a href="{{ $notificacion->url ?: route('citas.index') }}" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-brand-primary transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-brand-primary">
                                    Ver cita
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5 10a.75.75 0 0 1 .75-.75h7.69L10.22 6.03a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H5.75A.75.75 0 0 1 5 10Z" clip-rule="evenodd" />
                                    </svg>
                                </a>

                                @if ($pendiente)
                                    <form method="POST" action="{{ route('notificaciones.leida', $notificacion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-primary">Marcar leída</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('notificaciones.pendiente', $notificacion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-primary">Marcar pendiente</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center shadow-sm">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-primary" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.85 23.85 0 0 0 20.31 15.77 8.97 8.97 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.453 1.312m5.714 0a24.25 24.25 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </span>
                        @if ($total === 0)
                            <h3 class="mt-4 font-semibold text-gray-900">Todo está al día</h3>
                            <p class="mt-1 text-sm text-gray-500">Cuando haya cambios importantes en citas, los avisos aparecerán aquí.</p>
                        @elseif ($estadoActual === 'pendientes')
                            <h3 class="mt-4 font-semibold text-gray-900">No tienes avisos pendientes</h3>
                            <p class="mt-1 text-sm text-gray-500">Las notificaciones nuevas aparecerán aquí en tiempo real.</p>
                            <a href="{{ route('notificaciones.index', ['estado' => 'todas']) }}" class="mt-4 inline-flex rounded-lg px-3 py-2 text-sm font-semibold text-brand-primary hover:bg-rose-50">Ver todas</a>
                        @else
                            <h3 class="mt-4 font-semibold text-gray-900">No hay avisos leídos</h3>
                            <p class="mt-1 text-sm text-gray-500">Los avisos que marques como leídos se mostrarán en esta sección.</p>
                            <a href="{{ route('notificaciones.index', ['estado' => 'todas']) }}" class="mt-4 inline-flex rounded-lg px-3 py-2 text-sm font-semibold text-brand-primary hover:bg-rose-50">Ver todas</a>
                        @endif
                    </div>
                @endforelse
            </section>

            @if ($notificaciones->hasPages())
                <div class="pt-2">{{ $notificaciones->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
