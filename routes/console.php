<?php

use App\Services\Respaldo\CifradoRespaldo;
use App\Services\WhatsappService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whatsapp:probar {telefono} {--confirmar}', function (WhatsappService $whatsapp) {
    if (! $this->option('confirmar')) {
        $this->error('No se envió nada. Repite el comando agregando --confirmar para enviar hello_world al teléfono indicado.');

        return self::FAILURE;
    }

    // La plantilla de prueba de Meta está en inglés; no cambiar la preferencia
    // es_MX usada por las notificaciones normales de citas.
    config(['dentissa.whatsapp.plantilla_idioma' => 'en_US']);

    if (! $whatsapp->enviarPlantilla($this->argument('telefono'), 'hello_world')) {
        $this->error('Meta no aceptó el mensaje de prueba. Revisa credenciales, destinatario permitido y el log de Laravel.');

        return self::FAILURE;
    }

    $this->info('Meta aceptó el mensaje hello_world. Confirma su recepción en WhatsApp.');

    return self::SUCCESS;
})->purpose('Envía la plantilla oficial hello_world a un destinatario autorizado de prueba');

// Recordatorio 24 h antes de cada cita. withoutOverlapping evita que dos
// ejecuciones lentas se encimen; el comando además reclama cada cita antes
// de avisar, así que nunca se manda el mismo recordatorio dos veces.
Schedule::command('dentissa:enviar-recordatorios')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Respaldo cifrado diario a Google Drive, de madrugada para no competir con
// la agenda. Si falla, el comando avisa al administrador.
Schedule::command('dentissa:respaldar')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Artisan::command('dentissa:respaldo-llave', function () {
    $this->warn('Esta llave descifra todos los respaldos. Guárdala en un gestor de contraseñas y en DENTISSA_RESPALDO_CLAVE; si se pierde, los respaldos no se pueden recuperar. No la compartas ni la subas a Git.');
    $this->line(CifradoRespaldo::generarLlave());
})->purpose('Genera una llave nueva para cifrar los respaldos');
