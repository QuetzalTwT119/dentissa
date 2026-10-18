<?php

use App\Enums\TipoImagen;
use App\Http\Controllers\Admin\AtencionOportunaController as AdminAtencionOportunaController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CasoController;
use App\Http\Controllers\Admin\GaleriaController as AdminGaleriaController;
use App\Http\Controllers\Admin\HistorialClinicoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\MensajeContactoController as AdminMensajeContactoController;
use App\Http\Controllers\Admin\OfertaController as AdminOfertaController;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\PreguntaFrecuenteController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\TestimonioController as AdminTestimonioController;
use App\Http\Controllers\Admin\TratamientoController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AsistenteAgendaController;
use App\Http\Controllers\AsistentePacienteController;
use App\Http\Controllers\AtencionOportunaController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\CitaPublicaController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\GaleriaController;
use App\Http\Controllers\HistorialImagenController;
use App\Http\Controllers\MensajeContactoController;
use App\Http\Controllers\NotificacionSistemaController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\ProfileController;
use App\Models\Caso;
use App\Models\Imagen;
use App\Models\Oferta;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Testimonio;
use Illuminate\Support\Facades\Route;

Route::post('/contacto', [MensajeContactoController::class, 'store'])->name('contacto.store');

Route::get('/agendar-cita', [CitaPublicaController::class, 'create'])->name('citas.publica.create');
Route::post('/agendar-cita', [CitaPublicaController::class, 'store'])->name('citas.publica.store');
Route::get('/atencion-urgente', [AtencionOportunaController::class, 'urgencia'])->name('atencion.urgencia');
Route::post('/atencion-urgente', [AtencionOportunaController::class, 'guardarUrgencia'])
    ->middleware('throttle:10,1')->name('atencion.urgencia.store');
Route::get('/lista-de-espera', [AtencionOportunaController::class, 'listaEspera'])->name('atencion.lista-espera');
Route::post('/lista-de-espera', [AtencionOportunaController::class, 'guardarListaEspera'])
    ->middleware('throttle:10,1')->name('atencion.lista-espera.store');
Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index');
Route::post('/asistente-paciente/responder', [AsistentePacienteController::class, 'responder'])
    ->middleware('throttle:30,1')
    ->name('asistente-paciente.responder');

