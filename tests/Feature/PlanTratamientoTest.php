<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\PlanTratamiento;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plan de tratamiento (24/11/2026): pasos pendientes o hechos; el avance se
 * calcula solo.
 */
class PlanTratamientoTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
    }

    private function crearPlan(): PlanTratamiento
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.store', $this->paciente), ['titulo' => 'Rehabilitación']);

        return PlanTratamiento::sole();
    }

    public function test_se_crea_desde_el_expediente_y_lleva_al_plan(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('Plan de tratamiento')
            ->assertSee(route('admin.pacientes.planes.create', $this->paciente), false);

        $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.store', $this->paciente), ['titulo' => 'Rehabilitación'])
            ->assertRedirect(route('admin.pacientes.planes.show', [$this->paciente, PlanTratamiento::sole()]));

        $plan = PlanTratamiento::sole();
        $this->assertSame('activo', $plan->estado);
        $this->assertSame($this->doctora->id, $plan->id_doctor);
        $this->assertTrue(LogAuditoria::where('accion', 'plan.creado')->exists());

        $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.store', $this->paciente), ['titulo' => ''])->assertSessionHasErrors('titulo');
    }

    public function test_pasos_y_avance(): void
    {
        Tratamiento::create(['nombre' => 'Resina', 'duracion_estimada' => 45, 'activo' => true, 'precio_referencia' => 800]);
        $plan = $this->crearPlan();

        foreach ([['Resina', '36', 800], ['Extracción', '48', 900], ['Limpieza', null, null]] as [$descripcion, $dientes, $costo]) {
            $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.pasos.agregar', [$this->paciente, $plan]), compact('descripcion', 'dientes', 'costo'))
                ->assertSessionHasNoErrors();
        }
        $plan->load('pasos');
        $this->assertSame(3, $plan->pasos->count());
        $this->assertSame(0, $plan->avance());
        $this->assertSame(1700.0, $plan->costoTotal());
        $this->assertNotNull($plan->pasos->first()->id_tratamiento); // "Resina" es del catálogo

        $resina = $plan->pasos->first();
        $this->actingAs($this->doctora)->patch(route('admin.pacientes.planes.pasos.alternar', [$this->paciente, $plan, $resina]))->assertSessionHas('success', 'Paso marcado como hecho.');
        $this->assertSame(33, $plan->fresh()->avance());
        $this->assertSame(now()->toDateString(), $resina->fresh()->hecho_en->toDateString());

        // Desmarcar regresa a pendiente.
        $this->actingAs($this->doctora)->patch(route('admin.pacientes.planes.pasos.alternar', [$this->paciente, $plan, $resina]));
        $this->assertNull($resina->fresh()->hecho_en);

        // Todos hechos: sugiere terminarlo, pero no lo cierra solo.
        foreach ($plan->pasos as $paso) {
            $respuesta = $this->actingAs($this->doctora)->patch(route('admin.pacientes.planes.pasos.alternar', [$this->paciente, $plan, $paso]));
        }
        $respuesta->assertSessionHas('success', fn ($m) => str_contains($m, 'Todos los pasos están hechos'));
        $this->assertSame(100, $plan->fresh()->avance());
        $this->assertSame('activo', $plan->fresh()->estado);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.planes.show', [$this->paciente, $plan]))->assertOk()
            ->assertSee('data-porcentaje>100 %', false)
            ->assertSee('$1,700');
    }

    public function test_quitar_paso_y_cambiar_estado(): void
    {
        $plan = $this->crearPlan();
        $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.pasos.agregar', [$this->paciente, $plan]), ['descripcion' => 'Endodoncia']);
        $paso = $plan->pasos()->sole();

        $this->actingAs($this->doctora)->delete(route('admin.pacientes.planes.pasos.quitar', [$this->paciente, $plan, $paso]));
        $this->assertSame(0, $plan->pasos()->count());

        $this->actingAs($this->doctora)->put(route('admin.pacientes.planes.update', [$this->paciente, $plan]), ['titulo' => 'Rehabilitación oral', 'estado' => 'terminado'])
            ->assertSessionHasNoErrors();
        $this->assertSame('terminado', $plan->fresh()->estado);
        $this->assertTrue(LogAuditoria::where('accion', 'plan.actualizado')->exists());
    }

    public function test_no_se_mezclan_planes_ni_pasos_de_otros_pacientes(): void
    {
        $plan = $this->crearPlan();
        $otro = Paciente::create(['nombre' => 'Beto', 'apellido' => 'Paz', 'telefono' => '5533334444']);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.planes.show', [$otro, $plan]))->assertNotFound();
        $this->actingAs($this->doctora)->post(route('admin.pacientes.planes.pasos.agregar', [$otro, $plan]), ['descripcion' => 'X'])->assertNotFound();
    }

    public function test_la_asistente_no_accede(): void
    {
        $plan = $this->crearPlan();
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->get(route('admin.pacientes.planes.show', [$this->paciente, $plan]))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.pacientes.planes.store', $this->paciente), ['titulo' => 'X'])->assertForbidden();
    }
}
