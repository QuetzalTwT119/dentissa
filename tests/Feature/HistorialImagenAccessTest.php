<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\TipoImagen;
use App\Models\Imagen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HistorialImagenAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_puede_ver_un_adjunto_del_historial_clinico(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('historial/adjunto.jpg', 'imagen de prueba');
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $imagen = $this->crearImagen(TipoImagen::HistorialClinico, 'historial/adjunto.jpg');

        $this->actingAs($admin)
            ->get(route('historial.imagenes.show', $imagen))
            ->assertOk()
            ->assertStreamedContent('imagen de prueba');
    }

    public function test_un_asistente_no_puede_ver_un_adjunto_del_historial_clinico(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $imagen = $this->crearImagen(TipoImagen::HistorialClinico);

        $this->actingAs($asistente)
            ->get(route('historial.imagenes.show', $imagen))
            ->assertForbidden();
    }

    public function test_un_visitante_no_autenticado_no_puede_ver_un_adjunto_clinico(): void
    {
        $imagen = $this->crearImagen(TipoImagen::HistorialClinico);

        $this->get(route('historial.imagenes.show', $imagen))
            ->assertRedirect(route('login'));
    }

    public function test_la_ruta_privada_no_sirve_imagenes_de_otros_modulos(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $imagen = $this->crearImagen(TipoImagen::Galeria);

        $this->actingAs($admin)
            ->get(route('historial.imagenes.show', $imagen))
            ->assertNotFound();
    }

    private function crearImagen(TipoImagen $tipo, string $url = 'historial/ausente.jpg'): Imagen
    {
        return Imagen::create([
            'titulo' => 'Adjunto de prueba',
            'url' => $url,
            'tipo' => $tipo,
        ]);
    }
}
