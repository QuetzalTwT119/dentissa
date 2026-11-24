<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\NotificacionInternaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * QA de notificaciones (25/11/2026). Cada prueba cubre un error encontrado al
 * revisar la campana y la página de notificaciones.
 */
class NotificacionesQaTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NotificacionSistemaCreada::class]);
        Mail::fake();
        Carbon::setTestNow('2030-01-09 10:00:00');

        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Error 1: en MySQL un texto de más de 255 se rechazaba y el aviso se perdía. */
    public function test_un_aviso_largo_se_recorta_y_no_se_pierde(): void
    {
        $error = str_repeat('Google Drive respondió con un error largo. ', 10);

        app(NotificacionInternaService::class)->alertaSistema('respaldo_fallido', str_repeat('Título larguísimo ', 12), "El respaldo no se completó: {$error}");

        $aviso = NotificacionSistema::where('user_id', $this->doctora->id)->sole();
        $this->assertLessThanOrEqual(NotificacionSistema::LARGO_MENSAJE, mb_strlen($aviso->mensaje));
        $this->assertLessThanOrEqual(NotificacionSistema::LARGO_TITULO, mb_strlen($aviso->titulo));
        $this->assertStringStartsWith('El respaldo no se completó', $aviso->mensaje);
        $this->assertStringEndsWith('…', $aviso->mensaje);
    }

    public function test_un_aviso_de_cita_con_una_oferta_de_titulo_largo_tambien_cabe(): void
    {
        $cita = $this->cita();
        $oferta = \App\Models\Oferta::create([
            'titulo' => str_repeat('Promoción de temporada con descuento ', 4),
            'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15, 'activa' => true,
            'fecha_inicio' => '2030-01-01', 'fecha_fin' => '2030-02-01',
        ]);
        $cita->forceFill(['id_oferta' => $oferta->id])->save();

        app(NotificacionInternaService::class)->cambioCita($cita->fresh(), 'agendada');

        $aviso = NotificacionSistema::where('user_id', $this->doctora->id)->sole();
        $this->assertLessThanOrEqual(NotificacionSistema::LARGO_MENSAJE, mb_strlen($aviso->mensaje));
    }

    /** Error 2: "Marcar todas como leídas" dejaba el número de la campana. */
    public function test_marcar_todas_como_leidas_tambien_apaga_el_numero_de_la_campana(): void
    {
        $this->aviso('confirmada', ['vista_en' => null]);
        $this->aviso('testimonio_nuevo', ['vista_en' => null]);

        $this->actingAs($this->doctora)->patch(route('notificaciones.leidas'))->assertRedirect();

        $this->assertSame(0, $this->doctora->notificacionesSistema()->whereNull('leida_en')->count());
        $this->assertSame(0, $this->doctora->notificacionesSistema()->whereNull('vista_en')->count());

        $this->aviso('confirmada', ['vista_en' => null]);
        $this->actingAs($this->doctora)->patchJson(route('notificaciones.leidas'))
            ->assertOk()
            ->assertJson(['no_leidas' => 0, 'no_vistas' => 0]);
    }

    /** Error 3: la página decía "Ver cita" en todos los avisos, aunque no tuvieran cita. */
    public function test_cada_aviso_lleva_a_su_pantalla_y_no_todos_dicen_ver_cita(): void
    {
        $this->aviso('confirmada', ['id_cita' => $this->cita('2030-01-15 11:00:00')->id]);
        $this->aviso('testimonio_nuevo');
        $this->aviso('festivos_con_citas');
        $this->aviso('respaldo_fallido');

        $html = $this->actingAs($this->doctora)->get(route('notificaciones.index'))->assertOk()->getContent();
        // Solo la lista (<main>): la campana del encabezado tiene sus propios botones.
        $lista = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));

        $this->assertStringNotContainsString('Ver cita', $lista);
        $this->assertStringContainsString('Ver en la agenda', $lista);
        $this->assertStringContainsString('href="/citas?fecha=2030-01-15"', $lista);
        $this->assertStringContainsString('Revisar testimonios', $lista);
        $this->assertStringContainsString('href="/admin/testimonios"', $lista);
        $this->assertStringContainsString('Ver bloqueos', $lista);
        $this->assertStringContainsString('href="/admin/configuracion#bloqueos"', $lista);
        // El respaldo no tiene otra pantalla: en la lista no se pone un enlace a sí misma.
        $this->assertStringNotContainsString('Ver detalle', $lista);
    }

    /** Error 4: un testimonio por aprobar y un festivo con citas piden acción, pero salían como "Sistema". */
    public function test_testimonio_y_festivo_con_citas_van_en_por_atender_con_su_etiqueta(): void
    {
        $testimonio = $this->aviso('testimonio_nuevo');
        $festivo = $this->aviso('festivos_con_citas');

        $this->assertSame('Testimonio por aprobar', $testimonio->etiquetaEvento());
        $this->assertSame('Festivo con citas', $festivo->etiquetaEvento());
        $this->assertSame(2, $this->doctora->notificacionesSistema()->porAtender()->count());

        $this->actingAs($this->doctora)->get(route('citas.index'))->assertOk()
            ->assertSee('Revisar testimonios')
            ->assertSee('Ver bloqueos')
            ->assertSee('Ya lo atendí');
    }

    /** Error 5: el aviso en vivo salía antes de guardar la cita; si no se guardaba, quedaba un aviso fantasma. */
    public function test_el_aviso_en_vivo_sale_solo_si_la_cita_se_guarda(): void
    {
        $cita = $this->cita();

        try {
            DB::transaction(function () use ($cita) {
                app(NotificacionInternaService::class)->cambioCita($cita, 'agendada');

                throw new RuntimeException('La cita no se pudo guardar');
            });
        } catch (RuntimeException) {
        }

        Event::assertNotDispatched(NotificacionSistemaCreada::class);
        $this->assertSame(0, NotificacionSistema::count());

        DB::transaction(fn () => app(NotificacionInternaService::class)->cambioCita($cita, 'agendada'));

        Event::assertDispatchedTimes(NotificacionSistemaCreada::class, 2); // doctora y doctor
    }

    private function aviso(string $evento, array $extra = []): NotificacionSistema
    {
        return NotificacionSistema::create($extra + [
            'user_id' => $this->doctora->id,
            'evento' => $evento,
            'titulo' => 'Aviso '.$evento,
            'mensaje' => 'Detalle del aviso.',
            'url' => route('notificaciones.index', [], false),
            'vista_en' => now(),
        ]);
    }

    private function cita(string $fechaHora = '2030-01-10 09:00:00'): Cita
    {
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222', 'email' => fake()->unique()->safeEmail()]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => Servicio::firstOrCreate(['nombre' => 'Odontología General'], ['orden' => 1, 'activo' => true])->id,
            'id_tratamiento' => Tratamiento::firstOrCreate(['nombre' => 'Limpieza Dental'], ['duracion_estimada' => 60, 'activo' => true])->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 60,
            'estado' => EstadoCita::Agendada,
        ]);
    }
}
