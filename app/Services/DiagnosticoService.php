<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Models\User;
use App\Services\Google\GoogleAccessToken;
use App\Services\Respaldo\DestinoRespaldo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Revisa que el servidor esté bien configurado para operar con pacientes
 * reales. Solo informa si cada dato existe y si funciona: NUNCA devuelve el
 * valor de un secreto (tokens, llaves, contraseñas).
 *
 * Las revisiones locales solo leen la configuración. Las remotas
 * (`remoto: true`) preguntan a Meta y a Google si las credenciales sirven.
 */
class DiagnosticoService
{
    public const OK = 'ok';

    public const AVISO = 'aviso';

    public const ERROR = 'error';

    /** @var list<array{area: string, estado: string, detalle: string}> */
    private array $resultados = [];

    public function __construct(private DestinoRespaldo $destino) {}

    /** @return list<array{area: string, estado: string, detalle: string}> */
    public function ejecutar(bool $remoto = false): array
    {
        $this->resultados = [];

        $this->aplicacion();
        $this->sesiones();
        $this->baseDeDatos();
        $this->almacenamiento();
        $this->correoYGoogle();
        $this->respaldos();
        $this->whatsapp();
        $this->privacidad();
        $this->seguridad();
        $this->telegram();

        if ($remoto) {
            $this->whatsappRemoto();
            $this->googleRemoto();
        }

        return $this->resultados;
    }

    private function aplicacion(): void
    {
        $produccion = app()->environment('production');

        filled(config('app.key'))
            ? $this->ok('Aplicación', 'APP_KEY configurada.')
            : $this->error('Aplicación', 'Falta APP_KEY: las sesiones y los datos cifrados no funcionan.');

        if ($produccion && config('app.debug')) {
            $this->error('Aplicación', 'APP_DEBUG está activo en producción: los errores mostrarían datos internos. Ponlo en false.');
        } else {
            $this->ok('Aplicación', 'APP_DEBUG adecuado para el entorno ('.app()->environment().').');
        }

        if ($produccion && ! str_starts_with((string) config('app.url'), 'https://')) {
            $this->aviso('Aplicación', 'APP_URL no usa https: los enlaces de los correos y del recordatorio serían inseguros.');
        }

        config('app.name') === 'Dentissa'
            ? $this->ok('Aplicación', 'APP_NAME es Dentissa.')
            : $this->aviso('Aplicación', 'APP_NAME no es Dentissa: así aparece en la pestaña del navegador y en los correos.');
    }

    private function sesiones(): void
    {
        if (app()->environment('production') && ! config('session.secure')) {
            $this->error('Sesiones', 'SESSION_SECURE_COOKIE no está en true: la cookie de sesión podría viajar sin HTTPS.');
        }

        config('session.encrypt')
            ? $this->ok('Sesiones', 'Sesiones cifradas (SESSION_ENCRYPT).')
            : $this->aviso('Sesiones', 'SESSION_ENCRYPT no está en true.');

        (int) config('session.lifetime') <= 30
            ? $this->ok('Sesiones', 'La sesión se cierra tras '.(int) config('session.lifetime').' min sin actividad.')
            : $this->aviso('Sesiones', 'SESSION_LIFETIME es '.(int) config('session.lifetime').' min; para datos clínicos se recomienda 30.');
    }

    private function baseDeDatos(): void
    {
        try {
            DB::select('select 1');
            $this->ok('Base de datos', 'Conexión correcta ('.DB::connection()->getDriverName().').');
        } catch (Throwable) {
            $this->error('Base de datos', 'No hay conexión con la base de datos.');

            return;
        }

        $migrador = app('migrator');
        $archivos = array_keys($migrador->getMigrationFiles(array_merge([database_path('migrations')], $migrador->paths())));
        $pendientes = array_diff($archivos, $migrador->getRepository()->getRan());

        $pendientes === []
            ? $this->ok('Base de datos', 'Todas las migraciones aplicadas.')
            : $this->error('Base de datos', count($pendientes).' migraciones pendientes: ejecuta php artisan migrate.');
    }

    private function almacenamiento(): void
    {
        $privado = storage_path('app/private');

        is_dir($privado) && is_writable($privado)
            ? $this->ok('Almacenamiento', 'storage/app/private existe y se puede escribir (imágenes clínicas).')
            : $this->error('Almacenamiento', 'No se puede escribir en storage/app/private: revisa el volumen y sus permisos.');
    }

