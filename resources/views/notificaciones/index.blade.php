<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Centro de actividad</p>
                <h2 class="mt-1 text-2xl font-bold leading-tight text-[#2D2526]">Notificaciones</h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Avisos internos de citas para el equipo de la clínica.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" data-notification-sound-toggle data-label-on="Silenciar sonido" data-label-off="Activar sonido" aria-pressed="false" class="inline-flex items-center gap-2 rounded-xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-[#2D2526] shadow-soft transition hover:border-[#f2b0a6] hover:bg-[#FAF6F6] focus:outline-none focus:ring-2 focus:ring-brand-400" aria-label="Activar sonido de notificaciones" title="Activar sonido de notificaciones">
                    <svg class="h-4 w-4 text-[#8A7A7D]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H3v6h3l5 4V5Zm4.5 3.5a5 5 0 0 1 0 7m3-10a9 9 0 0 1 0 13" />
                    </svg>
                    <span data-notification-sound-label>Activar sonido</span>
                </button>

                @if ($noLeidas > 0)
                    <form method="POST" action="{{ route('notificaciones.leidas') }}">
                        @csrf
                        @method('PATCH')
                        <button class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-white shadow-soft transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
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
                <div role="status" class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 shadow-soft">
                    <svg class="h-5 w-5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06l-3.47 3.47-1.47-1.47a.75.75 0 0 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-4 sm:grid-cols-3" aria-label="Resumen de notificaciones">
                <article class="rounded-2xl border border-[#e9dfdb] bg-white p-5 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#8A7A7D]">Total de avisos</p>
                    <p class="mt-2 font-serif text-2xl font-bold text-[#2D2526]">{{ $total }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200/80 bg-white p-5 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Pendientes</p>
                    <p class="mt-2 font-serif text-2xl font-bold text-brand-600">{{ $noLeidas }}</p>
                </article>
                <article class="rounded-2xl border border-[#e9dfdb] bg-white p-5 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#8A7A7D]">Leídas</p>
                    <p class="mt-2 font-serif text-2xl font-bold text-[#6b5e62]">{{ $leidas }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft" aria-label="Filtros de notificaciones">
                <nav class="flex gap-1 overflow-x-auto border-b border-[#e9dfdb] px-4 pt-3" aria-label="Filtrar notificaciones">
                    @foreach ([
                        'todas' => ['label' => 'Todas', 'count' => $total],
                        'pendientes' => ['label' => 'Pendientes', 'count' => $noLeidas],
                        'leidas' => ['label' => 'Leídas', 'count' => $leidas],
                    ] as $estado => $filtro)
                        <a href="{{ route('notificaciones.index', ['estado' => $estado]) }}" @class([
                            'inline-flex shrink-0 items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-xs font-semibold uppercase tracking-wider transition',
                            'border-brand-600 text-brand-700 bg-brand-50/50' => $estadoActual === $estado,
                            'border-transparent text-[#6b5e62] hover:border-[#e9dfdb] hover:text-[#2D2526]' => $estadoActual !== $estado,
                        ]) @if ($estadoActual === $estado) aria-current="page" @endif>
                            {{ $filtro['label'] }}
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                'bg-brand-600 text-white' => $estadoActual === $estado,
                                'bg-[#FAF6F6] text-[#8A7A7D] border border-[#e9dfdb]' => $estadoActual !== $estado,
                            ])>{{ $filtro['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>

            <section class="space-y-3" aria-label="Lista de notificaciones" aria-live="polite">
                @forelse ($notificaciones as $notificacion)
                    @php($pendiente = is_null($notificacion->leida_en))
                    <article class="relative overflow-hidden rounded-2xl border bg-white shadow-soft transition hover:shadow-md {{ $pendiente ? 'border-[#f2b0a6]' : 'border-[#e9dfdb]' }}">
                        @if ($pendiente)
                            <span class="absolute inset-y-0 left-0 w-1.5 bg-brand-600" aria-hidden="true"></span>
                        @endif

                        <div class="flex flex-col gap-4 p-5 pl-6 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3.5">
                                <span @class([
                                    'mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                    'bg-rose-50 text-brand-600 border border-rose-200/50' => $pendiente,
                                    'bg-[#FAF6F6] text-[#8A7A7D] border border-[#e9dfdb]' => ! $pendiente,
                                ]) aria-hidden="true">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.85 23.85 0 0 0 20.31 15.77 8.97 8.97 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.453 1.312m5.714 0a24.25 24.25 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                    </svg>
                                </span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-[#2D2526]">{{ $notificacion->titulo }}</h3>
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
                                            'bg-rose-50 text-brand-700 border border-rose-200/60' => $pendiente,
                                            'bg-stone-100 text-[#8A7A7D] border border-stone-200/60' => ! $pendiente,
                                        ])>{{ $pendiente ? 'Pendiente' : 'Leída' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm leading-relaxed text-[#6b5e62]">{{ $notificacion->mensaje }}</p>
                                    <time class="mt-2 block text-xs text-[#8A7A7D]" datetime="{{ $notificacion->created_at->toIso8601String() }}" title="{{ $notificacion->created_at->translatedFormat('l d F Y, H:i') }}">
                                        {{ $notificacion->created_at->diffForHumans() }} · {{ $notificacion->created_at->translatedFormat('d M Y, H:i') }}
                                    </time>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2 pl-[3.25rem] sm:pl-0">
                                <a href="{{ $notificacion->url ?: route('citas.index') }}" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-brand-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    Ver cita
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5 10a.75.75 0 0 1 .75-.75h7.69L10.22 6.03a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H5.75A.75.75 0 0 1 5 10Z" clip-rule="evenodd" />
                                    </svg>
                                </a>

                                @if ($pendiente)
                                    <form method="POST" action="{{ route('notificaciones.leida', $notificacion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2 text-xs font-medium text-[#2D2526] transition hover:bg-[#FAF6F6] hover:border-[#f2b0a6] focus:outline-none focus:ring-2 focus:ring-brand-400">Marcar leída</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('notificaciones.pendiente', $notificacion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2 text-xs font-medium text-[#6b5e62] transition hover:bg-[#FAF6F6] hover:text-[#2D2526] focus:outline-none focus:ring-2 focus:ring-brand-400">Marcar pendiente</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#e9dfdb] bg-white px-6 py-14 text-center shadow-soft">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-200/50" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.85 23.85 0 0 0 20.31 15.77 8.97 8.97 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.453 1.312m5.714 0a24.25 24.25 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </span>
                        @if ($total === 0)
                            <h3 class="mt-4 font-semibold text-[#2D2526]">Todo está al día</h3>
                            <p class="mt-1 text-sm text-[#8A7A7D]">Cuando haya cambios importantes en citas, los avisos aparecerán aquí.</p>
                        @elseif ($estadoActual === 'pendientes')
                            <h3 class="mt-4 font-semibold text-[#2D2526]">No tienes avisos pendientes</h3>
                            <p class="mt-1 text-sm text-[#8A7A7D]">Las notificaciones nuevas aparecerán aquí en tiempo real.</p>
                            <a href="{{ route('notificaciones.index', ['estado' => 'todas']) }}" class="mt-4 inline-flex rounded-xl px-4 py-2 text-xs font-semibold uppercase tracking-wider text-brand-600 hover:bg-brand-50 transition">Ver todas</a>
                        @else
                            <h3 class="mt-4 font-semibold text-[#2D2526]">No hay avisos leídos</h3>
                            <p class="mt-1 text-sm text-[#8A7A7D]">Los avisos que marques como leídos se mostrarán en esta sección.</p>
                            <a href="{{ route('notificaciones.index', ['estado' => 'todas']) }}" class="mt-4 inline-flex rounded-xl px-4 py-2 text-xs font-semibold uppercase tracking-wider text-brand-600 hover:bg-brand-50 transition">Ver todas</a>
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
