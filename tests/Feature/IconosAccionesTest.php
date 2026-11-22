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
 * ambiguos (⏻ para ocultar una oferta, ✓/✕ para moderar). Se probó ícono +
 * palabra: la columna se salía de la tabla y mezclaba estilos. Decisión: solo
 * íconos, pero que se entiendan solos (ojo, ojo tachado, pulgares, sobre). El
 * ⓘ se probó en amarillo y se regresó a gris (amarillo = ⚠ "revisa esto").
 */
class IconosAccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_acciones_solo_con_iconos_claros_y_info_gris(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);
        Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => 5, 'comentario' => 'Excelente atención en todo.', 'estado' => 'pendiente']);
        MensajeContacto::create(['nombre' => 'Luis', 'email' => 'luis@example.test', 'mensaje' => 'Quisiera informes.', 'leido' => false]);

        $html = $this->actingAs($admin)->get(route('admin.testimonios.index'))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Aprobar testimonio de Ana"', $html);
        $this->assertStringContainsString('aria-label="Rechazar testimonio de Ana"', $html);
        $this->assertStringNotContainsString('<span>Aprobar</span>', $html);
        // Sin la píldora con palabra: todos los botones de la fila son círculos del mismo tamaño.
        $this->assertStringNotContainsString('border border-line bg-white px-3 text-xs', $html);

        $this->actingAs($admin)->get(route('admin.mensajes.index'))->assertOk()
            ->assertSee('aria-label="Marcar como leído"', false)
            ->assertDontSee('<span>Marcar leído</span>', false);

        // ⓘ gris otra vez (sin el círculo amarillo).
        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk()
            ->assertSee('data-ayuda-info', false)
            ->assertDontSee('fill-amber-400', false);
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
        // Publicar (ojo) apagado con su ⚠, del mismo tamaño que los demás botones.
        $this->assertStringContainsString('class="grid h-11 w-11 place-items-center lg:h-9 lg:w-9" data-no-publicable', $html);
        $this->assertStringContainsString('No se puede publicar', $html);

        foreach (['admin.ofertas.index', 'admin.servicios.index', 'admin.tratamientos.index', 'admin.usuarios.index'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();
            $this->assertStringNotContainsString('lg:justify-center', $html, $ruta);
            $this->assertStringNotContainsString('lg:text-center">Acciones', $html, $ruta);
        }
    }
}
