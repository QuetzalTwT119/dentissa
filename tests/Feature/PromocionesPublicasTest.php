<?php

namespace Tests\Feature;

use App\Models\Oferta;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Página pública /promociones (25/11/2026).
 *
 * Criterios de aceptación:
 * - Se abre sin iniciar sesión y muestra TODAS las vigentes, incluso las que no
 *   caben en el carrusel de la portada (Oferta::MAXIMO_PORTADA).
 * - No muestra las que están en revisión, vencidas u ocultas (las agotadas las
 *   quita Oferta::vigentesConLugar, igual que en la portada).
 * - El filtro por especialidad deja solo las de esa especialidad; las
 *   especialidades sin promociones no aparecen como filtro.
 * - Cada tarjeta lleva «Agendar» con la oferta aplicada.
 * - La portada enlaza a la página y dice cuántas hay cuando no caben todas.
 */
class PromocionesPublicasTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $general;

    private Servicio $estetica;

    private Tratamiento $limpieza;

    private Tratamiento $blanqueamiento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->general = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $this->estetica = Servicio::create(['nombre' => 'Odontología Estética', 'activo' => true]);
        Servicio::create(['nombre' => 'Ortodoncia', 'activo' => true]);
        $this->limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true, 'precio_referencia' => 150]);
        $this->blanqueamiento = Tratamiento::create(['nombre' => 'Blanqueamiento', 'duracion_estimada' => 60, 'activo' => true, 'precio_referencia' => 350]);
        $this->general->tratamientos()->attach($this->limpieza);
        $this->estetica->tratamientos()->attach($this->blanqueamiento);
    }

    public function test_muestra_todas_las_vigentes_aunque_no_quepan_en_la_portada(): void
    {
        $total = Oferta::MAXIMO_PORTADA + 2;
        foreach (range(1, $total) as $i) {
            $this->oferta(['titulo' => "Promo {$i}", 'fecha_fin' => now()->addDays($i)->toDateString()]);
        }

        $this->get(route('promociones.index'))
            ->assertOk()
            ->assertSee("{$total} promociones vigentes")
            ->assertSee("Promo {$total}");

        // La portada sigue con su tope y enlaza a la página con el total.
        $portada = $this->get('/')->assertOk();
        $this->assertSame(Oferta::MAXIMO_PORTADA, substr_count($portada->getContent(), 'data-oferta-publica'));
        $portada->assertSee(route('promociones.index'), false)
            ->assertSee("Ver todas las promociones ({$total})");
    }

    public function test_no_muestra_en_revision_vencidas_ni_ocultas(): void
    {
        $this->oferta(['titulo' => 'Vigente']);
        $this->oferta(['titulo' => 'En revisión', 'estado' => 'pendiente']);
        $this->oferta(['titulo' => 'Vencida', 'fecha_inicio' => now()->subMonth()->toDateString(), 'fecha_fin' => now()->subDay()->toDateString()]);
        $this->oferta(['titulo' => 'Oculta', 'estado' => 'inactiva']);

        $this->get(route('promociones.index'))
            ->assertOk()
            ->assertSee('1 promoción vigente')
            ->assertSee('Vigente')
            ->assertDontSee('En revisión')
            ->assertDontSee('Vencida')
            ->assertDontSee('Oculta');
    }

    public function test_filtra_por_especialidad_y_solo_ofrece_las_que_tienen_promociones(): void
    {
        $this->oferta(['titulo' => 'Limpieza de fin de año']);
        $this->oferta(['titulo' => 'Blanqueamiento navideño', 'id_tratamiento' => $this->blanqueamiento->id]);

        $this->get(route('promociones.index'))
            ->assertSee('Odontología General')
            ->assertSee('Odontología Estética')
            ->assertDontSee('Ortodoncia');

        $this->get(route('promociones.index', ['especialidad' => $this->estetica->id]))
            ->assertOk()
            ->assertSee('1 promoción vigente en Odontología Estética')
            ->assertSee('Blanqueamiento navideño')
            ->assertDontSee('Limpieza de fin de año');
    }

    public function test_agendar_lleva_la_oferta_aplicada(): void
    {
        $oferta = $this->oferta(['titulo' => 'Limpieza de fin de año']);

        $this->get(route('promociones.index'))
            ->assertSee(route('citas.publica.create', ['tratamiento' => $this->limpieza->id, 'oferta' => $oferta->id]));
    }

    public function test_sin_promociones_invita_a_agendar(): void
    {
        $this->get(route('promociones.index'))
            ->assertOk()
            ->assertSee('Por ahora no hay promociones vigentes.')
            ->assertSee(route('citas.publica.create'), false);
    }

    private function oferta(array $datos = []): Oferta
    {
        return Oferta::create($datos + [
            'titulo' => 'Oferta', 'descripcion' => 'Por tiempo limitado.',
            'id_tratamiento' => $this->limpieza->id, 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 20,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'cupo_total' => 10, 'estado' => 'activa',
        ]);
    }
}