Route::get('/', function () {
    $hoy = now()->toDateString();

    return view('welcome', [
        'canLogin' => Route::has('login'),
        'servicios' => Servicio::where('activo', true)->orderBy('orden')->get(),
        'ofertas' => Oferta::with('tratamiento:id,nombre')
            ->where('activa', true)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->orderBy('fecha_fin')
            ->take(12)
            ->get(),
        'testimonios' => Testimonio::with('paciente:id,nombre,apellido')
            ->where('estado', 'aprobado')
            ->orderByDesc('fecha_publicacion')
            ->take(6)
            ->get(),
        'galeria' => Imagen::where('tipo', TipoImagen::Galeria)
            ->where('activa', true)
            ->orderBy('orden')
            ->take(8)
            ->get(),
        'casos' => Caso::with('imagenes')
            ->where('activo', true)
            ->orderBy('orden')
            ->take(6)
            ->get(),
        'faqs' => PreguntaFrecuente::where('activa', true)->orderBy('orden')->orderBy('id')->get(),
        'contacto' => config('dentissa.contacto'),
    ]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'no-cache'])->name('dashboard');

Route::middleware(['auth', 'no-cache'])->group(function () {
    Route::get('/notificaciones', [NotificacionSistemaController::class, 'index'])
        ->middleware('role:admin,asistente')->name('notificaciones.index');
    Route::get('/notificaciones/actualizaciones', [NotificacionSistemaController::class, 'actualizaciones'])
        ->middleware('role:admin,asistente')->name('notificaciones.actualizaciones');
    Route::patch('/notificaciones/{notificacion}/leida', [NotificacionSistemaController::class, 'marcarLeida'])
        ->middleware('role:admin,asistente')->name('notificaciones.leida');
    Route::patch('/notificaciones/{notificacion}/pendiente', [NotificacionSistemaController::class, 'marcarPendiente'])
        ->middleware('role:admin,asistente')->name('notificaciones.pendiente');
    Route::patch('/notificaciones/leidas', [NotificacionSistemaController::class, 'marcarTodasLeidas'])
        ->middleware('role:admin,asistente')->name('notificaciones.leidas');

    Route::get('/asistente-agenda', [AsistenteAgendaController::class, 'create'])->name('asistente-agenda.create');
    Route::post('/asistente-agenda/proponer', [AsistenteAgendaController::class, 'proponer'])->name('asistente-agenda.proponer');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/crear', [CitaController::class, 'create'])->name('citas.create');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::get('/citas/{cita}/editar', [CitaController::class, 'edit'])->name('citas.edit');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
    Route::patch('/citas/{cita}/cancelar', [CitaController::class, 'cancel'])->name('citas.cancel');
    Route::patch('/citas/{cita}/confirmar', [CitaController::class, 'confirm'])->name('citas.confirm');
    Route::patch('/citas/{cita}/iniciar', [CitaController::class, 'start'])->name('citas.start');
    Route::patch('/citas/{cita}/no-asistio', [CitaController::class, 'noShow'])->name('citas.no-show');
    Route::patch('/citas/{cita}/completar', [CitaController::class, 'complete'])->name('citas.complete');

    Route::get('/historial/imagenes/{imagen}', [HistorialImagenController::class, 'show'])->name('historial.imagenes.show');

    Route::get('/ofertas', [OfertaController::class, 'index'])->name('ofertas.index');

    Route::get('/galeria', [GaleriaController::class, 'index'])->name('galeria.index');
});

Route::middleware(['auth', 'role:admin', 'no-cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('atencion-oportuna', [AdminAtencionOportunaController::class, 'index'])->name('atencion-oportuna.index');
    Route::patch('atencion-oportuna/urgencias/{urgencia}/contactar', [AdminAtencionOportunaController::class, 'contactarUrgencia'])->name('atencion-oportuna.urgencias.contactar');
    Route::patch('atencion-oportuna/espera/{espera}/contactar', [AdminAtencionOportunaController::class, 'contactarEspera'])->name('atencion-oportuna.espera.contactar');
    Route::patch('servicios/{servicio}/tratamientos', [ServicioController::class, 'syncTratamientos'])->name('servicios.tratamientos.sync');
    Route::resource('servicios', ServicioController::class)->except('show');
    Route::resource('tratamientos', TratamientoController::class)->except('show');

    Route::get('pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::get('pacientes/{paciente}/editar', [PacienteController::class, 'edit'])->name('pacientes.edit');
    Route::put('pacientes/{paciente}', [PacienteController::class, 'update'])->name('pacientes.update');

    Route::get('mensajes', [AdminMensajeContactoController::class, 'index'])->name('mensajes.index');
    Route::patch('mensajes/{mensaje}/leido', [AdminMensajeContactoController::class, 'toggleLeido'])->name('mensajes.toggleLeido');
    Route::delete('mensajes/{mensaje}', [AdminMensajeContactoController::class, 'destroy'])->name('mensajes.destroy');

    Route::get('pacientes/{paciente}/historial', [HistorialClinicoController::class, 'index'])->name('pacientes.historial.index');
    Route::get('pacientes/{paciente}/historial/crear', [HistorialClinicoController::class, 'create'])->name('pacientes.historial.create');
    Route::post('pacientes/{paciente}/historial', [HistorialClinicoController::class, 'store'])->name('pacientes.historial.store');
    Route::get('pacientes/{paciente}/historial/{historial}/editar', [HistorialClinicoController::class, 'edit'])->name('pacientes.historial.edit');
    Route::put('pacientes/{paciente}/historial/{historial}', [HistorialClinicoController::class, 'update'])->name('pacientes.historial.update');
    Route::delete('pacientes/{paciente}/historial/{historial}', [HistorialClinicoController::class, 'destroy'])->name('pacientes.historial.destroy');
    Route::post('pacientes/{paciente}/historial/{historial}/imagenes', [HistorialClinicoController::class, 'storeImagen'])->name('pacientes.historial.imagenes.store');
    Route::delete('pacientes/{paciente}/historial/{historial}/imagenes/{imagen}', [HistorialClinicoController::class, 'destroyImagen'])->name('pacientes.historial.imagenes.destroy');
    Route::get('pacientes/{paciente}/historial-pdf', [HistorialClinicoController::class, 'pdf'])->name('pacientes.historial.pdf');

    Route::patch('usuarios/{usuario}/activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.activo.toggle');
    // Sin destroy() a propósito: historial_clinico.id_doctor tiene cascadeOnDelete
    // hacia users, así que borrar un usuario borraría en cascada el historial
    // clínico que registró. "Desactivar" (toggleActivo) es la baja soportada.
    Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
    Route::get('reportes/excel', [ReporteController::class, 'excel'])->name('reportes.excel');

    Route::get('testimonios', [AdminTestimonioController::class, 'index'])->name('testimonios.index');
    Route::get('testimonios/crear', [AdminTestimonioController::class, 'create'])->name('testimonios.create');
    Route::post('testimonios', [AdminTestimonioController::class, 'store'])->name('testimonios.store');
    Route::get('testimonios/{testimonio}/editar', [AdminTestimonioController::class, 'edit'])->name('testimonios.edit');
    Route::put('testimonios/{testimonio}', [AdminTestimonioController::class, 'update'])->name('testimonios.update');
    Route::patch('testimonios/{testimonio}/aprobar', [AdminTestimonioController::class, 'aprobar'])->name('testimonios.aprobar');
    Route::patch('testimonios/{testimonio}/rechazar', [AdminTestimonioController::class, 'rechazar'])->name('testimonios.rechazar');
    Route::delete('testimonios/{testimonio}', [AdminTestimonioController::class, 'destroy'])->name('testimonios.destroy');

    Route::resource('ofertas', AdminOfertaController::class)->except('show');

    Route::get('galeria', [AdminGaleriaController::class, 'index'])->name('galeria.index');
    Route::get('galeria/crear', [AdminGaleriaController::class, 'create'])->name('galeria.create');
    Route::post('galeria', [AdminGaleriaController::class, 'store'])->name('galeria.store');
    Route::get('galeria/{imagen}/editar', [AdminGaleriaController::class, 'edit'])->name('galeria.edit');
    Route::put('galeria/{imagen}', [AdminGaleriaController::class, 'update'])->name('galeria.update');
    Route::delete('galeria/{imagen}', [AdminGaleriaController::class, 'destroy'])->name('galeria.destroy');

    Route::get('horarios', [HorarioController::class, 'edit'])->name('horarios.edit');
    Route::put('horarios', [HorarioController::class, 'update'])->name('horarios.update');

    Route::resource('casos', CasoController::class)->except('show');
    Route::post('casos/{caso}/imagenes', [CasoController::class, 'storeImagen'])->name('casos.imagenes.store');
    Route::delete('casos/{caso}/imagenes/{imagen}', [CasoController::class, 'destroyImagen'])->name('casos.imagenes.destroy');

    Route::resource('preguntas-frecuentes', PreguntaFrecuenteController::class)->except('show');
});

require __DIR__.'/auth.php';
