<?php

namespace App\Services;

use App\Rules\TelefonoMexico;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AgendaTelegramService
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
        private readonly CrearCitaService $crearCita,
        private readonly NotificacionService $notificaciones,
    ) {}

    public function tieneConsultaEnCurso(int|string $chatId): bool
    {
        return Cache::has($this->clave($chatId));
    }

    /** @return array<string, mixed>|null */
    public function tecladoActual(int|string $chatId): ?array
    {
        $estado = Cache::get($this->clave($chatId));

        if (! $estado) {
            return null;
        }

        if (($estado['paso'] ?? null) === 'telefono') {
            return [
                'keyboard' => [[['text' => '📱 Compartir mi número', 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
                'input_field_placeholder' => 'O escribe tu número',
            ];
        }

        // The phone-sharing reply keyboard is no longer needed once the email step begins.
        if (($estado['paso'] ?? null) === 'email') {
            return ['remove_keyboard' => true];
        }

        $filas = match ($estado['paso'] ?? null) {
            'servicio' => Servicio::where('activo', true)->orderBy('orden')->get(['id', 'nombre'])
                ->map(fn (Servicio $servicio) => [$this->boton($servicio->nombre, "agenda:servicio:{$servicio->id}")])->all(),
            'tratamiento' => Servicio::find($estado['servicio_id'] ?? null)?->tratamientos()
                ->where('activo', true)->orderBy('nombre')->get(['tratamientos.id', 'tratamientos.nombre'])
                ->map(fn (Tratamiento $tratamiento) => [$this->boton($tratamiento->nombre, "agenda:tratamiento:{$tratamiento->id}")])->all() ?? [],
            'fecha' => $this->filasCalendario($estado),
            'horario' => $this->filasHorarios($estado),
            'motivo' => [[$this->boton('Omitir motivo', 'agenda:motivo:omitir')]],
            'confirmacion' => [[$this->boton('Confirmar cita', 'agenda:confirmar:si')]],
            default => [],
        };

        $filas[] = [$this->boton('Cancelar', 'agenda:cancelar')];

        return ['inline_keyboard' => $filas];
    }

    public function responderSeleccion(int|string $chatId, string $callbackData, ?string $callbackQueryId = null): string
    {
        if ($callbackData === 'agenda:sin-accion') {
            return ' ';
        }

        if ($callbackData === 'agenda:cancelar') {
            Cache::forget($this->clave($chatId));

            return 'Cancelé la cita. No se guardó ningún registro.';
        }

        $estado = Cache::get($this->clave($chatId));

        if (! $estado) {
            return 'Esta opción ya venció. Escribe /agendar para comenzar de nuevo.';
        }

        if (preg_match('/^agenda:calendario:([+-]1)$/', $callbackData, $coincidencias)
            && ($estado['paso'] ?? null) === 'fecha') {
            $mes = $this->mesEnEstado($estado);
            $limite = today()->startOfMonth()->addMonths(12);
            $nuevoMes = $mes->copy()->addMonths((int) $coincidencias[1]);

            if ($nuevoMes->lt(today()->startOfMonth()) || $nuevoMes->gt($limite)) {
                return 'Ese mes está fuera del rango disponible.';
            }

            $estado['mes'] = $nuevoMes->format('Y-m');
            Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

            return 'Elige una fecha de '.$this->nombreMes($nuevoMes).':';
        }

        if (preg_match('/^agenda:fecha:(\d{4}-\d{2}-\d{2})$/', $callbackData, $coincidencias)
            && ($estado['paso'] ?? null) === 'fecha') {
            $fecha = Carbon::createFromFormat('!Y-m-d', $coincidencias[1]);

            if (! $fecha || $fecha->format('Y-m-d') !== $coincidencias[1]
                || $fecha->format('Y-m') !== $this->mesEnEstado($estado)->format('Y-m')
                || $fecha->lt(today())
                || $fecha->gt(today()->addMonths(12)->endOfMonth())) {
                return 'Esa fecha ya no está disponible. Elige un día marcado en el calendario.';
            }

            return $this->buscarHorarios($chatId, $fecha->toDateString(), $estado);
        }

        if (preg_match('/^agenda:(servicio|tratamiento):(\d+)$/', $callbackData, $coincidencias)) {
            [, $tipo, $id] = $coincidencias;

            if (($estado['paso'] ?? null) !== $tipo) {
                return 'Esta opción ya venció. Continúa desde el último mensaje o escribe /agendar.';
            }

            if ($tipo === 'servicio') {
                $servicio = Servicio::where('activo', true)->find($id);

                return $servicio
                    ? $this->elegirServicio($chatId, $servicio->nombre)
                    : 'Ese servicio ya no está disponible. Escribe /agendar para elegir otro.';
            }

            $tratamiento = Tratamiento::where('activo', true)->find($id);
            $servicio = Servicio::find($estado['servicio_id'] ?? null);

            if (! $tratamiento || ! $servicio || ! $servicio->tratamientos()->where('tratamientos.id', $id)->exists()) {
                return 'Ese tratamiento ya no está disponible. Escribe /agendar para elegir otro.';
            }

            return $this->elegirTratamiento($chatId, $tratamiento->nombre, $estado);
        }

        if (preg_match('/^agenda:horario:(\d{2}:\d{2})$/', $callbackData, $coincidencias)) {
            return ($estado['paso'] ?? null) === 'horario'
                ? $this->elegirHorario($chatId, $coincidencias[1], $estado)
                : 'Esta opción ya venció. Escribe /agendar para comenzar de nuevo.';
        }

        if ($callbackData === 'agenda:motivo:omitir' && ($estado['paso'] ?? null) === 'motivo') {
            return $this->prepararConfirmacion($chatId, $estado, null);
        }

        if ($callbackData === 'agenda:confirmar:si' && ($estado['paso'] ?? null) === 'confirmacion') {
            return $this->confirmar($chatId, $estado, $callbackQueryId);
        }

        return 'Esta opción ya venció. Escribe /agendar para comenzar de nuevo.';
    }

    public function esperaTelefono(int|string $chatId): bool
    {
        return (Cache::get($this->clave($chatId))['paso'] ?? null) === 'telefono';
    }

    /** Devuelve null cuando el mensaje no trata de agenda. */
    public function responder(int|string $chatId, string $texto): ?string
    {
        if (mb_strtolower(trim($texto)) === '/cancelar') {
            Cache::forget($this->clave($chatId));

            return 'Cancelé la cita. No se guardó ningún registro.';
        }

        $estado = Cache::get($this->clave($chatId));

        if (! $estado) {
            return $this->esSolicitudDeAgenda($texto) ? $this->iniciar($chatId) : null;
        }

        return match ($estado['paso'] ?? null) {
            'servicio' => $this->elegirServicio($chatId, $texto),
            'tratamiento' => $this->elegirTratamiento($chatId, $texto, $estado),
            'fecha' => $this->buscarHorarios($chatId, $texto, $estado),
            'horario' => 'Toca uno de los horarios disponibles debajo del mensaje.',
            'nombre' => $this->guardarCampo($chatId, $texto, 'nombre', $estado),
            'apellido' => $this->guardarCampo($chatId, $texto, 'apellido', $estado),
            'telefono' => $this->guardarCampo($chatId, $texto, 'telefono', $estado),
            'email' => $this->guardarCampo($chatId, $texto, 'email', $estado),
            'motivo' => $this->prepararConfirmacion(
                $chatId,
                $estado,
                mb_strtolower(trim($texto)) === '/omitir' ? null : $texto,
            ),
            'confirmacion' => 'Revisa el resumen y toca «Confirmar cita» o «Cancelar».',
            default => $this->reiniciar($chatId),
        };
    }

    public function iniciar(int|string $chatId): string
    {
        Cache::put($this->clave($chatId), ['paso' => 'servicio'], now()->addMinutes(20));

        return "¡Vamos a encontrar tu cita! 😊\n\nTe guiaré paso a paso. Para reservar necesitaré tus datos de contacto; solo se guardarán al confirmar la cita. Puedes escribir /cancelar cuando quieras.\n\nPrimero, ¿qué tipo de atención necesitas? Elige una opción:";
    }

    private function elegirServicio(int|string $chatId, string $texto): string
    {
        $servicio = Servicio::where('activo', true)->orderBy('orden')->get()
            ->first(fn (Servicio $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $servicio) {
            return 'No identifiqué ese servicio. Toca una opción debajo o escribe su nombre.';
        }

        if (! $servicio->tratamientos()->where('tratamientos.activo', true)->exists()) {
            Cache::forget($this->clave($chatId));

            return 'Ese servicio no tiene tratamientos disponibles. Escribe /agendar para elegir otro.';
        }

        Cache::put($this->clave($chatId), [
            'paso' => 'tratamiento',
            'servicio_id' => $servicio->id,
        ], now()->addMinutes(20));

        return "Elegiste {$servicio->nombre}. ¿Qué tratamiento necesitas?";
    }

    /** @param array<string, mixed> $estado */
    private function elegirTratamiento(int|string $chatId, string $texto, array $estado): string
    {
        $servicio = Servicio::find($estado['servicio_id'] ?? null);
        $tratamiento = $servicio?->tratamientos()->where('tratamientos.activo', true)->get()
            ->first(fn (Tratamiento $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $tratamiento) {
            return 'No identifiqué ese tratamiento. Toca una opción debajo o escribe su nombre.';
        }

        Cache::put($this->clave($chatId), [
            'paso' => 'fecha',
            'servicio_id' => $servicio->id,
            'tratamiento_id' => $tratamiento->id,
            'mes' => today()->format('Y-m'),
        ], now()->addMinutes(20));

        return "Perfecto, {$tratamiento->nombre}. Elige una fecha en el calendario o escríbela como AAAA-MM-DD.";
    }

    /** @param array<string, mixed> $estado */
    private function buscarHorarios(int|string $chatId, string $texto, array $estado): string
    {
        try {
            $fecha = Carbon::createFromFormat('!Y-m-d', trim($texto));
        } catch (\Throwable) {
            return 'No pude leer la fecha. Escríbela como AAAA-MM-DD, por ejemplo 2026-10-20.';
        }

        if (! $fecha || $fecha->format('Y-m-d') !== trim($texto) || $fecha->lt(today())) {
            return 'Esa fecha no es válida o ya pasó. Elige un día del calendario o escríbela como AAAA-MM-DD.';
        }

        $tratamiento = Tratamiento::find($estado['tratamiento_id'] ?? null);

        if (! $tratamiento) {
            return $this->reiniciar($chatId);
        }

        $estado['paso'] = 'horario';
        $estado['fecha'] = $fecha->toDateString();
        Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

        $horarios = $this->disponibilidad->horariosDisponibles($estado['fecha'], $tratamiento);

        if (empty($horarios)) {
            $estado['paso'] = 'fecha';
            Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

            return 'No hay horarios libres ese día. Elige otra fecha en el calendario o escríbela como AAAA-MM-DD.';
        }

        return "Hay estos horarios para {$tratamiento->nombre} el {$fecha->format('d/m/Y')}. Elige uno:";
    }

    /** @param array<string, mixed> $estado */
    private function elegirHorario(int|string $chatId, string $hora, array $estado): string
    {
        $tratamiento = Tratamiento::find($estado['tratamiento_id'] ?? null);
        $horarios = $tratamiento && isset($estado['fecha'])
            ? $this->disponibilidad->horariosDisponibles($estado['fecha'], $tratamiento)
            : [];

        if (! in_array($hora, $horarios, true)) {
            return 'Ese horario ya no está disponible. Elige uno de los que siguen libres.';
        }

        $estado['paso'] = 'nombre';
        $estado['hora'] = $hora;
        Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

        return '¡Ya casi terminamos! Para registrar la cita, escribe solo tu nombre (sin apellidos).';
    }

    /** @param array<string, mixed> $estado */
    private function guardarCampo(int|string $chatId, string $texto, string $campo, array $estado): string
    {
        $reglas = match ($campo) {
            'nombre', 'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^(?:\\+?52[\\s().-]*)?(?:[0-9][\\s().-]*){10}$/', new TelefonoMexico],
            'email' => ['required', 'email', 'max:100'],
        };
        $validador = Validator::make([$campo => trim($texto)], [$campo => $reglas]);

        if ($validador->fails()) {
            return match ($campo) {
                'nombre' => 'Escribe solo tu nombre, sin apellidos (máximo 100 caracteres).',
                'apellido' => 'Escribe tus apellidos (máximo 100 caracteres).',
                'telefono' => 'Ingresa un teléfono de México con 10 dígitos; puedes incluir +52, espacios, guiones o paréntesis.',
                'email' => 'Escribe un correo electrónico válido.',
            };
        }

        $estado[$campo] = trim($texto);
        $siguiente = match ($campo) {
            'nombre' => ['apellido', 'Gracias. Ahora escribe tus apellidos.'],
            'apellido' => ['telefono', '¿Qué número puede usar el consultorio para contactarte? Puedes tocar «Compartir mi número» o escribirlo manualmente.'],
            'telefono' => ['email', '¿Cuál es tu correo electrónico?'],
            'email' => ['motivo', '¿Quieres agregar el motivo de la visita? Es opcional; puedes escribirlo o tocar «Omitir motivo».'],
        };
        $estado['paso'] = $siguiente[0];
        Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

        return $siguiente[1];
    }

    /** @param array<string, mixed> $estado */
    private function prepararConfirmacion(int|string $chatId, array $estado, ?string $motivo): string
    {
        if ($motivo !== null && mb_strlen(trim($motivo)) > 1000) {
            return 'El motivo debe tener máximo 1000 caracteres; puedes resumirlo o tocar «Omitir motivo». ';
        }

        $estado['motivo'] = $motivo === null || trim($motivo) === '' ? null : trim($motivo);
        $estado['paso'] = 'confirmacion';
        Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

        return $this->resumen($estado)."\n\n¿Confirmas la cita? No se guardará hasta que toques «Confirmar cita».\n\n"
            .'Al tocar «Confirmar cita» aceptas el aviso de privacidad ('.route('privacidad.aviso').') '
            .'y das tu consentimiento para el tratamiento de tus datos, incluidos los de salud.';
    }

    /** @param array<string, mixed> $estado */
    private function confirmar(int|string $chatId, array $estado, ?string $callbackQueryId): string
    {
        if (! $callbackQueryId) {
            return 'No pude validar la confirmación. Toca «Confirmar cita» otra vez.';
        }

        $resultado = $this->crearCita->crear([
            'id_servicio' => $estado['servicio_id'],
            'id_tratamiento' => $estado['tratamiento_id'],
            'fecha' => $estado['fecha'],
            'hora' => $estado['hora'],
            'nombre' => $estado['nombre'],
            'apellido' => $estado['apellido'],
            'telefono' => $estado['telefono'],
            'email' => $estado['email'],
            'motivo' => $estado['motivo'] ?? null,
            // Tocar «Confirmar cita» después de leer el aviso es el consentimiento.
            'acepta_aviso' => true,
        ], 'telegram', 'telegram:'.$callbackQueryId);

        if (! $resultado['cita']) {
            $tratamiento = Tratamiento::find($estado['tratamiento_id']);
            $horarios = $tratamiento
                ? $this->disponibilidad->horariosDisponibles($estado['fecha'], $tratamiento)
                : [];

            if ($horarios) {
                $estado['paso'] = 'horario';
                Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

                return 'Alguien reservó ese horario mientras completabas tus datos. Elige otro que siga disponible:';
            }

            $estado['paso'] = 'fecha';
            unset($estado['hora']);
            Cache::put($this->clave($chatId), $estado, now()->addMinutes(20));

            return 'Ese horario ya no está disponible y no se creó la cita. Elige otra fecha en el calendario.';
        }

        Cache::forget($this->clave($chatId));
        $cita = $resultado['cita']->load(['paciente', 'tratamiento']);

        if ($resultado['creada']) {
            $this->notificaciones->citaConfirmada($cita);
        }

        return "¡Listo, tu cita quedó confirmada!\n\n"
            ."{$cita->tratamiento->nombre}\n"
            .$cita->fecha_hora->translatedFormat('d/m/Y, H:i')."\n"
            .'Te esperamos. Si necesitas cambiarla, comunícate con el consultorio.';
    }

    /** @param array<string, mixed> $estado */
    private function resumen(array $estado): string
    {
        $tratamiento = Tratamiento::find($estado['tratamiento_id']);

        return "Revisa los datos de tu cita\n\n"
            .'Tratamiento: '.($tratamiento?->nombre ?? 'No disponible')."\n"
            .'Fecha y hora: '.Carbon::parse($estado['fecha'].' '.$estado['hora'])->format('d/m/Y, H:i')."\n"
            .'Nombre: '.$estado['nombre'].' '.$estado['apellido']."\n"
            .'Teléfono: '.$this->telefonoEnmascarado($estado['telefono'])."\n"
            .'Correo: '.$this->correoEnmascarado($estado['email']);
    }

    private function telefonoEnmascarado(string $telefono): string
    {
        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';

        return '•••• '.substr($digitos, -4);
    }

    private function correoEnmascarado(string $correo): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');

        return mb_substr($usuario, 0, 1).'•••@'.$dominio;
    }

    /** @param array<string, mixed> $estado
     * @return array<int, array<int, array{text: string, callback_data: string}>>
     */
    private function filasHorarios(array $estado): array
    {
        $tratamiento = Tratamiento::find($estado['tratamiento_id'] ?? null);
        $horarios = $tratamiento && isset($estado['fecha'])
            ? $this->disponibilidad->horariosDisponibles($estado['fecha'], $tratamiento)
            : [];

        return collect($horarios)->chunk(3)->map(fn ($fila) => $fila
            ->map(fn (string $hora) => $this->boton($hora, "agenda:horario:{$hora}"))
            ->values()->all())->values()->all();
    }

    /** @param array<string, mixed> $estado
     * @return array<int, array<int, array{text: string, callback_data: string}>>
     */
    private function filasCalendario(array $estado): array
    {
        $mes = $this->mesEnEstado($estado);
        $actual = today()->startOfMonth();
        $limite = today()->startOfMonth()->addMonths(12);
        $filas = [[
            $this->boton($mes->gt($actual) ? '‹' : '·', $mes->gt($actual) ? 'agenda:calendario:-1' : 'agenda:sin-accion'),
            $this->boton($this->nombreMes($mes), 'agenda:sin-accion'),
            $this->boton($mes->lt($limite) ? '›' : '·', $mes->lt($limite) ? 'agenda:calendario:+1' : 'agenda:sin-accion'),
        ]];
        $filas[] = array_map(fn (string $dia) => $this->boton($dia, 'agenda:sin-accion'), ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do']);

        $desplazamiento = $mes->dayOfWeekIso - 1;
        $diasDelMes = $mes->daysInMonth;
        $celdas = [];

        for ($i = 0; $i < $desplazamiento; $i++) {
            $celdas[] = $this->boton('·', 'agenda:sin-accion');
        }

        for ($dia = 1; $dia <= $diasDelMes; $dia++) {
            $fecha = $mes->copy()->day($dia);
            $seleccionable = $fecha->gte(today()) && $fecha->lte(today()->addMonths(12)->endOfMonth());
            $celdas[] = $this->boton(
                $seleccionable ? (string) $dia : '·',
                $seleccionable ? 'agenda:fecha:'.$fecha->format('Y-m-d') : 'agenda:sin-accion',
            );

            if (count($celdas) === 7) {
                $filas[] = $celdas;
                $celdas = [];
            }
        }

        if ($celdas) {
            while (count($celdas) < 7) {
                $celdas[] = $this->boton('·', 'agenda:sin-accion');
            }
            $filas[] = $celdas;
        }

        return $filas;
    }

    /** @param array<string, mixed> $estado */
    private function mesEnEstado(array $estado): Carbon
    {
        try {
            $mes = Carbon::createFromFormat('!Y-m', (string) ($estado['mes'] ?? today()->format('Y-m')));
        } catch (\Throwable) {
            $mes = null;
        }

        return $mes ?: today()->startOfMonth();
    }

    private function nombreMes(Carbon $fecha): string
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return ucfirst($meses[$fecha->month]).' '.$fecha->year;
    }

    /** @return array{text: string, callback_data: string} */
    private function boton(string $texto, string $callbackData): array
    {
        return ['text' => $texto, 'callback_data' => $callbackData];
    }

    private function esSolicitudDeAgenda(string $texto): bool
    {
        $texto = $this->normalizar($texto);

        return Str::contains($texto, ['cita', 'horario', 'disponib', 'agendar', 'reservar']);
    }

    private function normalizar(string $texto): string
    {
        return trim(Str::ascii(Str::lower($texto)));
    }

    private function reiniciar(int|string $chatId): string
    {
        Cache::forget($this->clave($chatId));

        return 'La consulta venció. Escribe /agendar para comenzar de nuevo.';
    }

    private function clave(int|string $chatId): string
    {
        return "dentissa.telegram.agenda.{$chatId}";
    }
}
