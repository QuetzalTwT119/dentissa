<?php

namespace App\Services;

use App\Models\HorarioAtencion;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Flujo de consulta de horarios del widget web (no crea ni reserva citas). */
class AsistenteAgendaService
{
    /** Se agrega a cada paso del flujo para que la persona sepa cómo salir. */
    private const AYUDA_SALIR = "\n\nSi quieres salir, escribe \"cancelar\".";

    public function __construct(private readonly DisponibilidadService $disponibilidad) {}

    public function tieneConsultaEnCurso(int|string $conversacion): bool
    {
        return Cache::has($this->clave($conversacion));
    }

    public function responder(int|string $conversacion, string $texto): ?string
    {
        $estado = Cache::get($this->clave($conversacion));
        $comando = $this->normalizar($texto);

        // "/cancelar" funciona siempre; "cancelar" y "salir" solo con una búsqueda abierta,
        // para no interceptar mensajes de quien no está en el flujo.
        if ($comando === '/cancelar' || ($estado && in_array($comando, ['cancelar', 'salir'], true))) {
            Cache::forget($this->clave($conversacion));

            return 'Cancelé la consulta de horarios. Cuando quieras, escribe que buscas una cita.';
        }

        // "¿Cuál es el horario de atención?" pide información, no una reserva; si hay
        // una búsqueda abierta, se responde sin perderla.
        if ($this->preguntaPorHorarioDeAtencion($texto)) {
            $horario = 'Nuestro horario de atención: '.HorarioAtencion::resumenSemanal();

            return $estado
                ? $horario."\n\nSeguimos con tu búsqueda de horarios: responde la pregunta anterior o escribe \"cancelar\" para salir."
                : $horario."\n\nSi quieres buscar una cita, escribe \"quiero una cita\".";
        }

        if (! $estado) {
            if (! $this->esSolicitudDeAgenda($texto)) {
                return null;
            }

            return $this->iniciar($conversacion);
        }

        return match ($estado['paso'] ?? null) {
            'servicio' => $this->elegirServicio($conversacion, $texto),
            'tratamiento' => $this->elegirTratamiento($conversacion, $texto, $estado),
            'fecha' => $this->buscarHorarios($conversacion, $texto, $estado),
            default => $this->reiniciar($conversacion),
        };
    }

    private function iniciar(int|string $conversacion): string
    {
        Cache::put($this->clave($conversacion), ['paso' => 'servicio'], now()->addMinutes(15));

        return "Claro. ¿Qué tipo de atención necesitas?\n\n".$this->listaServicios().self::AYUDA_SALIR;
    }

    private function elegirServicio(int|string $conversacion, string $texto): string
    {
        $servicio = Servicio::where('activo', true)->orderBy('orden')->get()
            ->first(fn (Servicio $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $servicio) {
            return "No identifiqué ese tipo de atención. Escríbelo tal como aparece aquí:\n\n".$this->listaServicios().self::AYUDA_SALIR;
        }

        $tratamientos = $servicio->tratamientos()->where('activo', true)->orderBy('nombre')->get();

        if ($tratamientos->isEmpty()) {
            Cache::forget($this->clave($conversacion));

            return 'Ese servicio no tiene tratamientos disponibles por el momento. Intenta con otro servicio.';
        }

        Cache::put($this->clave($conversacion), [
            'paso' => 'tratamiento',
            'servicio_id' => $servicio->id,
        ], now()->addMinutes(15));

        return "Perfecto. Elige el tratamiento escribiendo su nombre:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n").self::AYUDA_SALIR;
    }

