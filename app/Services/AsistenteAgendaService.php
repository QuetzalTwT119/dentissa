<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Flujo de consulta de horarios del widget web (no crea ni reserva citas). */
class AsistenteAgendaService
{
    public function __construct(private readonly DisponibilidadService $disponibilidad) {}

    public function tieneConsultaEnCurso(int|string $conversacion): bool
    {
        return Cache::has($this->clave($conversacion));
    }

    public function responder(int|string $conversacion, string $texto): ?string
    {
        if (mb_strtolower(trim($texto)) === '/cancelar') {
            Cache::forget($this->clave($conversacion));

            return 'Cancelé la consulta de horarios. Cuando quieras, escribe que buscas una cita.';
        }

        $estado = Cache::get($this->clave($conversacion));

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

        return "Claro. ¿Qué tipo de atención necesitas?\n\n".$this->listaServicios();
    }

    private function elegirServicio(int|string $conversacion, string $texto): string
    {
        $servicio = Servicio::where('activo', true)->orderBy('orden')->get()
            ->first(fn (Servicio $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $servicio) {
            return "No identifiqué ese tipo de atención. Escríbelo tal como aparece aquí:\n\n".$this->listaServicios();
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

        return "Perfecto. Elige el tratamiento escribiendo su nombre:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n");
    }

    /** @param array<string, mixed> $estado */
    private function elegirTratamiento(int|string $conversacion, string $texto, array $estado): string
    {
        $servicio = Servicio::find($estado['servicio_id'] ?? null);
        $tratamientos = $servicio?->tratamientos()->where('activo', true)->orderBy('nombre')->get() ?? collect();
        $tratamiento = $tratamientos->first(fn (Tratamiento $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $tratamiento) {
            return "No identifiqué ese tratamiento. Escríbelo tal como aparece aquí:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n");
        }

        Cache::put($this->clave($conversacion), [
            'paso' => 'fecha',
            'servicio_id' => $servicio->id,
            'tratamiento_id' => $tratamiento->id,
        ], now()->addMinutes(15));

        return '¿Para qué fecha lo buscas? Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.';
    }

    /** @param array<string, mixed> $estado */
    private function buscarHorarios(int|string $conversacion, string $texto, array $estado): string
    {
        $fecha = $this->interpretarFecha($texto);

        if (! $fecha) {
            return 'No reconocí la fecha. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.';
        }

        if ($fecha->lt(today())) {
            return 'La fecha debe ser hoy o posterior. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.';
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
