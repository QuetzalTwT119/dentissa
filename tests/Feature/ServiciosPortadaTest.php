<?php

namespace Tests\Feature;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Carrusel de especialidades de la portada ("Fila"): sale de la base de
 * datos, distingue lo que se agenda en línea de lo que se agenda por
 * teléfono y lleva a la agenda con la especialidad ya elegida.
 */
class ServiciosPortadaTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(string $nombre, int $orden, array $tratamientos = [], array $extra = []): Servicio
    {
        $servicio = Servicio::create($extra + ['nombre' => $nombre, 'orden' => $orden, 'activo' => true]);

        foreach ($tratamientos as $i => [$tratamiento, $activo]) {
            $servicio->tratamientos()->attach(
                Tratamiento::create(['nombre' => $tratamiento, 'duracion_estimada' => 30, 'activo' => $activo])->id,
                ['orden' => $i + 1],
            );
        }

        return $servicio;
    }

    public function test_muestra_las_especialidades_activas_en_un_carrusel_con_su_forma_de_agendar(): void
    {
        $general = $this->servicio('Odontología General', 1, [['Valoración', true], ['Limpieza Dental', true]]);
        $ortodoncia = $this->servicio('Ortodoncia', 2);
        $periodoncia = $this->servicio('Periodoncia', 3, [['Raspado', false]]); // solo un tratamiento inactivo
        $this->servicio('Oculta', 4, [], ['activo' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('x-data="carruselServicios"', false)
            ->assertSeeInOrder(['Odontología General', 'Ortodoncia', 'Periodoncia'])
            ->assertDontSee('Oculta')
            // En línea: lleva a la agenda con la especialidad elegida.
            ->assertSee(route('citas.publica.create', ['servicio' => $general->id]), false)
            ->assertSee('Ver 2 tratamientos y agendar')
            // Sin tratamientos activos: por teléfono.
            ->assertSee('Agenda por teléfono')
            ->assertDontSee(route('citas.publica.create', ['servicio' => $ortodoncia->id]), false)
            ->assertDontSee(route('citas.publica.create', ['servicio' => $periodoncia->id]), false)
            ->assertSee(config('dentissa.contacto.telefono_enlace'), false)
            // El catálogo de ejemplo solo aparece cuando no hay especialidades.
            ->assertDontSee('Más solicitado');
    }

    public function test_usa_la_foto_y_la_descripcion_de_la_clinica_o_las_aprobadas(): void
    {
        $this->servicio('Ortodoncia', 1);
        $this->servicio('Endodoncia', 2, [], ['imagen' => 'servicios/conductos.jpg', 'descripcion' => 'Texto propio de la clínica.']);

        $this->get('/')
            ->assertOk()
            ->assertSee(asset('images/dentissa/ortodoncia.jpg'), false)
            ->assertSee('Alineación dental con brackets estéticos y alineadores invisibles de precisión.')
            ->assertSee('/storage/servicios/conductos.jpg', false)
            ->assertSee('Texto propio de la clínica.')
            ->assertDontSee('Terapia de conductos radiculares');
    }

    public function test_la_agenda_abre_con_la_especialidad_elegida_solo_si_se_puede_agendar(): void
    {
        $general = $this->servicio('Odontología General', 1, [['Valoración', true]]);
        $ortodoncia = $this->servicio('Ortodoncia', 2);

        $this->get(route('citas.publica.create', ['servicio' => $general->id]))
            ->assertOk()
            ->assertSee('idServicio: '.$general->id.',', false);

        foreach ([$ortodoncia->id, 9999, 'abc'] as $valor) {
            $this->get(route('citas.publica.create', ['servicio' => $valor]))
                ->assertOk()
                ->assertSee("idServicio: '',", false);
        }
    }
}
