{{--
    Diálogo de confirmación del panel (reemplaza confirm() y prompt() del navegador).

    Hay UNA sola instancia, en layouts/app. Cualquier formulario la usa declarando:
      data-confirmar="Mensaje"                  (obligatorio)
      data-confirmar-titulo="¿Eliminar…?"       (opcional)
      data-confirmar-boton="Sí, eliminar"       (opcional)
      data-confirmar-tono="peligro|normal"      (opcional; peligro por omisión)
      data-confirmar-motivo="campo"             (opcional: pide un texto y lo copia
      data-confirmar-motivo-etiqueta="…"         al input oculto con ese name)
    La lógica vive en resources/js/app.js (busca "dialogo-confirmar").

    <dialog> nativo: cierra con Esc, mantiene el teclado dentro y lo anuncian
    los lectores de pantalla.
--}}
<dialog id="dialogo-confirmar" data-dialogo-confirmar aria-labelledby="dialogo-confirmar-titulo" aria-describedby="dialogo-confirmar-mensaje"
        class="dialogo-confirmar m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border border-line bg-white p-0 text-ink shadow-lift">
    <form method="dialog" class="p-6">
        <div class="flex items-start gap-4">
            <span data-dialogo-icono class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-rose-700" aria-hidden="true">
                <svg data-icono-peligro class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.3 4.6 3.4 16.6a2 2 0 0 0 1.7 3h13.8a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0z"/></svg>
                <svg data-icono-normal class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 17.25h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="dialogo-confirmar-titulo" data-dialogo-titulo class="font-serif text-title-3 font-medium text-ink">¿Estás seguro?</h2>
                <p id="dialogo-confirmar-mensaje" data-dialogo-mensaje class="mt-1.5 text-sm leading-6 text-ink-soft"></p>
            </div>
        </div>

        <div data-dialogo-motivo class="mt-4 hidden">
            <label for="dialogo-confirmar-motivo" data-dialogo-motivo-etiqueta class="block text-sm font-semibold text-ink">Motivo (opcional)</label>
            <textarea id="dialogo-confirmar-motivo" rows="3" maxlength="500"
                      class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200"></textarea>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" data-dialogo-cancelar
                    class="inline-flex min-h-11 items-center justify-center rounded-full border border-line bg-white px-5 text-sm font-semibold text-ink transition hover:border-peach-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                Cancelar
            </button>
            <button type="button" data-dialogo-aceptar
                    class="inline-flex min-h-11 items-center justify-center rounded-full bg-rose-700 px-5 text-sm font-semibold text-white transition hover:bg-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300">
                Confirmar
            </button>
        </div>
    </form>
</dialog>
