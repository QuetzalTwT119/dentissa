<?php

use App\Enums\TipoImagen;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CasoController;
use App\Http\Controllers\Admin\GaleriaController as AdminGaleriaController;
use App\Http\Controllers\Admin\HistorialClinicoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\MensajeContactoController as AdminMensajeContactoController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\DatosConsultorioController;
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
use App\Http\Controllers\CitaController;
use App\Http\Controllers\CitaPublicaController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\DosPasosController;
use App\Http\Controllers\GaleriaController;
use App\Http\Controllers\HistorialImagenController;
use App\Http\Controllers\MensajeContactoController;
use App\Http\Controllers\TestimonioPublicoController;
use App\Http\Controllers\NotificacionSistemaController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RespuestaRecordatorioController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Models\Caso;
use App\Models\HorarioAtencion;
use App\Models\Imagen;
use App\Models\Oferta;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Testimonio;
use App\Services\RespuestaRecordatorioService;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

Route::post('/contacto', [MensajeContactoController::class, 'store'])->name('contacto.store');
// Testimonio del paciente: entra pendiente; máximo 5 envíos por hora por visitante.
Route::post('/testimonios', [TestimonioPublicoController::class, 'store'])->middleware('throttle:5,60')->name('testimonios.publico.store');

Route::get('/version', function () {
    $railwayCommit = env('RAILWAY_GIT_COMMIT_SHA');

    if ($railwayCommit) {
        return response()->json([
            'commit' => $railwayCommit,
            'source' => 'railway',
            'working_tree_clean' => null,
        ])->header('Cache-Control', 'no-store');
    }

    if (! is_dir(base_path('.git'))) {
        return response()->json([
            'commit' => 'unknown',
            'source' => 'unavailable',
            'working_tree_clean' => null,
        ])->header('Cache-Control', 'no-store');
    }

    $environment = ['GIT_OPTIONAL_LOCKS' => '0'];
    // The container runs as www-data while the mounted checkout belongs to the
    // host user. Mark this exact repository safe per command instead of changing
    // Git's global configuration inside the container.
    $git = ['git', '-c', 'safe.directory='.base_path()];
    $commitProcess = new Process([...$git, 'rev-parse', 'HEAD'], base_path(), $environment);
    // The local runtime image intentionally omits developer-only files that
    // .dockerignore excludes (such as .github/ and .env.example/).
    $statusProcess = new Process([
        ...$git,
        'status',
        '--porcelain',
        '--untracked-files=normal',
        '--',
        '.',
        ':!.env.example',
        ':!.github',
    ], base_path(), $environment);
    $commitProcess->run();
    $statusProcess->run();

    $commit = $commitProcess->isSuccessful() ? trim($commitProcess->getOutput()) : 'unknown';
    $clean = $statusProcess->isSuccessful() ? trim($statusProcess->getOutput()) === '' : null;

    return response()->json([
        'commit' => $commit,
        'source' => 'local',
        'working_tree_clean' => $clean,
    ])->header('Cache-Control', 'no-store');
})->name('version');

Route::view('/aviso-de-privacidad', 'privacidad.aviso')->name('privacidad.aviso');
Route::get('/agendar-cita', [CitaPublicaController::class, 'create'])->name('citas.publica.create');
Route::post('/agendar-cita', [CitaPublicaController::class, 'store'])
    ->middleware('throttle:agendar-cita')
    ->name('citas.publica.store');
// Respuesta del paciente al recordatorio de su cita. Sin cuenta: lo que
// autoriza es la firma del enlace que le llegó por correo.
Route::middleware(['signed', 'throttle:20,1'])
    ->whereIn('accion', RespuestaRecordatorioService::ACCIONES)
    ->group(function () {
        Route::get('/cita/{cita}/recordatorio/{accion}', [RespuestaRecordatorioController::class, 'show'])
            ->name('citas.recordatorio.show');
        Route::post('/cita/{cita}/recordatorio/{accion}', [RespuestaRecordatorioController::class, 'responder'])
            ->name('citas.recordatorio.responder');
    });
// Webhook de Meta para los botones del recordatorio por WhatsApp. Sin CSRF
// (bootstrap/app.php): lo protege la firma HMAC de la app de Meta.
Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verificar'])->name('webhooks.whatsapp.verificar');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'recibir'])
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp.recibir');
Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index');
// Calcula un mes entero (un cálculo por día): límite propio para que no se use
// como forma barata de cargar el servidor.
Route::get('/disponibilidad/mes', [DisponibilidadController::class, 'mes'])
    ->middleware('throttle:60,1')
    ->name('disponibilidad.mes');
Route::post('/asistente-paciente/responder', [AsistentePacienteController::class, 'responder'])
    ->middleware('throttle:30,1')
    ->name('asistente-paciente.responder');

