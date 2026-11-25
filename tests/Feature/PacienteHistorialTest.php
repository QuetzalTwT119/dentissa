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

    public function test_el_admin_puede_buscar_y_actualizar_datos_del_paciente(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente('Sofia', 'QA');
        $paciente->update(['telefono' => '5551236789']);

        $this->actingAs($admin)
            ->get(route('admin.pacientes.index', ['buscar' => 'ofi']))
            ->assertOk()
            ->assertSee('Sofia QA');

        $this->actingAs($admin)
            ->get(route('admin.pacientes.index', ['buscar' => '1236']))
            ->assertOk()
            ->assertSee('Sofia QA');

        $this->actingAs($admin)
            ->get(route('admin.pacientes.edit', $paciente))
            ->assertOk()
            ->assertSee('Nombre')
            ->assertSee('Apellido')
            // 22/11/2026: teléfono obligatorio; sin CURP, RFC, INE ni tutor.
            ->assertSee('Teléfono celular')
            ->assertSee('Correo electrónico (opcional)')
            ->assertDontSee('name="curp"', false)
            ->assertDontSee('name="tutor_id"', false)
            ->assertDontSee('Identificadores Oficiales');

        $this->actingAs($admin)
            ->put(route('admin.pacientes.update', $paciente), [
                'nombre' => 'Sofia Maria',
                'apellido' => 'QA Actualizada',
                'telefono' => '+52 (755) 555-1234',
                'email' => 'sofia.actualizada@example.test',
                // Si llegaran, se ignoran: ya no se editan desde aquí.
                'curp' => 'GORP800101HDFRZL09',
            ])
            ->assertRedirect(route('admin.pacientes.index'));

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'nombre' => 'Sofia Maria',
            'apellido' => 'QA Actualizada',
            'telefono' => '+52 (755) 555-1234',
            'email' => 'sofia.actualizada@example.test',
            'curp' => null,
        ]);
        $evento = LogAuditoria::where('id_usuario', $admin->id)
            ->where('accion', 'paciente.datos_actualizados')
            ->where('tabla', 'pacientes')
            ->where('registro_id', $paciente->id)
            ->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['nombre', 'apellido', 'telefono', 'email'],
            $evento->datos_nuevos['campos_modificados']
        );
        $this->assertStringNotContainsString('sofia.actualizada@example.test', json_encode($evento->datos_nuevos));
        $this->assertStringNotContainsString('+52 (755) 555-1234', json_encode($evento->datos_nuevos));
    }

    public function test_el_admin_no_puede_asignar_un_correo_duplicado_ni_un_telefono_invalido(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente('Sofia', 'QA');
        $otroPaciente = $this->crearPaciente('Otro', 'Paciente');

        $datos = [
            'nombre' => 'Sofia',
            'apellido' => 'QA',
            'telefono' => '5551236789',
            'email' => $otroPaciente->email,
        ];

        $this->actingAs($admin)
            ->from(route('admin.pacientes.edit', $paciente))
            ->put(route('admin.pacientes.update', $paciente), $datos)
            ->assertRedirect(route('admin.pacientes.edit', $paciente))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->from(route('admin.pacientes.edit', $paciente))
            ->put(route('admin.pacientes.update', $paciente), [
                ...$datos,
                'telefono' => '555-123',
                'email' => $paciente->email,
            ])
            ->assertRedirect(route('admin.pacientes.edit', $paciente))
            ->assertSessionHasErrors('telefono');

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'telefono' => '5544332211',
            'email' => $paciente->email,
        ]);
        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'paciente.datos_actualizados',
            'tabla' => 'pacientes',
            'registro_id' => $paciente->id,
        ]);
    }

    public function test_el_admin_puede_crear_editar_y_eliminar_una_consulta(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente();

        $this->actingAs($admin)
            ->get(route('admin.pacientes.historial.create', $paciente))
            ->assertOk()
            ->assertSee('Fecha de consulta (obligatoria)')
            ->assertSee('Tipo de consulta (obligatorio)')
            ->assertSee('Motivo de la consulta (obligatorio)')
            ->assertSee('Diagnóstico (opcional)')
            ->assertSee('Tratamiento realizado (opcional)')
            ->assertSee('Medicamentos recetados (opcional)')
            ->assertSee('Observaciones (opcional)')
            ->assertSee('Notas privadas del doctor (opcional)')
            ->assertSee('id="tipo_consulta" name="tipo_consulta" required', false);

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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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

    public function test_el_pdf_no_se_ofrece_ni_se_genera_si_el_historial_esta_vacio(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente();

        $response = $this->actingAs($admin)
            ->get(route('admin.pacientes.historial.index', $paciente));

        $response
            ->assertOk()
            ->assertSee('Este paciente todavía no tiene consultas registradas.')
            ->assertSee('Registra la primera con «Nueva consulta», o desde la Agenda al completar su cita.')
            ->assertSee('data-nueva-consulta', false)
            ->assertSee('data-volver', false)
            ->assertSee('Volver a Pacientes')
            ->assertSee('disabled', false)
            ->assertDontSee('href="'.route('admin.pacientes.historial.pdf', $paciente).'"', false);

        $this->actingAs($admin)
            ->get(route('admin.pacientes.historial.pdf', $paciente))
            ->assertRedirect(route('admin.pacientes.historial.index', $paciente))
            ->assertSessionHas('status', 'Aún no hay historia clínica ni consultas para generar el PDF.');
    }

    public function test_un_asistente_ve_el_directorio_pero_no_el_historial(): void
    {
        // 25/11/2026: recepción ve la lista de pacientes; las consultas, no.
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5511223344']);

        $this->actingAs($asistente)->get(route('admin.pacientes.index'))->assertOk();
        $this->actingAs($asistente)->get(route('admin.pacientes.historial.index', $paciente))->assertForbidden();
    }

    private function crearPaciente(string $nombre = 'Paciente', string $apellido = 'QA'): Paciente
    {
        return Paciente::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => '5544332211',
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    /** 22/11/2026: el teléfono es obligatorio (por él se avisa por WhatsApp); el correo sigue opcional. */
    public function test_el_telefono_es_obligatorio_y_el_correo_opcional(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente('Sofia', 'QA');

        $this->actingAs($admin)->from(route('admin.pacientes.edit', $paciente))
            ->put(route('admin.pacientes.update', $paciente), ['nombre' => 'Sofia', 'apellido' => 'QA', 'telefono' => '', 'email' => ''])
            ->assertSessionHasErrors('telefono');

        $this->actingAs($admin)
            ->put(route('admin.pacientes.update', $paciente), ['nombre' => 'Sofia', 'apellido' => 'QA', 'telefono' => '7551020089', 'email' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($paciente->fresh()->email);
    }
}
