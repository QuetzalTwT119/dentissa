<div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-3 text-ink">Foto de perfil</h2>
        <p class="mt-1 text-callout text-ink-soft">Personaliza tu cuenta con una imagen JPG, PNG o WebP de hasta 5 MB.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            @csrf
            <label for="foto_perfil" class="sr-only">Seleccionar foto de perfil</label>
            <input id="foto_perfil" name="foto_perfil" type="file" accept="image/jpeg,image/png,image/webp" required class="block min-h-11 w-full max-w-xs rounded-xl border border-field bg-white text-sm text-ink-soft file:mr-3 file:min-h-11 file:border-0 file:bg-brand-50 file:px-4 file:font-semibold file:text-brand-700 hover:file:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-300">
            <button type="submit" class="btn-primary min-h-11">Subir foto</button>
            <x-input-error class="w-full" :messages="$errors->getBag('profilePhoto')->get('foto_perfil')" />
        </form>

        @if ($user->foto_perfil)
            <form method="post" action="{{ route('profile.photo.delete') }}" onsubmit="return confirm('¿Quieres quitar tu foto de perfil?')">
                @csrf
                @method('delete')
                <button type="submit" class="btn-secondary min-h-11">Quitar foto</button>
            </form>
        @endif
    </div>
</div>

@if (session('status') === 'profile-photo-updated')
    <p class="mt-4 text-sm font-medium text-sage-700" role="status">La foto de perfil se actualizó correctamente.</p>
@elseif (session('status') === 'profile-photo-deleted')
    <p class="mt-4 text-sm font-medium text-sage-700" role="status">Se quitó la foto de perfil.</p>
@endif
