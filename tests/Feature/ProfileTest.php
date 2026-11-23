<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertDontSee('Desactivar mi cuenta');

        $this->assertFalse(Route::has('profile.destroy'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombre' => 'Test User',
                'apellido' => 'Prueba',
                'telefono' => '5512345678',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->nombre);
        $this->assertSame('Prueba', $user->apellido);
        $this->assertSame('5512345678', $user->telefono);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombre' => 'Test User',
                'apellido' => 'Prueba',
                'telefono' => '5512345678',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_upload_a_private_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);

        $response = $this->actingAs($user)->post('/profile/photo', [
            'foto_perfil' => UploadedFile::fake()->image('avatar.png', 500, 500),
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $user->refresh();

        $this->assertStringStartsWith('profile-photos/', $user->foto_perfil);
        Storage::disk('local')->assertExists($user->foto_perfil);
        $photoResponse = $this->actingAs($user)->get('/profile/photo')->assertOk();
        $this->assertStringContainsString('private', $photoResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $photoResponse->headers->get('Cache-Control'));
    }

    public function test_profile_photo_upload_rejects_non_images_and_oversized_images(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);

        $this->actingAs($user)->post('/profile/photo', [
            'foto_perfil' => UploadedFile::fake()->create('notes.txt', 20, 'text/plain'),
        ])->assertSessionHasErrorsIn('profilePhoto', 'foto_perfil');

        $this->actingAs($user)->post('/profile/photo', [
            'foto_perfil' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ])->assertSessionHasErrorsIn('profilePhoto', 'foto_perfil');

        $this->assertNull($user->fresh()->foto_perfil);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]);
        $path = UploadedFile::fake()->image('avatar.jpg')->store('profile-photos', 'local');
        $user->update(['foto_perfil' => $path]);

        $this->actingAs($user)->delete('/profile/photo')
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'profile-photo-deleted');

        $this->assertNull($user->fresh()->foto_perfil);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_profile_photo_is_available_only_to_the_authenticated_owner(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create([
            'rol' => \App\Enums\RolUsuario::Doctor,
            'foto_perfil' => UploadedFile::fake()->image('avatar.jpg')->store('profile-photos', 'local'),
        ]);

        $this->get('/profile/photo')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['rol' => \App\Enums\RolUsuario::Doctor]))->get('/profile/photo')->assertNotFound();
        $this->actingAs($owner)->get('/profile/photo')->assertOk();
    }
}
