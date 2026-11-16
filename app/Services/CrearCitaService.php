<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CrearCitaService
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
        private readonly CitaReglasService $reglas,
        private readonly AuditoriaService $auditoria,
        private readonly NotificacionInternaService $notificacionesInternas,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array{cita: Cita|null, creada: bool}
     */
    public function crear(array $datos, string $canal, ?string $claveIdempotencia = null, ?Request $request = null): array
    {
        // Web y Telegram piden datos personales y de salud: ningún canal puede
        // guardar una cita sin el consentimiento expreso al aviso de privacidad.
        if (($datos['acepta_aviso'] ?? false) !== true) {
            throw new InvalidArgumentException('Falta el consentimiento al aviso de privacidad.');
        }

        if ($claveIdempotencia !== null) {
            $existente = Cita::where('clave_idempotencia', $claveIdempotencia)->first();

            if ($existente) {
                return ['cita' => $existente, 'creada' => false];
            }
        }

        [, $tratamiento] = $this->reglas->validarCatalogo($datos['id_servicio'], $datos['id_tratamiento']);
        $fechaHora = Carbon::parse("{$datos['fecha']} {$datos['hora']}");
        $this->reglas->validarFechaFutura($fechaHora);

        try {
            return DB::transaction(function () use ($datos, $canal, $claveIdempotencia, $request, $tratamiento, $fechaHora) {
                if (! $this->disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
                    return ['cita' => null, 'creada' => false];
                }

                $paciente = Paciente::firstOrCreate(
                    ['email' => $datos['email']],
                    [
                        'nombre' => $datos['nombre'],
                        'apellido' => $datos['apellido'],
                        'telefono' => $datos['telefono'],
                    ],
                );

                // Evidencia del consentimiento: fecha y versión del aviso aceptado.
                $paciente->forceFill([
                    'aviso_privacidad_aceptado_en' => now(),
                    'aviso_privacidad_version' => config('dentissa.privacidad.version'),
                ])->save();

                $cita = Cita::create([
                    'id_paciente' => $paciente->id,
                    'id_servicio' => $datos['id_servicio'],
                    'id_tratamiento' => $tratamiento->id,
                    'fecha_hora' => $fechaHora,
                    'duracion_estimada' => $tratamiento->duracion_estimada,
                    'motivo' => $datos['motivo'] ?? null,
                    'clave_idempotencia' => $claveIdempotencia,
                    'estado' => EstadoCita::Confirmada,
                    // La disponibilidad se valida antes de guardar; la reserva queda
                    // confirmada de inmediato, sin requerir una segunda aprobación.
                    'fecha_confirmacion' => now(),
                ]);

                $this->auditoria->registrar('cita.creada', $cita, datosNuevos: [
                    'estado' => $cita->estado?->value ?? EstadoCita::Confirmada->value,
                    'fecha_hora' => $cita->fecha_hora->toIso8601String(),
                    'canal' => $canal,
                ], request: $request);
                $this->notificacionesInternas->cambioCita(
                    $cita,
                    'confirmada',
                );

                return ['cita' => $cita, 'creada' => true];
            });
        } catch (QueryException $e) {
            // Telegram puede volver a entregar el mismo callback si el worker
            // se interrumpe después del commit. La clave única evita duplicar
            // la cita; en ese caso devolvemos la que ya quedó registrada.
            if ($claveIdempotencia !== null) {
                $existente = Cita::where('clave_idempotencia', $claveIdempotencia)->first();

                if ($existente) {
                    return ['cita' => $existente, 'creada' => false];
                }
            }

            throw $e;
        }
    }
}
