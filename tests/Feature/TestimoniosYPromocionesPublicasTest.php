<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\NotificacionSistema;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Revisión del 20/11/2026: la portada no mostraba ofertas ni los testimonios
 * aprobados (tenía tarjetas fijas) y el paciente no podía escribir el suyo.
 */
class TestimoniosYPromocionesPublicasTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NotificacionSistemaCreada::class]);
        Mail::fake();
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '55 1234 5678', 'email' => 'ana@example.test']);
    }

    private function enviar(array $datos = [])
    {
        return $this->post(route('testimonios.publico.store'), $datos + [
            'contacto' => '+52 (55) 1234-5678',
            'calificacion' => 5,
            'comentario' => 'Me atendieron con mucha paciencia y sin dolor.',
            'autorizacion' => '1',
        ]);
    }

    public function test_el_paciente_deja_su_testimonio_y_queda_pendiente_con_aviso_a_la_clinica(): void
    {
        User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);

        $this->enviar()
            ->assertRedirect(url('/').'#testimonios')
            ->assertSessionHas('testimonio_enviado');

        $testimonio = Testimonio::firstOrFail();
        $this->assertSame($this->paciente->id, $testimonio->id_paciente);
        $this->assertSame('pendiente', $testimonio->estado->value);
        $this->assertSame(1, NotificacionSistema::where('evento', 'testimonio_nuevo')->count());

        // Pendiente: todavía no sale en la portada.
        $this->get('/')->assertOk()->assertDontSee('Me atendieron con mucha paciencia');
    }

    public function test_aprobado_se_publica_con_nombre_e_inicial_y_sin_tarjetas_de_ejemplo(): void
    {
        Testimonio::create(['id_paciente' => $this->paciente->id, 'calificacion' => 4, 'comentario' => 'Excelente trato y muy puntuales.', 'estado' => 'aprobado', 'fecha_publicacion' => now()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Excelente trato y muy puntuales.')
            ->assertSee('Ana M.')
            ->assertSee('4 de 5 estrellas')
            ->assertSee('data-carrusel-testimonios', false)
            ->assertDontSee('Testimonio pendiente de autorización')
            ->assertDontSee('Martínez');
    }

    /**
     * QA de testimonios (22/11/2026): el largo se cortaba sin poder leerlo
     * (portada y panel) y la lista no tenía buscador como las demás.
     */
    public function test_el_testimonio_largo_se_lee_completo_y_el_panel_busca_por_paciente_o_texto(): void
    {
        $otro = Paciente::create(['nombre' => 'Luis', 'apellido' => 'Pérez', 'telefono' => '55 8765 4321']);
        Testimonio::create(['id_paciente' => $this->paciente->id, 'calificacion' => 5, 'comentario' => "Primera línea.\nSegunda línea.", 'estado' => 'aprobado', 'fecha_publicacion' => now()]);
        Testimonio::create(['id_paciente' => $otro->id, 'calificacion' => 3, 'comentario' => 'Me costó encontrar el consultorio 100%.', 'estado' => 'pendiente']);

        $this->get('/')->assertOk()
            ->assertSee('Leer completo')
            ->assertSee('whitespace-pre-line', false)
            ->assertSee("Primera línea.\nSegunda línea.", false);

        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $buscar = fn (string $texto) => $this->actingAs($admin)->get(route('admin.testimonios.index', ['buscar' => $texto]))->assertOk();

        $buscar('Ana Martínez')->assertSee('Primera línea.')->assertDontSee('Me costó encontrar');
        $buscar('consultorio')->assertSee('Me costó encontrar')->assertDontSee('Primera línea.');
        $buscar('%')->assertSee('Me costó encontrar')->assertDontSee('Primera línea.');
        $buscar('nadie así')->assertSee('No hay testimonios registrados con esos criterios');
        $this->actingAs($admin)->get(route('admin.testimonios.index', ['buscar' => 'pérez', 'estado' => 'aprobado']))
            ->assertOk()->assertSee('No hay testimonios registrados con esos criterios');
    }

    public function test_un_contacto_desconocido_recibe_la_misma_respuesta_y_no_se_guarda(): void
    {
        $this->enviar(['contacto' => '55 9999 0000'])->assertSessionHas('testimonio_enviado');
        $this->enviar(['contacto' => 'nadie@example.test'])->assertSessionHas('testimonio_enviado');
        $this->enviar(['sitio_web' => 'http://spam.example'])->assertSessionHas('testimonio_enviado'); // trampa para robots

        $this->assertSame(0, Testimonio::count());
    }

    public function test_con_errores_regresa_a_la_seccion_con_lo_escrito(): void
    {
        $this->from('/')->post(route('testimonios.publico.store'), ['contacto' => 'ana@example.test', 'comentario' => 'corto'])
            ->assertRedirect(url('/').'#testimonios')
            ->assertSessionHasErrors(['calificacion', 'comentario', 'autorizacion'], null, 'testimonio');

        $this->assertSame(0, Testimonio::count());
    }

    public function test_la_portada_muestra_promociones_vigentes(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="promociones"', false);

        Oferta::create(['titulo' => 'Limpieza de temporada', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15,
            'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addWeek()->toDateString(), 'cupo_total' => 10, 'estado' => 'activa']);
        Oferta::create(['titulo' => 'Oferta vencida', 'tipo' => 'descuento', 'tipo_valor' => 'monto_fijo', 'valor_descuento' => 100,
            'fecha_inicio' => now()->subMonth()->toDateString(), 'fecha_fin' => now()->subDay()->toDateString(), 'estado' => 'activa']);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="promociones"', false)
            ->assertSee('Limpieza de temporada')
            ->assertSee('−15%')
            // 21/11/2026: los lugares se cuentan con citas reales (sin citas, quedan todos).
            ->assertSee('Quedan 10 lugares')
            ->assertDontSee('Oferta vencida')
            // 21/11/2026: la sección se llama "Ofertas" (antes "Beneficios vigentes").
            ->assertSee('id="titulo-promociones" class="mt-4 text-title-1 text-ink">Ofertas</h2>', false)
            ->assertDontSee('Beneficios vigentes');
    }

    /**
     * 21/11/2026: el formulario pedía Tipo de oferta, Tipo de valor, Valor del
     * descuento y Precio especial (se contradecían). Ahora: "Beneficio para el paciente"
     * (Descuento en % o $, Precio especial o Gratis) + cantidad.
     */
    public function test_que_ofreces_se_guarda_en_las_columnas_y_se_ve_igual_en_todas_partes(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $base = ['descripcion' => 'Válida para pacientes nuevos y de siempre.', 'fecha_fin' => now()->addMonth()->toDateString()];

        $casos = [
            'Veinte por ciento' => [['beneficio' => 'descuento', 'unidad' => 'porcentaje', 'cantidad' => 20], '−20%', 'porcentaje'],
            'Blanqueamiento especial' => [['beneficio' => 'precio', 'cantidad' => 2800], 'Precio especial $2,800', 'precio'],
            // Gratis no necesita cantidad; si llega una, se ignora.
            'Valoración sin costo' => [['beneficio' => 'gratis', 'unidad' => 'monto', 'cantidad' => 999], 'Gratis', 'gratis'],
        ];

        foreach ($casos as $titulo => [$campos, $etiqueta, $interno]) {
            $this->actingAs($admin)
                ->post(route('admin.ofertas.store'), ['titulo' => $titulo] + $campos + $base)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.ofertas.index'));

            $oferta = Oferta::where('titulo', $titulo)->firstOrFail();
            $this->assertSame($interno, $oferta->beneficio(), $titulo);
            $this->assertSame($etiqueta, $oferta->etiquetaBeneficio(), $titulo);
        }

        // Gratis no guarda descuento ni precio especial (antes "Bonificación" con $300 salía "−$300").
        $gratis = Oferta::where('titulo', 'Valoración sin costo')->first();
        $this->assertNull($gratis->valor_descuento);
        $this->assertNull($gratis->precio_especial);

        // Un descuento en $ ya no se crea desde el formulario (es "Precio especial");
        // una oferta vieja así se sigue viendo igual.
        Oferta::create(['titulo' => 'Trescientos menos', 'descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'monto_fijo', 'valor_descuento' => 300,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'pendiente']);

        // Recién creadas quedan En revisión: no salen en la portada hasta publicarlas.
        $this->assertSame(4, Oferta::where('estado', 'pendiente')->count());
        $this->get('/')->assertOk()->assertDontSee('id="promociones"', false);
        foreach (Oferta::all() as $o) {
            $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $o))->assertSessionHas('success');
        }

        // La misma etiqueta en la portada, la lista del panel y la vista de ofertas del personal
        // (esta última mostraba "$20 de descuento" para un 20 %: comparaba el enum con un texto).
        foreach (['/', route('admin.ofertas.index'), route('ofertas.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertSee('−20%')->assertSee('−$300')->assertSee('Precio especial $2,800')->assertSee('Gratis')
                ->assertDontSee('$20 de descuento');
        }

        // Editar recupera la elección y la cantidad, y las fechas en formato del <input type="date">.
        $precio = Oferta::where('titulo', 'Blanqueamiento especial')->first();
        $this->actingAs($admin)->get(route('admin.ofertas.edit', $precio))->assertOk()
            ->assertSee('name="beneficio" value="precio" class="sr-only" checked', false)
            ->assertSee('value="2800"', false)
            ->assertSee('value="'.now()->addMonth()->toDateString().'"', false);
    }

    public function test_que_ofreces_valida_la_cantidad(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $base = ['titulo' => 'Prueba de oferta', 'descripcion' => 'Válida para pacientes nuevos y de siempre.', 'fecha_fin' => now()->addWeek()->toDateString()];

        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'descuento', 'unidad' => 'porcentaje', 'cantidad' => 120])
            ->assertSessionHasErrors('cantidad');
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'precio'])
            ->assertSessionHasErrors('cantidad');
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'otra'])
            ->assertSessionHasErrors('beneficio');

        // Nueva oferta: sin "Empieza" ni "Mostrar" (cuenta desde hoy y se publica desde la lista).
        $this->actingAs($admin)->get(route('admin.ofertas.create'))->assertOk()
            ->assertDontSee('name="fecha_inicio"', false)
            ->assertDontSee('name="activa"', false)
            ->assertDontSee('data-aviso-revision', false)
            ->assertDontSee('name="tipo_valor"', false)
            ->assertDontSee('name="precio_especial"', false);

        // Lo opcional va plegado en "Más detalles" al crear (solo título, qué ofreces, cantidad y hasta).
        $this->actingAs($admin)->get(route('admin.ofertas.create'))->assertOk()
            ->assertSee('data-mas-detalles', false)
            ->assertDontSee(' open data-mas-detalles', false);

        $this->assertSame(0, Oferta::count());
    }

    /** 21/11/2026: validación estricta antes de guardar (frontend y backend). */
    public function test_la_oferta_rechaza_enlaces_spam_y_textos_cortos(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $base = ['titulo' => 'Limpieza de fin de año', 'descripcion' => 'Válida para pacientes nuevos y de siempre.',
            'beneficio' => 'descuento', 'unidad' => 'porcentaje', 'cantidad' => 20, 'fecha_fin' => now()->addWeek()->toDateString()];

        $invalidos = [
            'descripcion' => ['Visita https://promo.example', 'Escribe a promo@correo.com ya mismo', 'Gana premios en www.sitio.mx hoy', 'Muy corta', '<script>alert(1)</script> texto largo', 'Oferta!!!!!!!!!! increíble ahora'],
            'titulo' => ['Hola', 'Ve a descuentos.com'],
            'fecha_fin' => [now()->subDay()->toDateString()],
        ];

        foreach ($invalidos as $campo => $valores) {
            foreach ($valores as $valor) {
                $this->actingAs($admin)->post(route('admin.ofertas.store'), [$campo => $valor] + $base)
                    ->assertSessionHasErrors($campo);
            }
        }
        $this->actingAs($admin)->post(route('admin.ofertas.store'), ['beneficio' => ''] + $base)
            ->assertSessionHasErrors(['beneficio' => 'Elige el beneficio para el paciente.']);

        $this->assertSame(0, Oferta::count());

        // "Dra." o "1.5" no son enlaces.
        $this->actingAs($admin)->post(route('admin.ofertas.store'), ['descripcion' => 'Con la Dra. López, anestesia 1.5 incluida.'] + $base)
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Oferta::count());
    }

    /** 21/11/2026: En revisión → Publicada → Oculta. Solo las publicadas se ven al público. */
    public function test_solo_las_publicadas_se_ven_y_editar_una_publicada_la_regresa_a_revision(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $oferta = Oferta::create(['titulo' => 'Resina estética', 'descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'monto_fijo', 'valor_descuento' => 300,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addWeek()->toDateString(), 'estado' => 'pendiente']);

        // En revisión: no en la portada ni en /ofertas; sí en el panel con ✓ Publicar.
        $this->get('/')->assertDontSee('data-oferta-publica', false);
        $this->actingAs($admin)->get(route('ofertas.index'))->assertDontSee('Resina estética');
        $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertOk()
            ->assertSee('Resina estética')->assertSee('En revisión')
            ->assertSee(route('admin.ofertas.publicar', $oferta), false);

        $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $oferta))->assertSessionHas('success');
        $this->get('/')->assertSee('data-oferta-publica', false);

        // Cambiar lo que ve el paciente la regresa a revisión.
        $this->actingAs($admin)->put(route('admin.ofertas.update', $oferta), [
            'titulo' => 'Resina estética', 'descripcion' => 'Ahora con pulido incluido para todos.', 'beneficio' => 'descuento', 'unidad' => 'monto', 'cantidad' => 300,
            'fecha_fin' => now()->addWeek()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $oferta->fresh()->estado->value);
        $this->get('/')->assertDontSee('data-oferta-publica', false);

        // Oculta: fuera del público, sigue en el panel para volver a publicarla.
        $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $oferta));
        $this->actingAs($admin)->patch(route('admin.ofertas.ocultar', $oferta))->assertSessionHas('success');
        $this->get('/')->assertDontSee('data-oferta-publica', false);
        $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertSee('Oculta')
            ->assertSee(route('admin.ofertas.publicar', $oferta), false);

        // Vencida: no se puede publicar.
        $oferta->update(['fecha_fin' => now()->subDay()->toDateString()]);
        $this->actingAs($admin)->patch(route('admin.ofertas.publicar', $oferta))->assertSessionHas('error');
        $this->assertSame('inactiva', $oferta->fresh()->estado->value);
    }

    /**
     * 21/11/2026: "Beneficio para el paciente" con 3 opciones (antes "% de descuento"
     * y "$ de descuento" repetían lo mismo) y el diseño global x-opcion (rosa claro).
     */
    public function test_beneficio_tiene_tres_opciones_con_el_diseno_global(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $html = $this->actingAs($admin)->get(route('admin.ofertas.create'))->assertOk()
            ->assertSee('Beneficio para el paciente')
            ->assertDontSee('¿Qué ofreces?')
            ->assertDontSee('% de descuento')
            ->assertSee('x-data="{ beneficio: \'\', unidad: \'porcentaje\' }"', false)
            ->assertSee("x-on:click=\"if (beneficio === 'gratis') \$nextTick(() => beneficio = '')\"", false)
            ->getContent();

        $this->assertSame(3, substr_count($html, 'name="beneficio"'));
        // Descuento solo en %: sin botón de unidad (solo un campo oculto).
        $this->assertSame(1, substr_count($html, 'name="unidad"'));
        $this->assertSame(0, substr_count($html, 'data-unidad-descuento'));
        $this->assertSame(3, substr_count($html, 'class="opcion'));

        // Una oferta en $ se abre con Descuento y $ elegidos.
        $oferta = Oferta::create(['titulo' => 'Resina estética', 'descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'monto_fijo', 'valor_descuento' => 300,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addWeek()->toDateString(), 'estado' => 'activa']);
        $this->actingAs($admin)->get(route('admin.ofertas.edit', $oferta))->assertOk()
            ->assertSee('x-data="{ beneficio: \'descuento\', unidad: \'monto\' }"', false);

        // Un descuento necesita unidad, y en una oferta nueva solo puede ser %.
        $base = ['titulo' => 'Prueba de oferta', 'descripcion' => 'Válida para pacientes nuevos y de siempre.', 'fecha_fin' => now()->addWeek()->toDateString()];
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'descuento', 'cantidad' => 50])
            ->assertSessionHasErrors('unidad');
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'descuento', 'unidad' => 'monto', 'cantidad' => 500])
            ->assertSessionHasErrors('unidad');
        $this->actingAs($admin)->post(route('admin.ofertas.store'), $base + ['beneficio' => 'descuento', 'unidad' => 'porcentaje', 'cantidad' => 50])
            ->assertSessionHasNoErrors();
        $this->assertSame('−50%', Oferta::where('titulo', 'Prueba de oferta')->first()->etiquetaBeneficio());
    }
}
