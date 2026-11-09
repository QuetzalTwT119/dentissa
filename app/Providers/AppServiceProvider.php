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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
