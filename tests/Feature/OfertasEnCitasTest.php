<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Oferta;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 21/11/2026: las ofertas no tenían relación con la agenda. El paciente no las
 * veía al agendar, la cita no guardaba con qué oferta venía y el contador de
 * lugares nunca bajaba. Ahora la cita guarda la oferta (la doctora la aplica al
 * cobrar), los lugares se cuentan con citas reales y solo se ligan ofertas a
 * tratamientos que se agendan en línea.
 */
class OfertasEnCitasTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    private Carbon $fecha;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $this->servicio->tratamientos()->attach($this->tratamiento);

        $this->fecha = Carbon::tomorrow();
        while ($this->fecha->isSunday()) {
            $this->fecha->addDay();
        }
    }

    private function oferta(array $datos = []): Oferta
    {
        return Oferta::create($datos + [
            'titulo' => 'Limpieza de fin de año', 'descripcion' => 'Para pacientes nuevos y de siempre.',
            'id_tratamiento' => $this->tratamiento->id, 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 20,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'cupo_total' => 2, 'estado' => 'activa',
        ]);
    }

    private function agendar(string $hora = '09:00', ?Carbon $fecha = null, string $telefono = '5555555555')
    {
        return $this->post(route('citas.publica.store'), [
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha' => ($fecha ?? $this->fecha)->toDateString(),
            'hora' => $hora,
            'nombre' => 'Ana',
            'apellido' => 'Martínez',
            'telefono' => $telefono,
            'acepta_aviso' => '1',
        ]);
    }

    public function test_la_cita_guarda_la_oferta_y_ocupa_un_lugar(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        $oferta = $this->oferta();

        $this->agendar()->assertRedirect(route('citas.publica.create'))
            ->assertSessionHas('cita_resumen.oferta', 'Limpieza de fin de año (−20%)');

        $cita = Cita::firstOrFail();
        $this->assertSame($oferta->id, $cita->id_oferta);
        $this->assertSame(1, $oferta->fresh()->lugaresQuedan());
        $this->get('/')->assertSee('Queda 1 lugar');

        // El personal lo ve en la campana y en la agenda.
        $this->assertStringContainsString('Con oferta «Limpieza de fin de año» (−20%).', NotificacionSistema::where('user_id', $admin->id)->latest('id')->value('mensaje'));
        $this->actingAs($admin)->get(route('citas.index', ['fecha' => $this->fecha->toDateString()]))->assertOk()
            ->assertSee('data-cita-oferta', false)
            ->assertSee('Oferta −20% · Limpieza de fin de año');

        // Cancelar libera el lugar.
        $cita->update(['estado' => EstadoCita::Cancelada]);
        $this->assertSame(2, $oferta->fresh()->lugaresQuedan());
    }

    public function test_sin_lugar_o_fuera_de_vigencia_la_cita_no_lleva_oferta(): void
    {
        $oferta = $this->oferta(['cupo_total' => 1, 'fecha_fin' => $this->fecha->toDateString()]);

        $this->agendar('09:00')->assertSessionHasNoErrors();
        $this->assertSame($oferta->id, Cita::latest('id')->first()->id_oferta);

        // Agotada: la siguiente cita ya no la lleva y la portada deja de anunciarla.
        $this->agendar('10:00', telefono: '5555555556')->assertSessionHasNoErrors();
        $this->assertNull(Cita::latest('id')->first()->id_oferta);
        $this->get('/')->assertDontSee('data-oferta-publica', false);

        // Cita después de "Válida hasta": tampoco.
        $oferta->update(['cupo_total' => 0]);
        $despues = $this->fecha->copy()->addWeek();
        while ($despues->isSunday()) {
            $despues->addDay();
        }
        $this->agendar('11:00', $despues, '5555555557')->assertSessionHasNoErrors();
        $this->assertNull(Cita::latest('id')->first()->id_oferta);
    }

    public function test_la_agenda_en_linea_avisa_y_abre_con_el_tratamiento_de_la_oferta(): void
    {
        $this->oferta();

        $this->get('/')->assertSee(route('citas.publica.create', ['tratamiento' => $this->tratamiento->id]), false);

        $this->get(route('citas.publica.create', ['tratamiento' => $this->tratamiento->id]))->assertOk()
            ->assertSee('data-aviso-oferta', false)
            ->assertSee('data-etiqueta-oferta', false)
            ->assertSee('Limpieza de fin de año')
            ->assertSee('−20%')
            ->assertSee("idTratamiento: {$this->tratamiento->id}", false)
            ->assertSee("idServicio: {$this->servicio->id}", false);
    }

    public function test_solo_se_ligan_ofertas_a_tratamientos_que_se_agendan(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $inactivo = Tratamiento::create(['nombre' => 'Carillas', 'duracion_estimada' => 60, 'categoria' => 'Estética', 'activo' => false]);
        $this->servicio->tratamientos()->attach($inactivo);

        // El formulario no lo ofrece y el servidor no lo acepta.
        $this->actingAs($admin)->get(route('admin.ofertas.create'))->assertOk()
            ->assertSee('Limpieza Dental')->assertDontSee('Carillas');
        $this->actingAs($admin)->post(route('admin.ofertas.store'), [
            'titulo' => 'Carillas de temporada', 'descripcion' => 'Para pacientes nuevos y de siempre.',
            'id_tratamiento' => $inactivo->id, 'beneficio' => 'descuento', 'unidad' => 'porcentaje', 'cantidad' => 10,
            'fecha_fin' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('id_tratamiento');

        // Si su tratamiento se desactiva después: sale de la portada, no se publica y la lista lo avisa.
        $oferta = $this->oferta();
        $this->get('/')->assertSee('data-oferta-publica', false);
        $this->tratamiento->update(['activo' => false]);
        $this->get('/')->assertDontSee('data-oferta-publica', false);

        $oferta->update(['estado' => 'inactiva']);
        $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $oferta))->assertSessionHas('error');
        $this->assertSame('inactiva', $oferta->fresh()->estado->value);
        $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertSee('data-tratamiento-inactivo', false)->assertSee('Tratamiento inactivo: ver explicación')->assertSee('Esta oferta no se muestra en la portada ni se puede publicar.');

        // Al editarla sigue mostrando su tratamiento, marcado como inactivo.
        $this->actingAs($admin)->get(route('admin.ofertas.edit', $oferta))->assertSee('Limpieza Dental (inactivo)');
    }

    public function test_el_descuento_nuevo_solo_es_en_porcentaje(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $base = ['titulo' => 'Prueba de oferta', 'descripcion' => 'Válida para pacientes nuevos y de siempre.', 'beneficio' => 'descuento', 'cantidad' => 300, 'fecha_fin' => now()->addWeek()->toDateString()];

        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['unidad' => 'monto'])->assertSessionHasErrors('unidad');

        $html = $this->actingAs($admin)->get(route('admin.ofertas.create'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-unidad-descuento', $html);
        $this->assertSame(3, substr_count($html, 'class="opcion'));
        // 22/11/2026: el beneficio es obligatorio también en el navegador (antes no avisaba)
        // y el tratamiento está a la vista, fuera de "Más detalles".
        $this->assertSame(3, preg_match_all('/name="beneficio" value="[a-z]+" class="sr-only"\s+(?:checked\s+)?required/', $html));
        $detalles = substr($html, strpos($html, 'data-mas-detalles'));
        $this->assertStringContainsString('id="id_tratamiento"', $html);
        $this->assertStringNotContainsString('id="id_tratamiento"', $detalles);
        $this->assertStringContainsString('Sin tratamiento, la oferta solo se anuncia', $html);

        // Una oferta vieja en $ se puede seguir editando con su unidad.
        $vieja = $this->oferta(['tipo_valor' => 'monto_fijo', 'valor_descuento' => 300, 'cupo_total' => 0]);
        $this->actingAs($admin)->put(route('admin.ofertas.update', $vieja), $base + ['unidad' => 'monto', 'id_tratamiento' => $this->tratamiento->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('−$300', $vieja->fresh()->etiquetaBeneficio());
    }
}
