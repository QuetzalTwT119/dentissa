<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lista de espera</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-brand-50 font-sans text-gray-900">
        <main class="mx-auto max-w-xl px-6 py-12">
            <a href="{{ route('citas.publica.create') }}" class="text-sm font-medium text-brand-700 hover:underline">← Volver a agendar</a>
            <div class="mt-5 rounded-xl bg-white p-6 shadow-sm sm:p-8" x-data="{ servicios: @js($servicios), servicio: @js(old('id_servicio', '')), get tratamientos() { return this.servicios.find(item => item.id === Number(this.servicio))?.tratamientos ?? []; } }">
                <img src="{{ asset(config('dentissa.imagenes.lista_espera')) }}" alt="Paciente consultando la disponibilidad de citas dentales" class="mb-6 aspect-[16/9] w-full rounded-lg object-cover" fetchpriority="high">
                <h1 class="text-2xl font-semibold text-gray-900">Lista de espera</h1>
                <p class="mt-2 text-sm text-gray-700">Déjanos tus preferencias. El consultorio te contactará solo si se libera un horario compatible; no es una cita confirmada.</p>
                @if (session('success'))
                    <div class="mt-5 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
                @else
                    <form method="POST" action="{{ route('atencion.lista-espera.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div><label class="text-sm font-medium">Nombre</label><input name="nombre" value="{{ old('nombre') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Teléfono</label><input type="tel" name="telefono" value="{{ old('telefono') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Correo (opcional)</label><input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Servicio (opcional)</label><select name="id_servicio" x-model="servicio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Cualquier servicio</option><template x-for="item in servicios" :key="item.id"><option :value="item.id" x-text="item.nombre"></option></template></select></div>
                        <div x-show="tratamientos.length"><label class="text-sm font-medium">Tratamiento (opcional)</label><select name="id_tratamiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Cualquier tratamiento</option><template x-for="item in tratamientos" :key="item.id"><option :value="item.id" x-text="item.nombre"></option></template></select></div>
                        <div><label class="text-sm font-medium">Fecha preferida (opcional)</label><input type="date" min="{{ now()->toDateString() }}" name="fecha_preferida" value="{{ old('fecha_preferida') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Horario</label><select name="franja_horaria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="cualquiera">Cualquier hora</option><option value="manana">Mañana</option><option value="tarde">Tarde</option></select></div>
                        <div><label class="text-sm font-medium">Notas (opcional)</label><textarea name="notas" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notas') }}</textarea></div>
                        <button class="w-full rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700">Unirme a la lista de espera</button>
                    </form>
                @endif
            </div>
        </main>
    </body>
</html>
