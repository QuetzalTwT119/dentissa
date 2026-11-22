<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use App\Models\Caso;
use App\Models\Cita;
use App\Models\DatosConsultorio;
use App\Models\HistorialClinico;
use App\Models\HorarioAtencion;
use App\Models\Imagen;
use App\Models\MensajeContacto;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Testimonio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Observers\AuditoriaObserver;
use App\Services\Respaldo\DestinoRespaldo;
use App\Services\Respaldo\GoogleDriveDestino;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Destino de los respaldos cifrados. Las pruebas lo cambian por uno en memoria.
        $this->app->bind(DestinoRespaldo::class, GoogleDriveDestino::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('gmail-api', fn () => new GmailApiTransport);

        // Contraseñas del personal: mínimo 10 caracteres con mayúsculas,
        // minúsculas y números. Aplica al crearlas o cambiarlas; las
        // existentes siguen sirviendo hasta el siguiente cambio.
        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers());

        foreach ([
            Cita::class, Paciente::class, HistorialClinico::class, HorarioAtencion::class,
            Servicio::class, Tratamiento::class, User::class, Oferta::class, Testimonio::class,
            Imagen::class, Caso::class, PreguntaFrecuente::class, MensajeContacto::class, DatosConsultorio::class,
        ] as $modelo) {
            $modelo::observe(AuditoriaObserver::class);
        }

        // Teléfono, WhatsApp, correo y dirección que la doctora cambia en el panel
        // (22/11/2026) se ponen encima de los de .env.
        DatosConsultorio::aplicarAConfiguracion();

        // Búsqueda de texto de las listas (22/11/2026): "%" y "_" se buscan tal
        // cual. Con LIKE normal eran comodines y buscar "%" devolvía todo.
        // El escape "!" funciona igual en MySQL y en SQLite (pruebas).
        QueryBuilder::macro('whereContiene', function (string $columna, string $texto, string $boolean = 'and') {
            /** @var QueryBuilder $this */
            $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto).'%';

            return $this->whereRaw($this->getGrammar()->wrap($columna)." like ? escape '!'", [$patron], $boolean);
        });
        QueryBuilder::macro('orWhereContiene', function (string $columna, string $texto) {
            /** @var QueryBuilder $this */
            return $this->whereContiene($columna, $texto, 'or');
        });

        $this->configurarLimiteAgendaPublica();
    }

    /**
     * El formulario público no pide cuenta, así que se limita por IP para
     * evitar citas falsas en masa sin estorbar a un paciente real.
     */
    private function configurarLimiteAgendaPublica(): void
    {
        RateLimiter::for('agendar-cita', function (Request $request) {
            $avisar = fn (Request $request) => redirect()
                ->route('citas.publica.create')
                ->withInput($request->except('_token'))
                ->withErrors(['envios' => 'Recibimos varios intentos seguidos. Espera un minuto e inténtalo de nuevo.']);

            return [
                Limit::perMinute(5)->by('minuto|'.$request->ip())->response($avisar),
                Limit::perHour(20)->by('hora|'.$request->ip())->response($avisar),
            ];
        });
    }
}
