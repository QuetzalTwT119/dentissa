<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use App\Models\Caso;
use App\Models\Cita;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        foreach ([
            Cita::class, Paciente::class, HistorialClinico::class, HorarioAtencion::class,
            Servicio::class, Tratamiento::class, User::class, Oferta::class, Testimonio::class,
            Imagen::class, Caso::class, PreguntaFrecuente::class, MensajeContacto::class,
        ] as $modelo) {
            $modelo::observe(AuditoriaObserver::class);
        }

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
