<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\DatosConsultorio;
use App\Models\HorarioAtencion;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 25/11/2026: la foto grande del inicio se cambia desde Contenido → Galería, y
 * las tarjetas de fábrica dicen lo que de verdad hace el consultorio.
 */
class FotoInicioPortadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::forget('datos_consultorio');
    }

    private function doctora(): User
    {
        return User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    public function test_sin_foto_subida_la_portada_usa_la_de_fabrica(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('/images/dentissa/hero-inicio.jpg')
            ->assertSee('Una dentista conversa con su paciente');
    }

    public function test_la_doctora_sube_la_foto_de_inicio_y_la_portada_la_usa(): void
    {
        $doctora = $this->doctora();

        $this->actingAs($doctora)
            ->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->image('consultorio.jpg', 1600, 900)])
            ->assertRedirect(route('admin.galeria.index').'#foto-inicio');

        $ruta = DatosConsultorio::fotoInicio();
        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);
        $this->assertTrue(LogAuditoria::where('accion', 'portada.foto_inicio_cambiada')->exists());

        $this->get('/')->assertOk()
            ->assertSee('storage/'.$ruta, false)
            ->assertDontSee('/images/dentissa/hero-inicio.jpg');

        // Galería muestra la foto actual con su botón para quitarla.
        $this->actingAs($doctora)->get(route('admin.galeria.index'))->assertOk()
            ->assertSee('Foto de inicio de la portada')
            ->assertSee('Quitar y usar la de fábrica');
    }

    public function test_cambiar_la_foto_borra_la_anterior_y_quitarla_vuelve_a_la_de_fabrica(): void
    {
        $doctora = $this->doctora();
        $this->actingAs($doctora)->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->image('a.jpg')]);
        $primera = DatosConsultorio::fotoInicio();

        $this->actingAs($doctora)->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->image('b.jpg')]);
        $segunda = DatosConsultorio::fotoInicio();
        $this->assertNotSame($primera, $segunda);
        Storage::disk('public')->assertMissing($primera);

        $this->actingAs($doctora)->delete(route('admin.galeria.foto-inicio.destroy'))
            ->assertRedirect(route('admin.galeria.index').'#foto-inicio');
        $this->assertNull(DatosConsultorio::fotoInicio());
        Storage::disk('public')->assertMissing($segunda);
        $this->get('/')->assertSee('/images/dentissa/hero-inicio.jpg');
    }

    public function test_solo_acepta_fotos_de_hasta_5_mb(): void
    {
        $doctora = $this->doctora();

        $this->actingAs($doctora)->from(route('admin.galeria.index'))
            ->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('foto_inicio');
        $this->actingAs($doctora)->from(route('admin.galeria.index'))
            ->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->image('enorme.jpg')->size(6000)])
            ->assertSessionHasErrors(['foto_inicio' => 'La foto pesa más de 5 MB. Prueba con una más ligera.']);
        $this->assertNull(DatosConsultorio::fotoInicio());
    }

    public function test_la_asistente_no_puede_cambiar_la_foto(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->post(route('admin.galeria.foto-inicio.store'), ['foto_inicio' => UploadedFile::fake()->image('a.jpg')])
            ->assertForbidden();
    }

    public function test_la_tarjeta_dice_cuantos_pacientes_se_atienden_a_la_vez(): void
    {
        HorarioAtencion::query()->delete();
        foreach (range(0, 6) as $dia) {
            HorarioAtencion::create(['dia_semana' => $dia, 'abierto' => $dia !== 0, 'hora_apertura' => 9, 'hora_cierre' => 18, 'capacidad_simultanea' => 1]);
        }
        $this->get('/')->assertOk()
            ->assertSee('Un paciente a la vez')
            ->assertSee('Te avisamos un día antes');

        HorarioAtencion::query()->update(['capacidad_simultanea' => 2]);
        $this->get('/')->assertOk()
            ->assertDontSee('Un paciente a la vez')
            ->assertSee('Citas con hora exacta');
    }
}
