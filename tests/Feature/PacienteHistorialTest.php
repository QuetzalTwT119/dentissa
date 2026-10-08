<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\HistorialClinico;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PacienteHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_puede_buscar_y_actualizar_datos_oficiales_del_paciente(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente('Sofia', 'QA');

        $this->actingAs($admin)
            ->get(route('admin.pacientes.index', ['buscar' => 'Sofia']))
            ->assertOk()
            ->assertSee('Sofia QA');

        $this->actingAs($admin)
            ->put(route('admin.pacientes.update', $paciente), [
                'curp' => 'GORP800101HDFRZL09',
                'rfc' => 'GORP800101AB1',
                'ine_clave' => 'ABC123456789012345',
                'tutor_id' => null,
            ])
            ->assertRedirect(route('admin.pacientes.index'));

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'curp' => 'GORP800101HDFRZL09',
            'rfc' => 'GORP800101AB1',
            'ine_clave' => 'ABC123456789012345',
        ]);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'paciente.datos_oficiales_actualizados',
            'tabla' => 'pacientes',
            'registro_id' => $paciente->id,
        ]);
    }

    public function test_el_admin_puede_crear_editar_y_eliminar_una_consulta(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        $datos = [
            'fecha_consulta' => '2026-10-07',
            'tipo_consulta' => 'primera_vez',
            'motivo_consulta' => 'Consulta de QA',
            'diagnostico' => 'Registro de prueba',
            'tratamiento_realizado' => '',
            'medicamentos_recetados' => '',
            'observaciones' => '',
            'notas_doctor' => '',
        ];

        $this->actingAs($admin)
            ->post(route('admin.pacientes.historial.store', $paciente), $datos)
            ->assertRedirect(route('admin.pacientes.historial.index', $paciente));

        $historial = $paciente->historialClinico()->firstOrFail();
        $this->assertDatabaseHas('historial_clinico', [
            'id' => $historial->id,
            'id_doctor' => $admin->id,
            'motivo_consulta' => 'Consulta de QA',
        ]);
        $creacion = LogAuditoria::where('tabla', 'historial_clinico')
            ->where('registro_id', $historial->id)
            ->where('accion', 'historial.creado')
            ->firstOrFail();
        $this->assertArrayNotHasKey('diagnostico', $creacion->datos_nuevos ?? []);
        $this->assertArrayNotHasKey('medicamentos_recetados', $creacion->datos_nuevos ?? []);

        $this->actingAs($admin)
            ->put(route('admin.pacientes.historial.update', [$paciente, $historial]), [
                ...$datos,
                'motivo_consulta' => 'Consulta de QA editada',
            ])
            ->assertRedirect(route('admin.pacientes.historial.index', $paciente));

        $this->assertDatabaseHas('historial_clinico', [
            'id' => $historial->id,
            'motivo_consulta' => 'Consulta de QA editada',
        ]);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'historial.actualizado',
            'tabla' => 'historial_clinico',
            'registro_id' => $historial->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pacientes.historial.destroy', [$paciente, $historial]))
            ->assertRedirect(route('admin.pacientes.historial.index', $paciente));

        $this->assertDatabaseMissing('historial_clinico', ['id' => $historial->id]);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'historial.eliminado',
            'tabla' => 'historial_clinico',
            'registro_id' => $historial->id,
        ]);
    }

    public function test_el_admin_puede_descargar_el_historial_en_pdf(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        HistorialClinico::create([
            'id_paciente' => $paciente->id,
            'id_doctor' => $admin->id,
            'fecha_consulta' => '2026-10-07',
            'tipo_consulta' => 'primera_vez',
            'motivo_consulta' => 'Consulta para PDF',
            'diagnostico' => 'Registro de prueba',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.pacientes.historial.pdf', $paciente));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_un_asistente_no_puede_acceder_al_modulo_administrativo_de_pacientes(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('admin.pacientes.index'))
            ->assertForbidden();
    }

    private function crearPaciente(string $nombre = 'Paciente', string $apellido = 'QA'): Paciente
    {
        return Paciente::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => '5555555555',
            'email' => fake()->unique()->safeEmail(),
        ]);
    }
}
