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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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

    /**
     * QA con 21 ofertas (22/11/2026): con varias ofertas del mismo tratamiento la
     * agenda solo recibía la que vence primero y, con una fecha posterior, decía
     * "ya no aplica" aunque al guardar se aplicara otra. Ahora recibe todas.
     */
    public function test_la_agenda_recibe_todas_las_ofertas_del_tratamiento_y_coincide_con_lo_que_se_guarda(): void
    {
        $this->oferta(['titulo' => 'Limpieza que vence hoy', 'fecha_fin' => now()->toDateString(), 'cupo_total' => 0]);
        $larga = $this->oferta(['titulo' => 'Limpieza para estudiantes', 'valor_descuento' => 20, 'fecha_fin' => now()->addMonths(2)->toDateString(), 'cupo_total' => 0]);

        $this->get(route('citas.publica.create'))->assertOk()
            ->assertSee('Limpieza que vence hoy')
            ->assertSee('Limpieza para estudiantes')
            ->assertSee('lista.find(o => this.fecha <= o.hasta)', false);

        // Con una cita después de hoy, el servidor aplica la que sigue vigente.
        $despues = now()->addWeek();
        while ($despues->isSunday()) {
            $despues->addDay();
        }
        $this->agendar('09:00', $despues)->assertSessionHasNoErrors();
        $this->assertSame($larga->id, Cita::latest('id')->first()->id_oferta);
    }

    /** QA: la portada mostraba menos del máximo si una de las primeras estaba agotada, y "ya se ve" no siempre era cierto. */
    public function test_la_portada_muestra_hasta_12_disponibles_y_el_mensaje_no_promete_de_mas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        // La que vence primero está agotada: no debe "ocupar" uno de los 12 lugares.
        $agotada = $this->oferta(['titulo' => 'Agotada', 'cupo_total' => 1, 'fecha_fin' => now()->addDay()->toDateString()]);
        Cita::create(['id_paciente' => \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'López', 'telefono' => '5550001111'])->id,
            'id_servicio' => $this->servicio->id, 'id_tratamiento' => $this->tratamiento->id, 'id_oferta' => $agotada->id,
            'fecha_hora' => $this->fecha->copy()->setTime(9, 0), 'duracion_estimada' => 30, 'estado' => EstadoCita::Confirmada]);
        foreach (range(1, Oferta::MAXIMO_PORTADA) as $n) {
            $this->oferta(['titulo' => "Oferta número {$n}", 'cupo_total' => 0, 'fecha_fin' => now()->addDays(10 + $n)->toDateString()]);
        }

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(Oferta::MAXIMO_PORTADA, substr_count($html, 'data-oferta-publica'));
        // Carrusel global (como Especialidades y Testimonios) y descripción completa, sin cortar.
        $this->assertStringContainsString('data-carrusel-ofertas', $html);
        $this->assertStringNotContainsString('line-clamp-2 text-sm text-muted', $html);
        $this->assertStringNotContainsString('>Agotada<', $html);

        // Una más (vence al último) se publica, pero no cabe: el mensaje lo dice.
        $ultima = $this->oferta(['titulo' => 'La que vence al último', 'cupo_total' => 0, 'fecha_fin' => now()->addYear()->toDateString(), 'estado' => 'pendiente']);
        $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $ultima))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'La portada muestra las '.Oferta::MAXIMO_PORTADA.' que vencen primero'));
    }

    /** QA: 100 % salía "−100%" en la portada; el tratamiento inactivo no decía por qué. */
    public function test_cien_por_ciento_es_gratis_y_el_tratamiento_inactivo_se_explica(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $inactivo = Tratamiento::create(['nombre' => 'Carillas', 'duracion_estimada' => 60, 'categoria' => 'Estética', 'activo' => false]);
        $base = ['titulo' => 'Prueba de oferta', 'descripcion' => 'Válida para pacientes nuevos y de siempre.', 'beneficio' => 'descuento', 'unidad' => 'porcentaje', 'fecha_fin' => now()->addWeek()->toDateString()];

        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['cantidad' => 100])
            ->assertSessionHasErrors(['cantidad' => 'Un descuento de 100 % es gratis: elige «Gratis».']);
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['cantidad' => 10, 'id_tratamiento' => $inactivo->id])
            ->assertSessionHasErrors(['id_tratamiento' => 'Ese tratamiento ya no se agenda en línea (está inactivo). Elige otro o actívalo en Catálogo.']);
        $this->assertSame(0, Oferta::count());
    }

    /**
     * QA (22/11/2026, encontrado por el usuario): la lista decía "Publicada" de
     * una oferta cuyo tratamiento estaba inactivo y que no se veía en ningún lado.
     */
    public function test_la_lista_dice_cuando_una_publicada_no_se_ve_y_por_que(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $inactivo = Tratamiento::create(['nombre' => 'Ortodoncia', 'duracion_estimada' => 60, 'categoria' => 'Ortodoncia', 'activo' => false]);
        $this->servicio->tratamientos()->attach($inactivo);

        $visible = $this->oferta(['titulo' => 'Se ve bien', 'cupo_total' => 0]);
        $this->oferta(['titulo' => 'De tratamiento inactivo', 'id_tratamiento' => $inactivo->id, 'cupo_total' => 0]);
        $vencida = $this->oferta(['titulo' => 'Ya vencida', 'cupo_total' => 0]);
        $vencida->forceFill(['fecha_fin' => now()->subDay()->toDateString()])->save();

        $html = $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-no-visible'));
        $this->assertStringContainsString('Publicada, no se ve', $html);
        $this->assertStringContainsString('Su tratamiento está inactivo.', $html);
        $this->assertStringContainsString('Venció el', $html);
        // La que sí se ve sigue diciendo "Publicada".
        $this->assertNull($visible->fresh()->motivoNoVisible([$visible->id]));

        // Sin lugares, o fuera de las que caben en la portada, también se explica.
        $sinLugar = $this->oferta(['titulo' => 'Sin lugar', 'cupo_total' => 1]);
        Cita::create(['id_paciente' => \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'López', 'telefono' => '5550002222'])->id,
            'id_servicio' => $this->servicio->id, 'id_tratamiento' => $this->tratamiento->id, 'id_oferta' => $sinLugar->id,
            'fecha_hora' => $this->fecha->copy()->setTime(9, 0), 'duracion_estimada' => 30, 'estado' => EstadoCita::Confirmada]);
        $this->assertStringStartsWith('Ya no le quedan lugares.', $sinLugar->fresh()->motivoNoVisible(Oferta::paraPortada()->pluck('id')->all()));
        $this->assertStringStartsWith('La portada muestra las', $visible->fresh()->motivoNoVisible([]));
    }
}
