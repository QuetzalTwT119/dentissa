{{--
    Botón flotante de WhatsApp (24/11/2026), como en los sitios de referencia:
    siempre a la vista, abre el chat con un mensaje ya escrito. Va encima de la
    burbuja del asistente (abajo a la derecha) para no taparla.
    26/11/2026: en celular se esconde al bajar y vuelve al subir, igual que el
    asistente, para no tapar preguntas frecuentes ni opiniones.
--}}
<a href="https://wa.me/{{ preg_replace('/\D/', '', $contacto['whatsapp']) }}?text={{ rawurlencode('Hola, quisiera información para agendar una cita en el consultorio.') }}"
   target="_blank" rel="noopener noreferrer" data-whatsapp-flotante
   aria-label="Escribir por WhatsApp (se abre en otra pestaña)"
   x-data="{ oculto: false, ultimoY: 0 }"
   @scroll.window.passive="
        const y = window.scrollY;
        if (window.innerWidth >= 640) { oculto = false; ultimoY = y; return; }
        if (Math.abs(y - ultimoY) < 8) return;
        oculto = y > ultimoY && y > 80;
        ultimoY = y;"
   @focus="oculto = false"
   :data-oculto="oculto.toString()"
   :class="oculto ? 'translate-y-24 opacity-0 pointer-events-none' : ''"
   class="fixed bottom-24 right-4 z-40 mb-[env(safe-area-inset-bottom)] inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lift transition-[transform,opacity] duration-200 ease-out hover:scale-105 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 sm:right-6">
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.9-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.89 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41"/></svg>
</a>
