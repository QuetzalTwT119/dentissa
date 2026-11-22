<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\TipoImagen;
use App\Models\Caso;
use App\Models\Imagen;
use App\Models\Oferta;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026: el filtro global (x-filtro.barra) solo estaba en 6 listas. Con 22
 * ofertas de QA no había forma de buscar. Ahora también lo tienen Ofertas,
 * Tratamientos, Preguntas frecuentes, Casos y Galería.
 */
class FiltrosListasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    private function ver(string $ruta, array $filtros = []): string
    {
        return $this->actingAs($this->admin)->get(route($ruta, $filtros))->assertOk()->getContent();
    }

    public function test_las_listas_que_crecen_tienen_el_filtro_global(): void
    {
        // Notificaciones también (22/11/2026: antes usaba pestañas en lugar del menú).
        $this->assertStringNotContainsString('aria-label="Filtrar notificaciones" class="flex', $this->ver('notificaciones.index'));
        foreach (['notificaciones.index', 'admin.ofertas.index', 'admin.tratamientos.index', 'admin.preguntas-frecuentes.index', 'admin.casos.index', 'admin.galeria.index', 'admin.servicios.index'] as $ruta) {
            $this->assertStringContainsString('data-filtro-barra', $this->ver($ruta), $ruta);
        }
    }

    /**
     * Servicios (22/11/2026): no tenía el filtro global. Con filtro, la posición
     * es la real del carrusel y las flechas no aparecen (moverían junto a un
     * servicio oculto).
     */
    public function test_servicios_se_filtran_sin_perder_su_posicion_real(): void
    {
        Servicio::create(['nombre' => 'Odontología General', 'activo' => true, 'orden' => 1]);
        Servicio::create(['nombre' => 'Ortodoncia', 'activo' => false, 'orden' => 2]);
        Servicio::create(['nombre' => 'Estética 100%', 'activo' => true, 'orden' => 3]);

        $html = $this->ver('admin.servicios.index', ['buscar' => 'orto']);
        $this->assertStringContainsString('Ortodoncia', $html);
        $this->assertStringNotContainsString('Odontología General', $html);
        $this->assertStringContainsString('data-posicion-servicio="2"', $html);
        $this->assertStringContainsString('Para cambiar el orden, quita el filtro', $html);
        $this->assertStringNotContainsString('Subir «Ortodoncia»', $html);

        $html = $this->ver('admin.servicios.index', ['activo' => '1']);
        $this->assertStringContainsString('Estética 100%', $html);
        $this->assertStringNotContainsString('Ortodoncia', $html);
        $this->assertStringContainsString('data-posicion-servicio="3"', $html);

        $this->assertStringContainsString('Estética 100%', $this->ver('admin.servicios.index', ['buscar' => '%']));
        $this->assertStringNotContainsString('Ortodoncia', $this->ver('admin.servicios.index', ['buscar' => '%']));
        $this->assertStringContainsString('No hay servicios con esos criterios', $this->ver('admin.servicios.index', ['buscar' => 'nada así']));

        // Valor inválido: se ignora y sin filtro vuelven las flechas.
        $html = $this->ver('admin.servicios.index', ['activo' => 'x']);
        $this->assertStringContainsString('Subir «Ortodoncia»', $html);
        $this->assertStringContainsString('Odontología General', $html);
    }

    public function test_ofertas_se_filtran_por_texto_estado_y_tratamiento(): void
    {
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        $base = ['descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString()];
        Oferta::create($base + ['titulo' => 'Limpieza express', 'id_tratamiento' => $limpieza->id, 'estado' => 'activa']);
        Oferta::create($base + ['titulo' => 'Blanqueamiento navideño', 'estado' => 'pendiente']);

        $html = $this->ver('admin.ofertas.index', ['buscar' => 'express']);
        $this->assertStringContainsString('Limpieza express', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);

        $html = $this->ver('admin.ofertas.index', ['estado' => 'pendiente']);
        $this->assertStringContainsString('Blanqueamiento navideño', $html);
        $this->assertStringNotContainsString('Limpieza express', $html);

        $html = $this->ver('admin.ofertas.index', ['tratamiento' => $limpieza->id]);
        $this->assertStringContainsString('Limpieza express', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);
        // El selector solo ofrece tratamientos que tienen ofertas.
        $this->assertStringContainsString('<option value="'.$limpieza->id.'" selected', $html);
    }

    public function test_tratamientos_preguntas_casos_y_galeria_se_filtran(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        Tratamiento::create(['nombre' => 'Carillas', 'duracion_estimada' => 60, 'activo' => false]);
        $servicio->tratamientos()->attach($limpieza);

        $html = $this->ver('admin.tratamientos.index', ['activo' => '0']);
        $this->assertStringContainsString('Carillas', $html);
        $this->assertStringNotContainsString('Limpieza Dental', $html);
        $html = $this->ver('admin.tratamientos.index', ['servicio' => $servicio->id]);
        $this->assertStringContainsString('Limpieza Dental', $html);
        $this->assertStringNotContainsString('Carillas', $html);

        PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí, desde los 3 años.', 'activa' => true, 'orden' => 1]);
        PreguntaFrecuente::create(['pregunta' => '¿Hay estacionamiento?', 'respuesta' => 'No contamos con uno.', 'activa' => false, 'orden' => 2]);
        $html = $this->ver('admin.preguntas-frecuentes.index', ['buscar' => '3 años']);
        $this->assertStringContainsString('¿Atienden niños?', $html);
        $this->assertStringNotContainsString('estacionamiento', $html);
        $this->assertStringContainsString('estacionamiento', $this->ver('admin.preguntas-frecuentes.index', ['activa' => '0']));

        Caso::create(['titulo' => 'Blanqueamiento antes y después', 'activo' => true, 'orden' => 1]);
        Caso::create(['titulo' => 'Ortodoncia completa', 'activo' => false, 'orden' => 2]);
        $html = $this->ver('admin.casos.index', ['activo' => '1']);
        $this->assertStringContainsString('Blanqueamiento antes y después', $html);
        $this->assertStringNotContainsString('Ortodoncia completa', $html);

        Imagen::create(['titulo' => 'Recepción', 'url' => 'galeria/a.jpg', 'tipo' => TipoImagen::Galeria, 'orden' => 1, 'activa' => true]);
        Imagen::create(['titulo' => 'Consultorio 2', 'url' => 'galeria/b.jpg', 'tipo' => TipoImagen::Galeria, 'orden' => 2, 'activa' => true]);
        $html = $this->ver('admin.galeria.index', ['buscar' => 'Recep']);
        $this->assertStringContainsString('Recepción', $html);
        $this->assertStringNotContainsString('Consultorio 2', $html);
    }

    /**
     * QA de filtros (22/11/2026): un valor inválido (?estado=xyz) o una búsqueda
     * de más de 100 caracteres regresaba a la página anterior sin explicar nada.
     * Ahora se ignora ese filtro y la lista se muestra.
     */
    public function test_un_filtro_invalido_se_ignora_y_la_lista_se_muestra(): void
    {
        $casos = [
            'admin.ofertas.index' => ['estado' => 'xyz', 'buscar' => str_repeat('x', 101)],
            'admin.tratamientos.index' => ['activo' => '7', 'buscar' => str_repeat('x', 101)],
            'admin.usuarios.index' => ['rol' => 'xyz', 'activo' => '9'],
            'admin.testimonios.index' => ['estado' => 'xyz'],
            'admin.mensajes.index' => ['leido' => 'xyz'],
            'admin.pacientes.index' => ['buscar' => str_repeat('x', 101)],
            'admin.preguntas-frecuentes.index' => ['activa' => 'xyz'],
            'admin.casos.index' => ['activo' => 'xyz'],
            'admin.galeria.index' => ['activa' => 'xyz'],
            'admin.auditoria.index' => ['desde' => 'no-es-fecha', 'usuario_id' => 'abc'],
            'citas.index' => ['estado' => 'xyz', 'fecha' => 'no-es-fecha'],
            'notificaciones.index' => ['estado' => 'xyz'],
        ];

        foreach ($casos as $ruta => $filtros) {
            $this->actingAs($this->admin)->get(route($ruta, $filtros))->assertOk();
        }
    }

    /** QA: "%" y "_" eran comodines de la base de datos y buscar "%" devolvía todo. */
    public function test_porcentaje_y_guion_bajo_se_buscan_tal_cual(): void
    {
        $base = ['descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'activa'];
        Oferta::create($base + ['titulo' => 'Limpieza al 10% menos']);
        Oferta::create($base + ['titulo' => 'Blanqueamiento navideño']);

        $html = $this->ver('admin.ofertas.index', ['buscar' => '%']);
        $this->assertStringContainsString('Limpieza al 10% menos', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);

        $html = $this->ver('admin.ofertas.index', ['buscar' => '_']);
        $this->assertStringNotContainsString('Limpieza al 10% menos', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);

        \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'López', 'telefono' => '5551112222', 'email' => 'ana_lopez@example.test']);
        \App\Models\Paciente::create(['nombre' => 'Luis', 'apellido' => 'Pérez', 'telefono' => '5553334444', 'email' => 'luisperez@example.test']);
        $html = $this->ver('admin.pacientes.index', ['buscar' => '_']);
        $this->assertStringContainsString('ana_lopez@example.test', $html);
        $this->assertStringNotContainsString('luisperez@example.test', $html);
    }
}