Route::get('/', function () {
    return view('welcome', [
        'canLogin' => Route::has('login'),
        'estadoConsultorio' => HorarioAtencion::estadoActual(),
        // El pie muestra el horario configurado en Panel › Horarios (antes decía "por confirmar").
        'horarioSemanal' => HorarioAtencion::lineasSemanales(),
        // Con sus tratamientos activos: la portada dice cuáles se agendan en línea y cuáles por teléfono.
        'servicios' => Servicio::where('activo', true)
            ->with(['tratamientos' => fn ($q) => $q->where('activo', true)->orderBy('servicios_tratamientos.orden')])
            ->orderBy('orden')
            ->orderBy('id')
            ->get(),
        // Publicadas, con tratamiento agendable y con lugar (las agotadas ya no se anuncian).
        'ofertas' => Oferta::paraPortada(),
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
    Route::patch('/notificaciones/{notificacion}/atendida', [NotificacionSistemaController::class, 'marcarAtendida'])
        ->middleware('role:admin,asistente')->name('notificaciones.atendida');
    Route::patch('/notificaciones/{notificacion}/pendiente', [NotificacionSistemaController::class, 'marcarPendiente'])
        ->middleware('role:admin,asistente')->name('notificaciones.pendiente');
    Route::patch('/notificaciones/leidas', [NotificacionSistemaController::class, 'marcarTodasLeidas'])
        ->middleware('role:admin,asistente')->name('notificaciones.leidas');
    Route::patch('/notificaciones/vistas', [NotificacionSistemaController::class, 'marcarTodasVistas'])
        ->middleware('role:admin,asistente')->name('notificaciones.vistas');
    Route::get('/asistente-agenda', [AsistenteAgendaController::class, 'create'])->name('asistente-agenda.create');
    Route::post('/asistente-agenda/proponer', [AsistenteAgendaController::class, 'proponer'])->name('asistente-agenda.proponer');

    Route::get('/profile/dos-pasos', [DosPasosController::class, 'show'])->name('dos-pasos.show');
    Route::post('/profile/dos-pasos', [DosPasosController::class, 'confirmar'])->middleware('throttle:10,1')->name('dos-pasos.confirmar');
    Route::post('/profile/dos-pasos/codigos', [DosPasosController::class, 'regenerar'])->name('dos-pasos.regenerar');
    Route::delete('/profile/dos-pasos', [DosPasosController::class, 'desactivar'])->name('dos-pasos.desactivar');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo.show');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

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
    // 22/11/2026: "Auditoría" se llama "Control de actividades"; la dirección de antes redirige.
    Route::get('control-de-actividades', [AuditoriaController::class, 'index'])->name('auditoria.index');
    // Conserva los filtros (?accion=…) de un enlace guardado.
    Route::get('auditoria', fn (\Illuminate\Http\Request $request) => redirect()->route('admin.auditoria.index', $request->query(), 301));
    Route::patch('servicios/{servicio}/tratamientos', [ServicioController::class, 'syncTratamientos'])->name('servicios.tratamientos.sync');
    Route::patch('servicios/{servicio}/mover/{direccion}', [ServicioController::class, 'mover'])
        ->whereIn('direccion', ['arriba', 'abajo'])->name('servicios.mover');
    Route::resource('servicios', ServicioController::class)->except('show');
    Route::patch('tratamientos/{tratamiento}/estado', [TratamientoController::class, 'alternarEstado'])->name('tratamientos.estado');
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
    Route::patch('testimonios/{testimonio}/aprobar', [AdminTestimonioController::class, 'aprobar'])->name('testimonios.aprobar');
    Route::patch('testimonios/{testimonio}/rechazar', [AdminTestimonioController::class, 'rechazar'])->name('testimonios.rechazar');
    Route::delete('testimonios/{testimonio}', [AdminTestimonioController::class, 'destroy'])->name('testimonios.destroy');

    Route::resource('ofertas', AdminOfertaController::class)->except('show');
    Route::patch('ofertas/{oferta}/publicar', [AdminOfertaController::class, 'publicar'])->name('ofertas.publicar');
    Route::patch('ofertas/{oferta}/ocultar', [AdminOfertaController::class, 'ocultar'])->name('ofertas.ocultar');

    Route::get('galeria', [AdminGaleriaController::class, 'index'])->name('galeria.index');
    Route::get('galeria/crear', [AdminGaleriaController::class, 'create'])->name('galeria.create');
    Route::post('galeria', [AdminGaleriaController::class, 'store'])->name('galeria.store');
    Route::get('galeria/{imagen}/editar', [AdminGaleriaController::class, 'edit'])->name('galeria.edit');
    Route::put('galeria/{imagen}', [AdminGaleriaController::class, 'update'])->name('galeria.update');
    Route::delete('galeria/{imagen}', [AdminGaleriaController::class, 'destroy'])->name('galeria.destroy');

    // Configuración: una sola página (22/11/2026). Las direcciones de antes llevan a su bloque.
    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
    Route::redirect('horarios', '/admin/configuracion#horarios')->name('horarios.edit');
    Route::put('horarios', [HorarioController::class, 'update'])->name('horarios.update');
    Route::redirect('consultorio', '/admin/configuracion#consultorio')->name('consultorio.edit');
    Route::put('consultorio', [DatosConsultorioController::class, 'update'])->name('consultorio.update');

    Route::resource('casos', CasoController::class)->except('show');
    Route::post('casos/{caso}/imagenes', [CasoController::class, 'storeImagen'])->name('casos.imagenes.store');
    Route::delete('casos/{caso}/imagenes/{imagen}', [CasoController::class, 'destroyImagen'])->name('casos.imagenes.destroy');

    Route::resource('preguntas-frecuentes', PreguntaFrecuenteController::class)->except('show');
});

require __DIR__.'/auth.php';

/*
 * SOLO DESARROLLO: revisión de casos extremos de la interfaz (Fase 4 del pulido).
 * Dibuja las pantallas con datos "peor caso" en memoria; no existe fuera de local.
 */
if (app()->environment('local')) {
    Route::get('/dev/casos-extremos/{pantalla}', \App\Http\Controllers\Dev\CasosExtremosController::class)
        ->name('dev.casos-extremos');
}
