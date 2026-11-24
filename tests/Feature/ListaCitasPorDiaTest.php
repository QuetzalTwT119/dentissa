<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Lista de citas "Por pestañas": un día a la vez (hoy por defecto) con una
 * pestaña por día de la semana; la búsqueda y "Todas las fechas" recorren
 * todo el historial.
 */
class ListaCitasPorDiaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Miércoles 9 de enero de 2030, 10:00.
        Carbon::setTestNow(Carbon::parse('2030-01-09 10:00:00', 'America/Mexico_City'));
        $this->admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_por_defecto_muestra_solo_las_citas_de_hoy_con_la_pestana_de_hoy_activa(): void
    {
        $this->cita('Hoy Uno', '2030-01-09 12:00', EstadoCita::Agendada);
        $this->cita('Hoy Dos', '2030-01-09 09:00', EstadoCita::Confirmada);
        $this->cita('Manana Tres', '2030-01-10 09:00', EstadoCita::Agendada);

        $html = $this->actingAs($this->admin)->get(route('citas.index'))
            ->assertOk()
            ->assertSee('Hoy · miércoles 9 de enero')
            ->assertSeeInOrder(['Hoy Dos', 'Hoy Uno'])
            ->assertDontSee('Manana Tres')
            ->assertSee('data-pestanas-dias', false)
            ->getContent();

        // La pestaña activa es hoy y la de mañana avisa que hay 1 por confirmar.
        $this->assertMatchesRegularExpression('#aria-current="date"[^>]*>\s*<span[^>]*>\s*Hoy 9#', $html);
        $this->assertStringContainsString('fecha=2030-01-10', $html);
        $this->assertMatchesRegularExpression('#Jue 10\s*</span>\s*<span[^>]*>\s*1 cita\s*·\s*<span[^>]*>1 por confirmar#u', $html);
    }

    public function test_las_pestanas_cubren_la_semana_del_dia_elegido_y_permiten_cambiar_de_semana(): void
    {
        $this->cita('Viernes Siguiente', '2030-01-18 11:00', EstadoCita::Agendada);

        $this->actingAs($this->admin)->get(route('citas.index', ['fecha' => '2030-01-18']))
            ->assertOk()
            ->assertSee('Viernes 18 de enero')
            ->assertSee('Viernes Siguiente')
            ->assertSee('fecha=2030-01-14', false)   // lunes de esa semana
            ->assertDontSee('fecha=2030-01-20', false) // domingo cerrado y sin citas: sin pestaña
            ->assertSee('fecha=2030-01-11', false)   // semana anterior
            ->assertSee('fecha=2030-01-25', false)   // semana siguiente
            ->assertSee('Ir a hoy');
    }

    public function test_lo_ya_atendido_se_pliega_mientras_queden_citas_por_atender(): void
    {
        $this->cita('Ya Atendida', '2030-01-09 08:00', EstadoCita::Completada);
        $this->cita('Cancelada Hoy', '2030-01-09 08:30', EstadoCita::Cancelada);
        $this->cita('Por Atender', '2030-01-09 11:00', EstadoCita::Confirmada);

        $this->actingAs($this->admin)->get(route('citas.index'))
            ->assertOk()
            ->assertSee('data-citas-plegadas', false)
            ->assertSee('2 ya atendidas o canceladas')
            ->assertSee('1 completada, 1 cancelada')
            ->assertSee('Ya Atendida')   // sigue en la página, dentro del plegable
            ->assertSee('Por Atender');

        // Un día ya terminado no pliega nada: todo es historial.
        $this->actingAs($this->admin)->get(route('citas.index', ['fecha' => '2030-01-09', 'estado' => 'completada']))
            ->assertOk()
            ->assertDontSee('data-citas-plegadas', false)
            ->assertSee('Ya Atendida')
            ->assertDontSee('Por Atender');
    }

    public function test_buscar_encuentra_citas_de_cualquier_fecha(): void
    {
        $this->cita('Lucia Pasada', '2029-11-20 10:00', EstadoCita::Completada);
        $this->cita('Lucia Futura', '2030-02-15 10:00', EstadoCita::Agendada);
        $this->cita('Otra Persona', '2030-01-09 12:00', EstadoCita::Agendada);

        $this->actingAs($this->admin)->get(route('citas.index', ['buscar' => 'Lucia', 'fecha' => '2030-01-09']))
            ->assertOk()
            ->assertSee('Resultados en todas las fechas')
            ->assertSeeInOrder(['Lucia Futura', 'Lucia Pasada'])
            ->assertDontSee('Otra Persona')
            ->assertDontSee('data-pestanas-dias', false);
    }

    public function test_todas_las_fechas_lista_el_historial_paginado(): void
    {
        $this->cita('Antigua Visita', '2029-06-01 10:00', EstadoCita::Completada);
        $this->cita('Reciente Visita', '2030-01-09 12:00', EstadoCita::Agendada);

        $this->actingAs($this->admin)->get(route('citas.index', ['todas' => 1]))
            ->assertOk()
            ->assertSee('Todas las fechas')
            ->assertSeeInOrder(['Reciente', 'Antigua'])
            ->assertSee('Mostrando');
    }

    public function test_un_dia_cerrado_y_sin_citas_lo_dice(): void
    {
        // Domingo 13 de enero: horarios_atencion lo tiene cerrado por defecto.
        $this->actingAs($this->admin)->get(route('citas.index', ['fecha' => '2030-01-13']))
            ->assertOk()
            ->assertSee('El consultorio no abre este día.');
    }

    public function test_un_dia_con_solo_canceladas_lo_dice_en_la_pestana_y_el_titulo(): void
    {
        $this->cita('Ana Cancelada', '2030-01-09 09:00', EstadoCita::Cancelada);
        $this->cita('Beto Cancelado', '2030-01-09 11:00', EstadoCita::Cancelada);

        $html = $this->actingAs($this->admin)->get(route('citas.index'))->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression('#0 citas activas\s*·\s*2 canceladas#u', $html);
        $this->assertMatchesRegularExpression('#Hoy 9\s*</span>\s*<span[^>]*>\s*2 canceladas#u', $html);
    }

    public function test_tras_una_accion_regresa_al_mismo_dia_y_no_a_hoy(): void
    {
        $cita = $this->cita('Ana Lunes', '2030-01-07 09:00', EstadoCita::Confirmada);
        $agendaDelLunes = route('citas.index', ['fecha' => '2030-01-07']);

        $this->actingAs($this->admin)->from($agendaDelLunes)->patch(route('citas.start', $cita))
            ->assertRedirect($agendaDelLunes);

        // Si no venía de la agenda, va al día de la cita.
        $this->actingAs($this->admin)->from(route('dashboard'))->patch(route('citas.complete', $cita))
            ->assertRedirect($agendaDelLunes);
    }

    public function test_lista_y_semana_estan_en_el_encabezado(): void
    {
        foreach ([route('citas.index'), route('citas.index', ['vista' => 'semana'])] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertLessThan(strpos($html, 'panel-contenido'), strpos($html, 'data-vista-actual'), 'El selector va junto al título');
        }
    }

    private function cita(string $nombre, string $fechaHora, EstadoCita $estado): Cita
    {
        $servicio = Servicio::firstOrCreate(['nombre' => 'Odontología General'], ['activo' => true, 'orden' => 1]);
        $tratamiento = Tratamiento::firstOrCreate(
            ['nombre' => 'Limpieza Dental'],
            ['duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true],
        );
        [$nombrePila, $apellido] = explode(' ', $nombre, 2);
        $paciente = Paciente::create([
            'nombre' => $nombrePila,
            'apellido' => $apellido,
            'telefono' => '55'.random_int(10000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 30,
            'estado' => $estado,
        ]);
    }
}