    /** @param array<string, mixed> $estado */
    private function elegirTratamiento(int|string $conversacion, string $texto, array $estado): string
    {
        $servicio = Servicio::find($estado['servicio_id'] ?? null);
        $tratamientos = $servicio?->tratamientos()->where('activo', true)->orderBy('nombre')->get() ?? collect();
        $tratamiento = $tratamientos->first(fn (Tratamiento $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $tratamiento) {
            return "No identifiqué ese tratamiento. Escríbelo tal como aparece aquí:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n").self::AYUDA_SALIR;
        }

        Cache::put($this->clave($conversacion), [
            'paso' => 'fecha',
            'servicio_id' => $servicio->id,
            'tratamiento_id' => $tratamiento->id,
        ], now()->addMinutes(15));

        return '¿Para qué fecha lo buscas? Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.'.self::AYUDA_SALIR;
    }

    /** @param array<string, mixed> $estado */
    private function buscarHorarios(int|string $conversacion, string $texto, array $estado): string
    {
        $fecha = $this->interpretarFecha($texto);

        if (! $fecha) {
            return 'No reconocí la fecha. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.'.self::AYUDA_SALIR;
        }

        if ($fecha->lt(today())) {
            return 'La fecha debe ser hoy o posterior. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.'.self::AYUDA_SALIR;
        }

        $tratamiento = Tratamiento::find($estado['tratamiento_id'] ?? null);

        if (! $tratamiento) {
            return $this->reiniciar($conversacion);
        }

        $horarios = $this->disponibilidad->horariosDisponibles($fecha->toDateString(), $tratamiento);
        Cache::forget($this->clave($conversacion));

        if (empty($horarios)) {
            return 'No hay horarios disponibles para esa fecha. Puedes iniciar otra consulta escribiendo que buscas una cita.';
        }

        return "Horarios disponibles para {$tratamiento->nombre} el {$fecha->format('d/m/Y')}:\n\n"
            .collect($horarios)->map(fn ($horario) => "• {$horario}")->join("\n")
            ."\n\nPara reservar, entra a ".rtrim(config('app.url'), '/').'/agendar-cita.';
    }

    private function listaServicios(): string
    {
        return Servicio::where('activo', true)->orderBy('orden')->pluck('nombre')
            ->map(fn ($nombre) => "• {$nombre}")
            ->join("\n");
    }

    private function esSolicitudDeAgenda(string $texto): bool
    {
        return Str::contains($this->normalizar($texto), ['cita', 'horario', 'disponib', 'agendar', 'reservar']);
    }

    private function preguntaPorHorarioDeAtencion(string $texto): bool
    {
        $texto = $this->normalizar($texto);

        if (Str::contains($texto, ['cita', 'disponib', 'agendar', 'reservar', 'turno'])) {
            return false;
        }

        return Str::contains($texto, [
            'horario de atencion',
            'horarios de atencion',
            'a que hora abren',
            'a que hora cierran',
            'hasta que hora',
            'que dias abren',
            'que dias atienden',
            'estan abiertos',
        ]);
    }

    private function interpretarFecha(string $texto): ?Carbon
    {
        $texto = trim($texto);
        $textoNormalizado = $this->normalizar($texto);

        if (in_array($textoNormalizado, ['hoy', 'para hoy'], true)) {
            return today();
        }

        if (in_array($textoNormalizado, ['manana', 'para manana'], true)) {
            return today()->addDay();
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat('!'.$formato, $texto);
                $errores = Carbon::getLastErrors();

                if ($fecha
                    && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
                    && $fecha->format($formato) === $texto) {
                    return $fecha->startOfDay();
                }
            } catch (\Throwable) {
                // Un formato inválido se trata como una fecha no reconocida.
            }
        }

        return null;
    }

    private function normalizar(string $texto): string
    {
        return trim(Str::ascii(Str::lower($texto)));
    }

    private function reiniciar(int|string $conversacion): string
    {
        Cache::forget($this->clave($conversacion));

        return 'La consulta anterior venció. Escribe que buscas una cita para comenzar de nuevo.';
    }

    private function clave(int|string $conversacion): string
    {
        return "dentissa.asistente.agenda.{$conversacion}";
    }
}
