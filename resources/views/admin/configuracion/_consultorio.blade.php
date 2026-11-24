{{--
    Bloque Datos del consultorio de la página Configuración (22/11/2026). El
    teléfono y el WhatsApp que ven los pacientes (portada, pie, "¿Prefieres
    llamar?", página del recordatorio y respuesta automática del bot), sin
    depender de las variables de Railway. Solo administradores; pide
    confirmación y queda en Control de actividades. El número del bot no se cambia aquí.
--}}
<section id="consultorio" aria-labelledby="titulo-consultorio" class="scroll-mt-24">
    <h2 id="titulo-consultorio" class="text-title-3 text-ink">Datos del consultorio</h2>
    <p class="mt-1 text-sm text-muted">El teléfono y el WhatsApp que ven los pacientes en la portada y en los mensajes.</p>
    <div class="mt-3">
        <x-admin.card class="p-5 sm:p-8">
            <form method="POST" action="{{ route('admin.consultorio.update') }}" class="formulario-panel space-y-6"
                  data-confirmar="Los pacientes verán el número nuevo de inmediato en la portada y en los mensajes."
                  data-confirmar-titulo="¿Guardar los datos del consultorio?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal"
                  x-data="{ mismo: @js((bool) old('mismo_whatsapp', $mismoWhatsapp)) }">
                @csrf
                @method('put')

                <x-form-obligatorios />

                <div>
                    <x-input-label :required="true" for="telefono" value="Teléfono del consultorio" />
                    <x-text-input id="telefono" name="telefono" type="tel" inputmode="numeric" autocomplete="tel-national" class="mt-1.5 block w-full"
                                  :value="old('telefono', $telefono)" required placeholder="755 113 1833" />
                    <p class="mt-1 text-xs text-muted">Celular de 10 dígitos. Es el que se marca al tocar "¿Prefieres llamar?".</p>
                    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                </div>

                <div>
                    <span class="flex items-center gap-1 text-sm font-semibold text-ink">
                        WhatsApp de contacto
                        <x-ayuda etiqueta="No es el número del bot">
                            Es el WhatsApp donde la doctora atiende a pacientes. Los recordatorios automáticos salen de otro número (el del bot), que se configura en Meta y no cambia aquí.
                        </x-ayuda>
                    </span>
                    <label class="mt-1.5 flex min-h-11 items-center gap-2.5">
                        <input type="hidden" name="mismo_whatsapp" value="0">
                        <x-checkbox name="mismo_whatsapp" value="1" x-model="mismo" :checked="old('mismo_whatsapp', $mismoWhatsapp)" />
                        <span class="text-sm text-ink-soft">Es el mismo número que el teléfono</span>
                    </label>
                    <template x-if="! mismo">
                        <div>
                            <x-text-input id="whatsapp" name="whatsapp" type="tel" inputmode="numeric" class="mt-1.5 block w-full"
                                          :value="old('whatsapp', $mismoWhatsapp ? '' : $whatsapp)" required placeholder="755 113 1833" aria-label="Número de WhatsApp de contacto" />
                        </div>
                    </template>
                    <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="correo" value="Correo del consultorio" />
                    <x-text-input id="correo" name="correo" type="email" autocomplete="email" class="mt-1.5 block w-full" :value="old('correo', $correo)" placeholder="consultorio@correo.com" />
                    <x-input-error :messages="$errors->get('correo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="direccion" value="Dirección" />
                    <x-text-input id="direccion" name="direccion" class="mt-1.5 block w-full" :value="old('direccion', $direccion)" maxlength="200" placeholder="Calle, número, colonia y ciudad" />
                    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                </div>

                {{-- 24/11/2026: textos del bloque "La clínica" de la portada. --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="mision" value="Misión (portada)" />
                        <x-textarea id="mision" name="mision" rows="3" maxlength="400" class="mt-1.5">{{ old('mision', $mision) }}</x-textarea>
                        <x-input-error :messages="$errors->get('mision')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="vision" value="Visión (portada)" />
                        <x-textarea id="vision" name="vision" rows="3" maxlength="400" class="mt-1.5">{{ old('vision', $vision) }}</x-textarea>
                        <x-input-error :messages="$errors->get('vision')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end border-t border-line pt-4">
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>
        </x-admin.card>
    </div>
</section>
