<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Testimonio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba de diseño y UX de las vistas públicas (26/11/2026, H-111).
 * Lo visual se verificó en el navegador; aquí se fija el marcado que lo hace posible.
 */
class UxPortadaMovilTest extends TestCase
{
    use RefreshDatabase;

    /** En un 360×640 el botón "Agendar mi cita" quedaba abajo del borde: la foto era 4:3. */
    public function test_la_foto_del_inicio_es_baja_en_celular_para_ver_el_boton(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="relative sm:aspect-\[16\/9\][^"]*aspect-\[2\/1\]"/', $html);
        $this->assertStringNotContainsString('aspect-[4/3]', $html);
    }

    /** 27/11/2026: con la barra de promoción arriba, la foto baja a 5:2 en celular. */
    public function test_con_barra_de_promocion_la_foto_es_mas_baja_en_celular(): void
    {
        \App\Models\Oferta::create(['titulo' => 'Limpieza', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15,
            'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addDays(3)->toDateString(), 'cupo_total' => 10, 'estado' => 'activa']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="relative sm:aspect-\[16\/9\][^"]*aspect-\[5\/2\]"/', $html);
    }

    /** "Leer completo" se medía una sola vez y salía en opiniones cortas. */
    public function test_leer_completo_se_vuelve_a_medir_al_cargar_letras_y_cambiar_tamano(): void
    {
        $paciente = Paciente::create(['nombre' => 'Itzel', 'apellido' => 'Ramos', 'telefono' => '55 1234 5678', 'email' => 'itzel@example.test']);
        Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => 5, 'comentario' => 'Muy buena atención al cliente, es muy amable.', 'estado' => 'aprobado', 'fecha_publicacion' => now()]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('document.fonts?.ready.then(() => this.medir())', $html);
        $this->assertStringContainsString('new ResizeObserver(() => this.medir()).observe(this.$refs.texto)', $html);
        // Sin JavaScript el botón no se ve: arranca oculto y solo aparece si "cortado".
        $this->assertMatchesRegularExpression('/x-show="cortado" x-cloak/', $html);
    }

    /** En celular el resumen de la agenda repetía el paso; solo sale en el último. */
    public function test_el_resumen_de_la_agenda_en_celular_solo_sale_en_el_ultimo_paso(): void
    {
        $this->get(route('citas.publica.create'))->assertOk()
            ->assertSee(':class="paso < pasos.length - 1 && \'hidden sm:block\'" data-resumen-agenda', false)
            ->assertSee('Tu cita, paso a paso');
    }
}
