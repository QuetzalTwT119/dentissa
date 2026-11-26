@php
    $iniciales = strtoupper(mb_substr($user->nombre, 0, 1).mb_substr($user->apellido, 0, 1));
@endphp
<section>
    <header class="flex items-start gap-3.5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
        </span>
        <div>
            <h2 class="text-title-3 text-ink">Información personal</h2>
            <p class="mt-1 text-callout text-ink-soft">Mantén actualizados tus datos de contacto, correo y foto.</p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{--
        Datos y foto en un solo formulario (23/11/2026): la foto se elige a la derecha,
        se ve ANTES de guardar tal como saldrá en el perfil y en el menú, y se guarda
        con el mismo botón "Guardar". "Quitar foto" también espera a Guardar.
    --}}
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar tus datos?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal"
          x-data="{
              tieneFoto: @js((bool) $user->foto_perfil),
              vista: null,
              quitar: false,
              nombre: @js($user->nombre.' '.$user->apellido),
              elegir(evento) {
                  const archivo = evento.target.files[0];
                  if (this.vista) URL.revokeObjectURL(this.vista);
                  this.vista = archivo ? URL.createObjectURL(archivo) : null;
                  if (archivo) this.quitar = false;
              },
              quitarFoto() {
                  if (this.vista) URL.revokeObjectURL(this.vista);
                  this.vista = null;
                  this.$refs.archivo.value = '';
                  this.quitar = true;
              },
              deshacer() { this.quitar = false; },
              get conImagen() { return this.vista || (this.tieneFoto && ! this.quitar); },
          }"
          class="mt-6 grid grid-cols-1 gap-6 lg:mt-4 lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-start" data-perfil-formulario>
        @csrf
        @method('patch')

        {{-- Foto: arriba en celular, a la derecha en PC. --}}
        <div class="order-first rounded-2xl border border-line bg-cream/60 p-4 lg:order-last" data-perfil-foto>
            <p class="text-sm font-semibold text-ink">Foto de perfil <span class="font-normal text-muted">(opcional)</span></p>

            <div class="mt-3 flex items-center gap-4 lg:flex-col lg:items-stretch">
                {{-- Vista previa grande, igual que en el resumen de arriba. --}}
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 via-[#e2698d] to-peach-300 text-2xl font-semibold text-white shadow-soft ring-4 ring-white lg:mx-auto lg:h-32 lg:w-32 lg:text-3xl" data-vista-previa>
                    <img x-show="vista" x-cloak :src="vista" alt="Vista previa de tu nueva foto" class="h-full w-full object-cover">
                    @if ($user->foto_perfil)
                        <img x-show="! vista && ! quitar" src="{{ route('profile.photo.show') }}" alt="Tu foto de perfil actual" class="h-full w-full object-cover">
                    @endif
                    <span x-show="! conImagen" @if ($user->foto_perfil) x-cloak @endif>{{ $iniciales }}</span>
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    {{-- Así se verá en el menú de arriba. --}}
                    <div class="flex items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3" aria-hidden="true">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-caption font-semibold text-brand-700">
                            <img x-show="vista" x-cloak :src="vista" alt="" class="h-full w-full object-cover">
                            @if ($user->foto_perfil)
                                <img x-show="! vista && ! quitar" src="{{ route('profile.photo.show') }}" alt="" class="h-full w-full object-cover">
                            @endif
                            <span x-show="! conImagen" @if ($user->foto_perfil) x-cloak @endif>{{ $iniciales }}</span>
                        </span>
                        <span class="truncate text-xs font-semibold text-ink" x-text="nombre">{{ $user->nombre }} {{ $user->apellido }}</span>
                    </div>
                    <p class="text-caption text-muted">Así se verá en el menú.</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 lg:flex-col">
                <label for="foto_perfil" class="btn-secondary min-h-11 cursor-pointer justify-center focus-within:ring-2 focus-within:ring-brand-300">
                    <span x-text="conImagen ? 'Cambiar foto' : 'Elegir foto'">{{ $user->foto_perfil ? 'Cambiar foto' : 'Elegir foto' }}</span>
                    <input id="foto_perfil" name="foto_perfil" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" x-ref="archivo" @change="elegir($event)">
                </label>
                @if ($user->foto_perfil)
                    <button type="button" x-show="! quitar && ! vista" @click="quitarFoto()" class="inline-flex min-h-11 items-center justify-center rounded-full px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300" data-quitar-foto>
                        Quitar foto
                    </button>
                @endif
                <button type="button" x-show="quitar" x-cloak @click="deshacer()" class="inline-flex min-h-11 items-center justify-center rounded-full px-4 text-sm font-semibold text-ink-soft transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                    No quitarla
                </button>
                {{-- x-if agrega o quita el campo: así "Guardar" se enciende al marcarlo. --}}
                <template x-if="quitar"><input type="hidden" name="quitar_foto" value="1"></template>
            </div>

            <p class="mt-3 text-caption text-muted" x-show="! vista && ! quitar">JPG, PNG o WebP de hasta 5 MB.</p>
            <p class="mt-3 text-caption font-medium text-brand-700" x-show="vista" x-cloak role="status">Vista previa: se guardará al pulsar «Guardar».</p>
            <p class="mt-3 text-caption font-medium text-rose-700" x-show="quitar" x-cloak role="status">Se quitará al pulsar «Guardar» y se verán tus iniciales.</p>
            <x-input-error class="mt-2" :messages="$errors->get('foto_perfil')" />
        </div>

        <div class="grid grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2 lg:gap-y-4">
            <x-form-obligatorios />
            <div>
                <x-input-label :required="true" for="nombre" value="Nombre" />
                <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $user->nombre)" required autofocus autocomplete="given-name"
                              x-on:input="nombre = $event.target.value + ' ' + document.getElementById('apellido').value" />
                <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
            </div>

            <div>
                <x-input-label :required="true" for="apellido" value="Apellido" />
                <x-text-input id="apellido" name="apellido" type="text" class="mt-1 block w-full" :value="old('apellido', $user->apellido)" required autocomplete="family-name"
                              x-on:input="nombre = document.getElementById('nombre').value + ' ' + $event.target.value" />
                <x-input-error class="mt-2" :messages="$errors->get('apellido')" />
            </div>

            <div>
                <x-input-label :required="true" for="telefono" value="Teléfono" />
                <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $user->telefono)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
            </div>

            <div>
                <x-input-label :required="true" for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-ink">
                            Tu correo electrónico no está verificado.

                            <button form="send-verification" class="underline text-sm text-ink-soft hover:text-ink rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand-400">
                                Haz clic aquí para reenviar el correo de verificación.
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-sage-700">
                                Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Receta en PDF (24/11/2026): la especialidad sale en cada receta. Solo quien ve
                 expedientes. 26/11/2026: la cédula se captura en Configuración → Cédulas profesionales. --}}
            @if ($user->puede('pacientes'))
                <div>
                    <x-input-label for="especialidad" value="Especialidad (opcional)" />
                    <x-text-input id="especialidad" name="especialidad" type="text" maxlength="100" class="mt-1 block w-full"
                                  :value="old('especialidad', $user->especialidad)" placeholder="Cirujana dentista" autocomplete="off" />
                    <x-input-error class="mt-2" :messages="$errors->get('especialidad')" />
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                <x-primary-button>Guardar</x-primary-button>

                @if (session('status') === 'profile-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-ink-soft"
                    role="status"
                    >Cambios guardados.</p>
                @endif
            </div>
        </div>
    </form>
</section>
