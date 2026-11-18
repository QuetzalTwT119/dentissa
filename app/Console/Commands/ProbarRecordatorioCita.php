<?php

namespace App\Console\Commands;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Console\Command;

/**
 * Manda AHORA el recordatorio de una cita, sin esperar la ventana de 24 h.
 *
 * Sirve para probar la plantilla cita_recordatorio en cuanto Meta la aprueba
 * y como plan B en la demo. Envía mensajes reales, así que sin --confirmar
 * solo muestra qué haría.
 *
 *   php artisan dentissa:probar-recordatorio 42
 *   php artisan dentissa:probar-recordatorio 42 --confirmar
 */
class ProbarRecordatorioCita extends Command
{
    protected $signature = 'dentissa:probar-recordatorio {cita : ID de la cita} {--confirmar : Envía de verdad el recordatorio}';

    protected $description = 'Envía en este momento el recordatorio (WhatsApp y correo) de una cita, para probar la plantilla';

    public function handle(NotificacionService $notificaciones): int
    {
        $cita = Cita::with(['paciente', 'tratamiento'])->find($this->argument('cita'));

        if (! $cita || ! $cita->paciente) {
            $this->error('No existe una cita con ese ID, o no tiene paciente.');

            return self::FAILURE;
        }

        if (! in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true) || ! $cita->fecha_hora->isFuture()) {
            $this->error("La cita {$cita->id} no admite recordatorio: debe estar Agendada o Confirmada y ser futura.");

            return self::FAILURE;
        }

        // Datos enmascarados: la salida puede terminar en una captura.
        $this->line("Cita {$cita->id}: ".($cita->tratamiento?->nombre ?? 'sin tratamiento').', '
            .$cita->fecha_hora->copy()->locale('es')->translatedFormat('d M Y, H:i'));
        $this->line('Paciente: '.$cita->paciente->nombre);
        $this->line('  WhatsApp: '.$this->enmascarar((string) $cita->paciente->telefono, 4));
        $this->line('  Correo:   '.$this->enmascararCorreo((string) $cita->paciente->email));

        if (! $this->option('confirmar')) {
            $this->warn('No se envió nada. Repite el comando con --confirmar para mandar el recordatorio de verdad.');

            return self::SUCCESS;
        }

        $antes = Notificacion::where('id_cita', $cita->id)->max('id') ?? 0;
        $notificaciones->citaRecordatorio($cita);
        // Igual que el envío programado: así no llega un segundo recordatorio.
        $cita->forceFill(['recordatorio_enviado_en' => now()])->save();

        $canales = Notificacion::where('id_cita', $cita->id)->where('id', '>', $antes)->get()
            ->map(fn (Notificacion $n) => $n->getRawOriginal('tipo'))->filter()->values()->all();

        if ($canales === []) {
            $this->error('No salió por ningún canal. Revisa php artisan dentissa:diagnostico --remoto y el log (plantilla no aprobada, token o correo).');

            return self::FAILURE;
        }

        $this->info('Recordatorio enviado por: '.implode(' y ', $canales).'.');
        if (! in_array('whatsapp', $canales, true)) {
            $this->warn('WhatsApp no salió: revisa que la plantilla cita_recordatorio esté aprobada y que el paciente tenga teléfono.');
        }

        return self::SUCCESS;
    }

    private function enmascarar(string $texto, int $visibles): string
    {
        $digitos = preg_replace('/\D/', '', $texto);

        return $digitos === '' ? 'sin teléfono' : str_repeat('•', max(0, strlen($digitos) - $visibles)).substr($digitos, -$visibles);
    }

    private function enmascararCorreo(string $correo): string
    {
        if (! str_contains($correo, '@')) {
            return 'sin correo';
        }
        [$usuario, $dominio] = explode('@', $correo, 2);

        return substr($usuario, 0, 1).'•••@'.$dominio;
    }
}
