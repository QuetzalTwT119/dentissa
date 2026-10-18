<?php

use App\Services\WhatsappService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
