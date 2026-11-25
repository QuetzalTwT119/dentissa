<?php

namespace Tests\Feature;

use App\Models\HorarioAtencion;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Support\Dev\CasosExtremos;
use App\Support\Iniciales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regresión de la Fase 4 del pulido: las pantallas clave se dibujan sin
 * errores con datos "peor caso" (nombres y correos en el límite, campos
 * vacíos, listas vacías, de un elemento y de 1 284 registros).
 * Los datos viven en App\Support\Dev\CasosExtremos y no tocan la base.
 */
class CasosExtremosInterfazTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fuera de una petición web, Laravel no comparte $errors con las vistas.
        view()->share('errors', new ViewErrorBag);
    }

    public static function conjuntos(): array
    {
        return array_combine(CasosExtremos::CONJUNTOS, array_map(fn ($c) => [$c], CasosExtremos::CONJUNTOS));
    }

    #[DataProvider('conjuntos')]
    public function test_la_agenda_se_dibuja_con_cada_conjunto(string $conjunto): void
    {
        Auth::setUser(CasosExtremos::usuario($conjunto));

        $html = view('citas.index', ['citas' => CasosExtremos::citas($conjunto), 'filtros' => []])->render();

        $this->assertStringContainsString('Agenda', $html);
        if ($conjunto === 'peor') {
            $this->assertStringContainsString(e(CasosExtremos::APELLIDO_LARGO), $html);
            $this->assertStringContainsString(e(CasosExtremos::TRATAMIENTO_LARGO), $html);
        }
    }

    #[DataProvider('conjuntos')]
    public function test_el_directorio_de_pacientes_se_dibuja_con_cada_conjunto(string $conjunto): void
    {
        Auth::setUser(CasosExtremos::usuario($conjunto));

        $html = view('admin.pacientes.index', ['pacientes' => CasosExtremos::pacientes($conjunto), 'filtros' => []])->render();

        $this->assertStringContainsString('Pacientes', $html);
        if ($conjunto === 'peor') {
            $this->assertStringContainsString(CasosExtremos::CORREO_LARGO, $html);
        }
    }

    #[DataProvider('conjuntos')]
    public function test_agendar_cita_se_dibuja_con_cada_conjunto(string $conjunto): void
    {
        $html = view('citas-publicas.create', [
            'servicios' => CasosExtremos::servicios($conjunto),
            'horarioRegular' => HorarioAtencion::resumenSemanal(),
        ])->render();

        $this->assertStringContainsString('Tu cita, paso a paso', $html);
    }

    #[DataProvider('conjuntos')]
    public function test_el_panel_se_dibuja_con_un_nombre_de_usuario_largo(string $conjunto): void
    {
        Auth::setUser(CasosExtremos::usuario($conjunto));

        $html = view('dashboard')->render();

        $this->assertStringContainsString('Panel de administración', $html);
    }

    public function test_las_iniciales_respetan_acentos_y_omiten_emoji(): void
    {
        $this->assertSame('ÑÖ', Iniciales::de('Ñandú', 'Ötzi Ybáñez'));
        $this->assertSame('AP', Iniciales::de('😊 Ana', 'Pérez'));
        $this->assertSame('MW', Iniciales::de(CasosExtremos::NOMBRE_LARGO, CasosExtremos::APELLIDO_LARGO));
        $this->assertSame('J', Iniciales::de('Jo', null));
        $this->assertSame('ÁM', Iniciales::deTexto('Ángel Martínez'));
        $this->assertSame('?', Iniciales::de('😊', ''));
    }

    public function test_la_agenda_no_muestra_caracteres_rotos_en_los_nombres(): void
    {
        Auth::setUser(CasosExtremos::usuario('peor'));

        $html = view('citas.index', ['citas' => CasosExtremos::citas('peor'), 'filtros' => []])->render();

        $this->assertStringNotContainsString("\u{FFFD}", $html);
        // La lista ya no dibuja iniciales; el nombre completo debe llegar intacto.
        $this->assertStringContainsString('Ñandú Ötzi Ybáñez', $html);
    }

    public function test_los_contadores_usan_singular_y_separador_de_miles(): void
    {
        Auth::setUser(CasosExtremos::usuario('uno'));

        $uno = view('citas.index', ['citas' => CasosExtremos::citas('uno'), 'filtros' => []])->render();
        $this->assertMatchesRegularExpression('#>1</strong>\s*cita\s*<#', $uno);

        $muchos = view('citas.index', ['citas' => CasosExtremos::citas('muchos'), 'filtros' => []])->render();
        $this->assertStringContainsString('>1,284</strong> citas', $muchos);

        $pacientes = view('admin.pacientes.index', ['pacientes' => CasosExtremos::pacientes('peor'), 'filtros' => []])->render();
        $this->assertStringContainsString('1,284', $pacientes);
    }

    public function test_agendar_oculta_especialidades_sin_tratamientos_activos(): void
    {
        $conTratamiento = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $conTratamiento->tratamientos()->attach(Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 60, 'activo' => true]));
        Servicio::create(['nombre' => 'Especialidad vacía', 'orden' => 2, 'activo' => true]);

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Odontología General')
            ->assertDontSee('Especialidad vacía');
    }

    public function test_el_motivo_de_la_cita_publica_tiene_un_limite_de_1000_caracteres(): void
    {
        $this->post(route('citas.publica.store'), ['motivo' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('motivo');

        // El campo ya no se muestra (se platica en la consulta); el servidor mantiene el límite.
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $this->post(route('citas.publica.store'), ['telefono' => ''])
            ->assertSessionHasErrors(['telefono' => 'El teléfono es obligatorio.']);
    }

    public function test_los_titulos_del_panel_son_h1(): void
    {
        $html = $this->blade('<x-admin.header title="Pacientes" />');

        $html->assertSee('<h1', false)->assertDontSee('<h2', false);
    }
}
