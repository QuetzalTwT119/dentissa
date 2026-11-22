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
}
