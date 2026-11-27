{{--
    Barra de promoción (27/11/2026, idea de sitios de clínicas de referencia):
    la promoción vigente que termina primero, con cuenta regresiva hasta el fin
    de su último día (hora de la clínica). Sin promociones vigentes no aparece.
    Sin JavaScript dice "hasta el 30 de noviembre". Se puede cerrar y no vuelve
    a salir en esa visita (sessionStorage).
--}}
@if ($ofertaBarra ?? null)
    @php
        $fin = $ofertaBarra->fecha_fin->copy()->timezone(config('app.timezone'))->endOfDay();
    @endphp
    <div x-data="{
            visible: true,
            fin: {{ $fin->getTimestamp() * 1000 }},
            texto: '',
            clave: 'dentissa_barra_promo_{{ $ofertaBarra->id }}',
            actualizar() {
                const resta = this.fin - Date.now();
                if (resta <= 0) { this.visible = false; return; }
                const m = Math.floor(resta / 60000);
                const d = Math.floor(m / 1440), h = Math.floor((m % 1440) / 60), min = m % 60;
                this.texto = 'Termina en ' + (d ? d + ' d ' : '') + (d || h ? h + ' h ' : '') + min + ' min';
            },
            init() {
                try { if (sessionStorage.getItem(this.clave)) { this.visible = false; return; } } catch (e) {}
                this.actualizar();
                setInterval(() => this.actualizar(), 30000);
            },
            cerrar() {
                this.visible = false;
                try { sessionStorage.setItem(this.clave, '1'); } catch (e) {}
            },
         }"
         x-show="visible"
         class="relative bg-brand-700 text-white" data-barra-promocion>
        {{-- En celular una sola línea (el nombre se corta con "…") para no empujar
             "Agendar mi cita" abajo del borde. --}}
        <div class="mx-auto flex min-h-9 max-w-7xl 2xl:max-w-[88rem] items-center justify-center pl-3 pr-11 text-xs sm:min-h-10 sm:px-12 sm:text-sm">
            <a href="{{ route('promociones.index') }}" class="flex min-h-9 min-w-0 items-center gap-x-2 font-semibold underline-offset-4 hover:underline">
                <span class="min-w-0 truncate">{{ $ofertaBarra->titulo }} · {{ $ofertaBarra->etiquetaBeneficio() }}</span>
                <span class="shrink-0 rounded-full bg-white/15 px-2 py-0.5 font-medium tabular-nums" x-text="texto" aria-live="off">Hasta el {{ $fin->translatedFormat('j \d\e F') }}</span>
            </a>
        </div>
        <button type="button" x-on:click="cerrar()" aria-label="Cerrar aviso de promoción"
                class="absolute right-1 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-white/80 transition-colors hover:bg-white/10 hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endif