    private function correoYGoogle(): void
    {
        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            app()->environment('production')
                ? $this->aviso('Correo', "MAIL_MAILER es {$mailer}: no se envía ningún correo real.")
                : $this->ok('Correo', "MAIL_MAILER es {$mailer} (no se envían correos reales en este entorno).");
        } else {
            $this->ok('Correo', "MAIL_MAILER es {$mailer}.");
        }

        if (str_ends_with((string) config('mail.from.address'), '@example.com')) {
            $this->aviso('Correo', 'MAIL_FROM_ADDRESS sigue con el valor de ejemplo.');
        }

        $enVariables = filled(config('services.gmail.client_id')) && filled(config('services.gmail.client_secret')) && filled(config('services.gmail.refresh_token'));
        $enArchivos = is_file((string) config('services.gmail.client_file')) && is_file((string) config('services.gmail.token_file'));

        $enVariables || $enArchivos
            ? $this->ok('Google', 'Credenciales de Google configuradas ('.($enVariables ? 'variables' : 'archivos privados').').')
            : $this->aviso('Google', 'Faltan las credenciales de Google: sin ellas no salen correos por Gmail ni respaldos a Drive.');
    }

    private function respaldos(): void
    {
        $llave = base64_decode((string) config('dentissa.respaldos.clave'), true);

        if ($llave !== false && strlen($llave) === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            $this->ok('Respaldos', 'Llave de cifrado válida. Copias por '.(int) config('dentissa.respaldos.dias_retencion').' días.');
        } else {
            $this->error('Respaldos', 'DENTISSA_RESPALDO_CLAVE falta o no es válida: el respaldo nocturno fallará. Genera una con php artisan dentissa:respaldo-llave.');
        }
    }

    private function whatsapp(): void
    {
        $variables = [
            'DENTISSA_WHATSAPP_PHONE_ID' => config('dentissa.whatsapp.phone_number_id'),
            'DENTISSA_WHATSAPP_TOKEN' => config('dentissa.whatsapp.access_token'),
            'DENTISSA_WHATSAPP_APP_SECRET' => config('dentissa.whatsapp.app_secret'),
            'DENTISSA_WHATSAPP_VERIFY_TOKEN' => config('dentissa.whatsapp.verify_token'),
        ];
        $faltan = array_keys(array_filter($variables, fn ($valor) => blank($valor)));

        if (count($faltan) === count($variables)) {
            $this->aviso('WhatsApp', 'WhatsApp no está configurado: los avisos salen solo por correo.');
        } elseif ($faltan !== []) {
            $this->error('WhatsApp', 'Faltan variables: '.implode(', ', $faltan).'.');
        } else {
            $this->ok('WhatsApp', 'Número, token, clave secreta y token de verificación configurados.');
        }
    }

    private function privacidad(): void
    {
        $datos = ['responsable' => 'nombre del responsable', 'domicilio' => 'domicilio', 'correo' => 'correo para solicitudes'];
        $faltan = array_values(array_filter($datos, fn ($etiqueta, $clave) => blank(config("dentissa.privacidad.{$clave}")), ARRAY_FILTER_USE_BOTH));

        $faltan === []
            ? $this->ok('Privacidad', 'Datos del responsable completos.')
            : $this->aviso('Privacidad', 'El aviso de privacidad aún dice "Por completar": '.implode(', ', $faltan).'.');

        if (! config('dentissa.privacidad.revisado')) {
            $this->aviso('Privacidad', 'El aviso sigue como borrador: DENTISSA_AVISO_REVISADO=true solo tras la revisión legal.');
        }
    }

    private function seguridad(): void
    {
        if (! config('dentissa.seguridad.dos_pasos_admin_obligatorio')) {
            $this->aviso('Seguridad', 'La verificación en dos pasos del administrador está apagada (DENTISSA_2FA_ADMIN=false). Solo aceptable en una demo.');

            return;
        }

        try {
            $sinActivar = User::query()
                ->where('activo', true)
                ->where('rol', RolUsuario::Admin->value)
                ->whereNull('dos_pasos_confirmado_en')
                ->count();

            $sinActivar === 0
                ? $this->ok('Seguridad', 'Verificación en dos pasos obligatoria y activa en todos los administradores.')
                : $this->aviso('Seguridad', "{$sinActivar} administrador(es) aún no activan la verificación en dos pasos; se les pedirá al entrar.");
        } catch (Throwable) {
            // Sin tabla (migraciones pendientes): ya se reporta en Base de datos.
        }
    }

    private function telegram(): void
    {
        filled(config('services.telegram.bot_token'))
            ? $this->ok('Telegram', 'Token del bot configurado.')
            : $this->aviso('Telegram', 'Bot de Telegram sin token: no responderá.');
    }

    private function whatsappRemoto(): void
    {
        $id = config('dentissa.whatsapp.phone_number_id');
        $token = config('dentissa.whatsapp.access_token');
        if (blank($id) || blank($token)) {
            return;
        }

        try {
            $respuesta = Http::withToken($token)->timeout(15)
                ->get('https://graph.facebook.com/'.config('dentissa.whatsapp.api_version').'/'.rawurlencode($id), [
                    'fields' => 'display_phone_number,verified_name,status,quality_rating',
                ]);
        } catch (Throwable) {
            $this->error('WhatsApp (Meta)', 'No hubo conexión con Meta.');

            return;
        }

        if ($respuesta->failed()) {
            // El mensaje de Meta no incluye el token.
            $this->error('WhatsApp (Meta)', 'Meta rechazó el token o el número (código '.$respuesta->json('error.code').'): '.$respuesta->json('error.message'));

            return;
        }

        $this->ok('WhatsApp (Meta)', 'El token funciona. Número '.$respuesta->json('display_phone_number').' ('.$respuesta->json('verified_name').'), estado '
            .($respuesta->json('status') ?? 'sin dato').', calidad '.($respuesta->json('quality_rating') ?? 'sin dato').'.');

        $bot = substr(preg_replace('/\D/', '', (string) $respuesta->json('display_phone_number')), -10);
        $consultorio = substr(preg_replace('/\D/', '', (string) config('dentissa.contacto.telefono')), -10);
        if ($bot !== '' && $bot === $consultorio) {
            $this->aviso('Contacto', 'El teléfono del consultorio (DENTISSA_TELEFONO) es el mismo número del bot: los pacientes escribirían a un número que nadie lee.');
        }
    }

    private function googleRemoto(): void
    {
        try {
            $token = app(GoogleAccessToken::class)->obtener();
            $info = Http::timeout(15)->get('https://oauth2.googleapis.com/tokeninfo', ['access_token' => $token]);
        } catch (Throwable $e) {
            $this->error('Google', 'Google no entregó un token: '.$e->getMessage());

            return;
        }

        $permisos = explode(' ', (string) $info->json('scope'));
        in_array('https://www.googleapis.com/auth/gmail.send', $permisos, true)
            ? $this->ok('Google', 'Permiso para enviar correo (gmail.send).')
            : $this->error('Google', 'La cuenta de Google no tiene permiso para enviar correo.');

        if (! in_array('https://www.googleapis.com/auth/drive.file', $permisos, true)) {
            $this->error('Respaldos', 'Falta el permiso de Drive: vuelve a autorizar con scripts/google-oauth.ps1 y actualiza GMAIL_OAUTH_REFRESH_TOKEN.');

            return;
        }

        try {
            $ultima = $this->destino->listar()[0] ?? null;
        } catch (Throwable $e) {
            $this->error('Respaldos', 'No se pudo leer la carpeta de respaldos en Drive: '.$e->getMessage());

            return;
        }

        if (! $ultima) {
            $this->aviso('Respaldos', 'Aún no hay respaldos en Drive: ejecuta php artisan dentissa:respaldar.');
        } elseif ($ultima['creado']->lt(now()->subHours(36))) {
            $this->error('Respaldos', 'El último respaldo es del '.$ultima['creado']->format('Y-m-d H:i').': el respaldo nocturno no está corriendo.');
        } else {
            $this->ok('Respaldos', 'Último respaldo en Drive: '.$ultima['creado']->format('Y-m-d H:i').'.');
        }
    }

    private function ok(string $area, string $detalle): void
    {
        $this->resultados[] = ['area' => $area, 'estado' => self::OK, 'detalle' => $detalle];
    }

    private function aviso(string $area, string $detalle): void
    {
        $this->resultados[] = ['area' => $area, 'estado' => self::AVISO, 'detalle' => $detalle];
    }

    private function error(string $area, string $detalle): void
    {
        $this->resultados[] = ['area' => $area, 'estado' => self::ERROR, 'detalle' => $detalle];
    }
}
