<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Oferta;
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
    public function crear(array $datos, string $canal, ?string $claveIdempotencia = null, ?Request $request = null, string $eventoInterno = 'confirmada'): array
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
            return DB::transaction(function () use ($datos, $canal, $claveIdempotencia, $request, $tratamiento, $fechaHora, $eventoInterno) {
                if (! $this->disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
                    return ['cita' => null, 'creada' => false];
                }

                $paciente = $this->buscarOCrearPaciente($datos);

                // Evidencia del consentimiento: fecha y versión del aviso aceptado.
                // Al reagendar con el aviso ya aceptado se conserva la fecha original.
                if (! ($datos['aviso_previo'] ?? false) || $paciente->aviso_privacidad_aceptado_en === null) {
                    $paciente->forceFill([
                        'aviso_privacidad_aceptado_en' => now(),
                        'aviso_privacidad_version' => config('dentissa.privacidad.version'),
                    ])->save();
                }

                // Solo la oferta que el paciente eligió (23/11/2026); antes se aplicaba sola.
                $oferta = filled($datos['id_oferta'] ?? null)
                    ? $this->ofertaConLugar((int) $datos['id_oferta'], $tratamiento->id, $fechaHora)
                    : null;

                $cita = Cita::create([
                    'id_paciente' => $paciente->id,
                    'id_servicio' => $datos['id_servicio'],
                    'id_tratamiento' => $tratamiento->id,
                    // La oferta vigente del tratamiento queda en la cita: la doctora
                    // la ve en la agenda y la aplica al cobrar (21/11/2026).
                    'id_oferta' => $oferta?->id,
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
                    'id_oferta' => $oferta?->id,
                ], request: $request);
                $this->notificacionesInternas->cambioCita(
                    $cita,
                    $eventoInterno,
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

    /**
     * Oferta que se aplica a la cita, con su fila bloqueada mientras se guarda:
     * dos pacientes que agendan a la vez no pueden tomar el último lugar.
     */
    private function ofertaConLugar(int $idOferta, int $idTratamiento, Carbon $fechaHora): ?Oferta
    {
        $oferta = Oferta::publicadas()
            ->whereKey($idOferta)
            ->where('id_tratamiento', $idTratamiento)
            ->whereDate('fecha_fin', '>=', $fechaHora->toDateString())
            ->lockForUpdate()
            ->first();

        return $oferta && $oferta->lugaresQuedan() !== 0 ? $oferta : null;
    }

    /**
     * El correo es opcional desde el 20/11/2026: se busca por correo si lo hay;
     * si no, por celular (el que no tiene correo vuelve a ser el mismo paciente).
     * Si se encontró por celular y ahora dio correo, se le guarda.
     */
    private function buscarOCrearPaciente(array $datos): Paciente
    {
        $email = trim((string) ($datos['email'] ?? '')) ?: null;

        $paciente = $email ? Paciente::where('email', $email)->first() : null;
        $paciente ??= Paciente::where('telefono', $datos['telefono'])
            ->when($email, fn ($q) => $q->whereNull('email'))
            ->first();

        if (! $paciente) {
            return Paciente::create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => $datos['telefono'],
                'email' => $email,
            ]);
        }

        if ($email && ! $paciente->email) {
            $paciente->update(['email' => $email]);
        }

        return $paciente;
    }
}
