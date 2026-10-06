<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Solicitar atención urgente</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-red-50 font-sans text-gray-900">
        <main class="mx-auto max-w-xl px-6 py-12">
            <a href="{{ route('citas.publica.create') }}" class="text-sm font-medium text-brand-700 hover:underline">← Volver a agendar</a>
            <div class="mt-5 rounded-xl bg-white p-6 shadow-sm sm:p-8">
                <h1 class="text-2xl font-semibold text-red-900">Solicitar atención urgente</h1>
                <p class="mt-3 text-sm text-gray-700">Describe brevemente lo que ocurre. Un integrante del consultorio revisará la solicitud y te contactará; este formulario no proporciona diagnóstico ni sustituye atención médica.</p>
                <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-900">
                    Si hay dificultad para respirar o tragar, sangrado que no se detiene, pérdida de conciencia u otra emergencia que pone en riesgo la vida, llama a emergencias o acude al servicio de urgencias más cercano.
                </div>

                @if (session('success'))
                    <div class="mt-5 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
                @else
                    <form method="POST" action="{{ route('atencion.urgencia.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div><label class="text-sm font-medium">Nombre</label><input name="nombre" value="{{ old('nombre') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Teléfono para contactarte</label><input type="tel" name="telefono" value="{{ old('telefono') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <div><label class="text-sm font-medium">Correo (opcional)</label><input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                        <fieldset><legend class="text-sm font-medium">¿Qué señales reportas? (opcional)</legend><div class="mt-2 grid grid-cols-2 gap-2 text-sm">@foreach (['dolor' => 'Dolor', 'inflamacion' => 'Inflamación', 'sangrado' => 'Sangrado', 'traumatismo' => 'Golpe o traumatismo', 'otro' => 'Otro'] as $valor => $etiqueta)<label><input type="checkbox" name="senales_reportadas[]" value="{{ $valor }}" @checked(in_array($valor, old('senales_reportadas', [])))> {{ $etiqueta }}</label>@endforeach</div></fieldset>
                        <div><label class="text-sm font-medium">Cuéntanos brevemente qué ocurre</label><textarea name="motivo" required rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('motivo') }}</textarea>@error('motivo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        <button class="w-full rounded-md bg-red-700 px-4 py-3 text-sm font-semibold text-white hover:bg-red-800">Enviar solicitud</button>
                    </form>
                @endif
            </div>
        </main>
    </body>
</html>
