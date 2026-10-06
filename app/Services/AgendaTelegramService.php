<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AgendaTelegramService
{
    public function __construct(private readonly DisponibilidadService $disponibilidad)
    {
    }

    public function tieneConsultaEnCurso(int|string $chatId): bool
    {
        return Cache::has($this->clave($chatId));
    }

    /** Devuelve null cuando el mensaje no trata de agenda. */
    public function responder(int|string $chatId, string $texto): ?string
    {
        if (mb_strtolower(trim($texto)) === '/cancelar') {
            Cache::forget($this->clave($chatId));

            return 'Cancelé la consulta de horarios. Cuando quieras, escribe que buscas una cita.';
        }

        $estado = Cache::get($this->clave($chatId));

        if (! $estado) {
            if (! $this->esSolicitudDeAgenda($texto)) {
                return null;
            }

            Cache::put($this->clave($chatId), ['paso' => 'servicio'], now()->addMinutes(15));

            return "Claro. ¿Qué tipo de atención necesitas?\n\n".$this->listaServicios();
        }

        return match ($estado['paso'] ?? null) {
            'servicio' => $this->elegirServicio($chatId, $texto),
            'tratamiento' => $this->elegirTratamiento($chatId, $texto, $estado),
            'fecha' => $this->buscarHorarios($chatId, $texto, $estado),
            default => $this->reiniciar($chatId),
        };
    }

    private function elegirServicio(int|string $chatId, string $texto): string
    {
        $servicios = Servicio::where('activo', true)->orderBy('orden')->get();
        $servicio = $servicios->first(fn (Servicio $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $servicio) {
            return "No identifiqué ese tipo de atención. Escríbelo tal como aparece aquí:\n\n".$this->listaServicios();
        }

        $tratamientos = $servicio->tratamientos()->where('activo', true)->orderBy('nombre')->get();

        if ($tratamientos->isEmpty()) {
            Cache::forget($this->clave($chatId));

            return 'Ese servicio no tiene tratamientos disponibles por el momento. Intenta con otro servicio.';
        }

        Cache::put($this->clave($chatId), [
            'paso' => 'tratamiento',
            'servicio_id' => $servicio->id,
        ], now()->addMinutes(15));

        return "Perfecto. Elige el tratamiento escribiendo su nombre:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n");
    }

    /** @param array<string, mixed> $estado */
    private function elegirTratamiento(int|string $chatId, string $texto, array $estado): string
    {
        $servicio = Servicio::find($estado['servicio_id'] ?? null);
        $tratamientos = $servicio?->tratamientos()->where('activo', true)->orderBy('nombre')->get() ?? collect();
        $tratamiento = $tratamientos->first(fn (Tratamiento $item) => $this->normalizar($item->nombre) === $this->normalizar($texto));

        if (! $tratamiento) {
            return "No identifiqué ese tratamiento. Escríbelo tal como aparece aquí:\n\n".$tratamientos->pluck('nombre')->map(fn ($nombre) => "• {$nombre}")->join("\n");
        }

        Cache::put($this->clave($chatId), [
            'paso' => 'fecha',
            'servicio_id' => $servicio->id,
            'tratamiento_id' => $tratamiento->id,
        ], now()->addMinutes(15));

        return '¿Para qué fecha lo buscas? Escríbela con este formato: AAAA-MM-DD.';
    }

    /** @param array<string, mixed> $estado */
    private function buscarHorarios(int|string $chatId, string $texto, array $estado): string
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m-d', trim($texto))->startOfDay();
        } catch (\Throwable) {
            return 'No pude leer la fecha. Escríbela así: AAAA-MM-DD.';
        }

        if ($fecha->lt(today())) {
            return 'La fecha debe ser hoy o posterior. Escríbela así: AAAA-MM-DD.';
        }

        $tratamiento = Tratamiento::find($estado['tratamiento_id'] ?? null);

        if (! $tratamiento) {
            return $this->reiniciar($chatId);
        }

        $horarios = $this->disponibilidad->horariosDisponibles($fecha->toDateString(), $tratamiento);
        Cache::forget($this->clave($chatId));

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

        return 'La consulta anterior venció. Escribe que buscas una cita para comenzar de nuevo.';
    }

    private function clave(int|string $chatId): string
    {
        return "dentissa.telegram.agenda.{$chatId}";
    }
}
