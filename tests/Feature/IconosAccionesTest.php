<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\MensajeContacto;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario): algunos íconos de acción eran
 * ambiguos (⏻ para ocultar una oferta, ✓/✕ para moderar). Ahora llevan una
 * palabra; editar, eliminar y las flechas siguen solo con ícono. El ⓘ
 * informativo pasa a amarillo con la "i" oscura.
 */
class IconosAccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_acciones_ambiguas_llevan_palabra_y_el_info_es_amarillo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);
        Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => 5, 'comentario' => 'Excelente atención en todo.', 'estado' => 'pendiente']);
        MensajeContacto::create(['nombre' => 'Luis', 'email' => 'luis@example.test', 'mensaje' => 'Quisiera informes.', 'leido' => false]);

        $html = $this->actingAs($admin)->get(route('admin.testimonios.index'))->assertOk()->getContent();
        $this->assertStringContainsString('<span>Aprobar</span>', $html);
        $this->assertStringContainsString('<span>Rechazar</span>', $html);

        $this->actingAs($admin)->get(route('admin.mensajes.index'))->assertOk()->assertSee('<span>Marcar leído</span>', false);

        // ⓘ amarillo (antes gris) en la cuenta propia de Usuarios.
        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk()
            ->assertSee('data-ayuda-info', false)
            ->assertSee('fill-amber-400', false);
    }

    /**
     * 22/11/2026 (encontrado por el usuario): con "Ocultar" (más ancho) y el grupo
     * centrado, Editar y Eliminar cambiaban de lugar en cada fila; y el ⚠ suelto no
     * decía si era acción o aviso.
     */
    public function test_acciones_a_la_derecha_y_bloqueos_con_su_accion_apagada(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $tratamiento = \App\Models\Tratamiento::create(['nombre' => 'Ortodoncia', 'duracion_estimada' => 60, 'activo' => false]);
        \App\Models\Oferta::create(['titulo' => 'Brackets', 'descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'inactiva', 'id_tratamiento' => $tratamiento->id]);

        $html = $this->actingAs($admin)->get(route('admin.ofertas.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~data-no-publicable>.*?<span class="opacity-70">Publicar</span>~s', $html);

        foreach (['admin.ofertas.index', 'admin.servicios.index', 'admin.tratamientos.index', 'admin.usuarios.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();
            $this->assertStringNotContainsString('lg:justify-center', $html, $ruta);
            $this->assertStringNotContainsString('lg:text-center">Acciones', $html, $ruta);
        }
    }
}
