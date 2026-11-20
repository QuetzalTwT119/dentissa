import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Alpine = Alpine;

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // El sitio continúa funcionando aunque el navegador no admita instalación PWA.
        });
    });
}

// Botón "enviando": al enviar un formulario, el botón muestra un indicador,
// se desactiva y (si tiene data-loading-text) cambia su texto. Evita envíos
// dobles y confirma que el sistema recibió el clic.
// - Respeta formularios cancelados (confirm() o @submit.prevent de Alpine).
// - Se omite con data-sin-carga en el <form>.
function restaurarBoton(boton) {
    if (!boton || boton.dataset.loading === undefined) return;
    delete boton.dataset.loading;
    boton.removeAttribute('aria-busy');
    boton.querySelector(':scope > .btn-spinner')?.remove();
    if (boton.dataset.textoOriginal !== undefined) {
        const etiqueta = boton.querySelector(':scope > [data-loading-label]');
        if (etiqueta) etiqueta.textContent = boton.dataset.textoOriginal;
        delete boton.dataset.textoOriginal;
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.hasAttribute('data-sin-carga')) return;

    const boton = event.submitter ?? form.querySelector('button[type="submit"]:not(.sr-only)');
    if (!boton || boton.dataset.loading !== undefined) return;

    boton.dataset.loading = '';
    boton.setAttribute('aria-busy', 'true');

    const spinner = document.createElement('span');
    spinner.className = 'btn-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    boton.prepend(spinner);

    const etiqueta = boton.querySelector(':scope > [data-loading-label]');
    if (etiqueta && boton.dataset.loadingText) {
        boton.dataset.textoOriginal = etiqueta.textContent;
        etiqueta.textContent = boton.dataset.loadingText;
    }

    // Seguridad: si la página no cambia (descarga, error de red), se libera.
    setTimeout(() => restaurarBoton(boton), 12000);
});

// Al volver con el botón "Atrás", el navegador puede restaurar la página
// tal como quedó: liberamos los botones que seguían "enviando".
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('[data-loading]').forEach(restaurarBoton);
    }
});

window.cargarHorariosDisponibles = async function (tratamientoId, fecha, excludeCitaId) {
    if (!tratamientoId || !fecha) {
        return [];
    }

    const params = new URLSearchParams({
        tratamiento_id: tratamientoId,
        fecha,
    });

    if (excludeCitaId) {
        params.set('exclude_cita_id', excludeCitaId);
    }

    const response = await fetch(`/disponibilidad?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        return [];
    }

    const data = await response.json();

    return data.horarios ?? [];
};

// Resumen de un mes para el calendario: { mes, duracion_minutos, dias: { 'YYYY-MM-DD': { horarios, estado } } }.
// Devuelve null si falla, para que el calendario muestre "reintentar".
window.cargarDisponibilidadMes = async function (tratamientoId, mes, excludeCitaId) {
    const params = new URLSearchParams({ tratamiento_id: tratamientoId, mes });

    if (excludeCitaId) {
        params.set('exclude_cita_id', excludeCitaId);
    }

    try {
        const response = await fetch(`/disponibilidad/mes?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        return response.ok ? await response.json() : null;
    } catch {
        return null;
    }
};

const aMinutos = (hora) => {
    const [h, m] = hora.split(':').map(Number);
    return h * 60 + m;
};
const aHora = (minutos) => `${String(Math.floor(minutos / 60)).padStart(2, '0')}:${String(minutos % 60).padStart(2, '0')}`;
const conMayuscula = (texto) => texto.charAt(0).toUpperCase() + texto.slice(1);
// Fechas 'YYYY-MM-DD' a mediodía local: así la zona horaria nunca cambia el día.
const fechaLocal = (fecha) => {
    const [anio, mes, dia] = fecha.split('-').map(Number);
    return new Date(anio, mes - 1, dia, 12);
};
const sumarMeses = (mes, n) => {
    const [anio, m] = mes.split('-').map(Number);
    const d = new Date(anio, m - 1 + n, 1, 12);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
};

/*
 * Calendario de disponibilidad (partials/disponibilidad-picker).
 *
 * Vive DENTRO del x-data del formulario y comparte sus datos: idTratamiento,
 * fecha, hora, horarios, loadingHorarios y cargarHorarios(). Alpine resuelve
 * `this.fecha` en el formulario padre porque este componente no la define.
 * Aquí solo vive lo propio del calendario: el mes visible y su resumen.
 */
Alpine.data('calendarioDisponibilidad', ({ hoy, excluirCitaId = null, mesesAdelante = 12 }) => ({
    hoy,
    mes: '',
    dias: {},
    duracion: 0,
    cargandoMes: false,
    errorMes: false,
    pedido: 0,
    nombresDias: ['L', 'M', 'M', 'J', 'V', 'S', 'D'],

    init() {
        this.mes = (this.fecha || hoy).slice(0, 7);
        this.cargarMes();
        this.$watch('idTratamiento', () => this.cargarMes());
    },

    async cargarMes() {
        // Si el paciente cambia de mes rápido, solo vale la última respuesta.
        const pedido = ++this.pedido;

        if (!this.idTratamiento) {
            this.dias = {};
            this.cargandoMes = false;
            return;
        }

        this.cargandoMes = true;
        this.errorMes = false;
        const datos = await window.cargarDisponibilidadMes(this.idTratamiento, this.mes, excluirCitaId);

        if (pedido !== this.pedido) return;

        this.cargandoMes = false;
        this.errorMes = !datos;
        this.dias = datos?.dias ?? {};
        this.duracion = datos?.duracion_minutos ?? 0;
    },

    get etiquetaMes() {
        return conMayuscula(fechaLocal(`${this.mes}-01`).toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }));
    },

    get puedeRetroceder() {
        return this.mes > hoy.slice(0, 7);
    },

    get puedeAvanzar() {
        return this.mes < sumarMeses(hoy.slice(0, 7), mesesAdelante);
    },

    cambiarMes(paso) {
        if ((paso < 0 && !this.puedeRetroceder) || (paso > 0 && !this.puedeAvanzar)) return;
        this.mes = sumarMeses(this.mes, paso);
        this.cargarMes();
    },

    // Celdas del mes, con huecos al inicio para que la semana empiece en lunes.
    get celdas() {
        const primero = fechaLocal(`${this.mes}-01`);
        const huecos = (primero.getDay() + 6) % 7;
        const total = new Date(primero.getFullYear(), primero.getMonth() + 1, 0).getDate();
        const celdas = Array.from({ length: huecos }, (_, i) => ({ clave: `hueco-${i}`, hueco: true }));

        for (let dia = 1; dia <= total; dia++) {
            const fecha = `${this.mes}-${String(dia).padStart(2, '0')}`;
            const info = this.dias[fecha] ?? { horarios: 0, estado: 'cargando' };
            celdas.push({ clave: fecha, hueco: false, fecha, numero: dia, ...info });
        }

        return celdas;
    },

    etiquetaDia(celda) {
        const estados = {
            disponible: `${celda.horarios} ${celda.horarios === 1 ? 'horario disponible' : 'horarios disponibles'}`,
            sin_horarios: 'sin horarios disponibles',
            cerrado: 'consultorio cerrado',
            pasado: 'fecha pasada',
            cargando: 'consultando disponibilidad',
        };
        const fecha = fechaLocal(celda.fecha).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });

        return `${conMayuscula(fecha)}, ${estados[celda.estado] ?? ''}`;
    },

    elegirDia(fecha) {
        if (this.fecha === fecha) return;
        this.fecha = fecha;
        this.cargarHorarios();
    },

    // Flechas dentro del calendario: ←/→ día anterior/siguiente con horarios, ↑/↓ misma columna.
    moverFoco(evento) {
        const pasos = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
        const paso = pasos[evento.key];
        if (!paso || !evento.target.dataset.fecha) return;

        evento.preventDefault();
        const botones = [...this.$refs.dias.querySelectorAll('[data-fecha]')];
        let indice = botones.indexOf(evento.target) + paso;

        while (botones[indice]?.disabled) {
            indice += paso;
        }

        botones[indice]?.focus();
    },

    get fechaElegidaLegible() {
        return this.fecha
            ? conMayuscula(fechaLocal(this.fecha).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }))
            : '';
    },

    finHora(hora) {
        return this.duracion ? aHora(aMinutos(hora) + this.duracion) : '';
    },
}));

Alpine.data('ofertaCarousel', (total) => ({
    activeIndex: 0,
    isPaused: false,
    autoplayId: null,
    scrollTimeoutId: null,
    scrollProgramatico: false,
    total,

    init() {
        if (this.total <= 1) {
            return;
        }

        const prefiereMenosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!prefiereMenosMovimiento) {
            this.autoplayId = setInterval(() => {
                if (!this.isPaused) {
                    this.goTo(this.activeIndex + 1);
                }
            }, 5000);
        }
    },

    destroy() {
        clearInterval(this.autoplayId);
        clearTimeout(this.scrollTimeoutId);
    },

    scrollToIndex(index) {
        const track = this.$refs.track;
        const card = track?.children[index];

        if (!track || !card) {
            return;
        }

        this.scrollProgramatico = true;
        clearTimeout(this.scrollTimeoutId);
        this.scrollTimeoutId = setTimeout(() => {
            this.scrollProgramatico = false;
        }, 600);

        track.scrollTo({
            left: card.offsetLeft - track.offsetLeft,
            behavior: 'smooth',
        });
    },

    goTo(index) {
        this.activeIndex = ((index % this.total) + this.total) % this.total;
        this.scrollToIndex(this.activeIndex);
    },

    next() {
        this.goTo(this.activeIndex + 1);
    },

    prev() {
        this.goTo(this.activeIndex - 1);
    },

    handleScroll() {
        if (this.scrollProgramatico) {
            return;
        }

        const track = this.$refs.track;
        if (!track) {
            return;
        }

        let closestIndex = 0;
        let closestDistance = Infinity;

        Array.from(track.children).forEach((child, index) => {
            const distance = Math.abs(child.offsetLeft - track.offsetLeft - track.scrollLeft);

            if (distance < closestDistance) {
                closestDistance = distance;
                closestIndex = index;
            }
        });

        this.activeIndex = closestIndex;
    },
}));

/*
 * Paso "Datos" de la agenda pública (citas-publicas/create). Valida en el
 * navegador con las mismas reglas que CitaPublicaController@store para avisar
 * antes de enviar; el servidor vuelve a validar siempre.
 *
 * - El error de un campo aparece al salir de él (no mientras se escribe) y se
 *   quita en cuanto el dato queda bien.
 * - El teléfono se muestra como "55 1234 5678"; el servidor quita los espacios.
 */
const formatoTelefono = (valor) => {
    const d = String(valor ?? '').replace(/\D/g, '').slice(0, 10);
    return [d.slice(0, 2), d.slice(2, 6), d.slice(6)].filter(Boolean).join(' ');
};

Alpine.data('datosPaciente', (inicial = {}) => ({
    valores: {
        nombre: inicial.nombre ?? '',
        apellido: inicial.apellido ?? '',
        telefono: formatoTelefono(inicial.telefono),
        email: inicial.email ?? '',
        aviso: Boolean(inicial.aviso),
    },
    errores: { ...(inicial.errores ?? {}) },
    intentado: Object.keys(inicial.errores ?? {}).length > 0,

    reglas: {
        nombre: (v) => (!v.trim() ? 'Escribe tu nombre.' : ''),
        apellido: (v) => (!v.trim() ? 'Escribe tu apellido.' : ''),
        telefono: (v) => {
            const digitos = v.replace(/\D/g, '').length;
            if (!digitos) return 'Escribe tu teléfono.';
            return digitos === 10 ? '' : `Faltan ${10 - digitos} ${10 - digitos === 1 ? 'dígito' : 'dígitos'}: ingresa exactamente 10 dígitos numéricos.`;
        },
        email: (v) => {
            if (!v.trim()) return 'Escribe tu correo.';
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? '' : 'Revisa el correo: falta algo como "@gmail.com".';
        },
        aviso: (v) => (v ? '' : 'Para agendar necesitamos que aceptes el aviso de privacidad.'),
    },

    validar(campo) {
        this.errores[campo] = this.reglas[campo](this.valores[campo]);
        return !this.errores[campo];
    },

    alSalir(campo) {
        if (String(this.valores[campo]).trim() !== '') this.validar(campo);
    },

    alEscribir(campo) {
        if (this.errores[campo]) this.validar(campo);
    },

    escribirTelefono(evento) {
        this.valores.telefono = formatoTelefono(evento.target.value);
        evento.target.value = this.valores.telefono;
        this.alEscribir('telefono');
    },

    // Palomita verde: el dato ya cumple la regla.
    bien(campo) {
        return String(this.valores[campo]).trim() !== '' && !this.reglas[campo](this.valores[campo]);
    },

    get cuentaErrores() {
        return Object.values(this.errores).filter(Boolean).length;
    },

    enviar(evento) {
        const malos = ['nombre', 'apellido', 'telefono', 'email', 'aviso'].filter((campo) => !this.validar(campo));
        if (!malos.length) return;

        evento.preventDefault();
        this.intentado = true;
        this.$nextTick(() => this.$el.querySelector(`[name="${malos[0] === 'aviso' ? 'acepta_aviso' : malos[0]}"]`)?.focus());
    },
}));

/*
 * Tablas del panel (components/admin/table.blade.php), modo "Mixta".
 *
 * tablaApilable: en celular cada fila se apila como tarjeta. Copia el título
 * de cada columna de <thead> a sus celdas (data-label, que el CSS muestra con
 * ::before) y marca la columna "Acciones". Al apilarse, display:block quita la
 * semántica de tabla en algunos lectores de pantalla; los roles la conservan.
 */
Alpine.data('tablaApilable', () => ({
    init() {
        const tabla = this.$el.querySelector('table');
        const titulos = [...tabla.querySelectorAll('thead th')].map((th) => th.textContent.trim());

        tabla.setAttribute('role', 'table');
        tabla.querySelectorAll('tr').forEach((fila) => fila.setAttribute('role', 'row'));
        tabla.querySelectorAll('thead th').forEach((th) => th.setAttribute('role', 'columnheader'));
        tabla.querySelectorAll('tbody tr').forEach((fila) => {
            let columna = 0;
            [...fila.children].forEach((celda) => {
                celda.setAttribute('role', 'cell');
                const titulo = titulos[columna] ?? '';
                if (celda.colSpan === 1 && titulo) celda.dataset.label = titulo;
                if (titulo === 'Acciones') celda.dataset.acciones = '';
                columna += celda.colSpan || 1;
            });
        });
    },
}));

/*
 * tablaLarga: la tabla se desplaza de lado solo si no cabe. "desborda" muestra
 * la pista "Desliza para ver más columnas"; "desplazada" da sombra a la
 * primera columna (fija) cuando ya se movió.
 */
Alpine.data('tablaLarga', () => ({
    desborda: false,
    desplazada: false,

    init() {
        this.medir();
        if ('ResizeObserver' in window) {
            new ResizeObserver(() => this.medir()).observe(this.$refs.desplazable);
        }
    },

    medir() {
        const caja = this.$refs.desplazable;
        this.desborda = caja.scrollWidth > caja.clientWidth + 1;
        this.desplazada = caja.scrollLeft > 0;
    },
}));

/*
 * Carrusel de especialidades de la portada ("Fila"). Lo mueve el paciente:
 * nunca avanza solo.
 * - Cada flecha avanza UNA tarjeta, así cada clic mueve exactamente un punto
 *   (con 7 tarjetas y 3 visibles, avanzar por páginas saltaba puntos).
 * - Da la vuelta: al final, › regresa a la primera; al inicio, ‹ va al final.
 * - Los puntos son una posición por tarjeta que puede quedar a la izquierda.
 */
Alpine.data('carruselServicios', () => ({
    total: 0,
    porVista: 1,
    actual: 0,
    temporizador: null,

    init() {
        this.total = this.$refs.pista.children.length;
        this.medir();
        new ResizeObserver(() => this.medir()).observe(this.$refs.pista);
    },

    get posiciones() {
        return Math.max(1, this.total - this.porVista + 1);
    },

    ancho() {
        const [a, b] = this.$refs.pista.children;
        return b ? b.offsetLeft - a.offsetLeft : this.$refs.pista.clientWidth;
    },

    medir() {
        const pista = this.$refs.pista;
        this.porVista = Math.max(1, Math.round(pista.clientWidth / this.ancho()));
        this.actual = Math.min(Math.round(pista.scrollLeft / this.ancho()), this.posiciones - 1);
    },

    // El desplazamiento suave dispara muchos eventos; se mide al terminar.
    alDesplazar() {
        clearTimeout(this.temporizador);
        this.temporizador = setTimeout(() => this.medir(), 80);
    },

    ir(indice) {
        const destino = (indice + this.posiciones) % this.posiciones;
        this.$refs.pista.scrollTo({ left: destino * this.ancho() });
        this.actual = destino;
    },

    siguiente() {
        this.ir(this.actual + 1);
    },

    anterior() {
        this.ir(this.actual - 1);
    },
}));

Alpine.start();

window.Pusher = Pusher;

const notificationsBody = document.body;
const updatesUrl = notificationsBody.dataset.notificationsUpdatesUrl;

if (updatesUrl) {
    let lastNotificationId = Number(notificationsBody.dataset.notificationsCursor || 0);
    let pollingNotifications = false;
    const soundPreferenceKey = `dentissa:notifications:sound:${notificationsBody.dataset.realtimeUserId}`;
    let notificationSoundEnabled = localStorage.getItem(soundPreferenceKey) === 'enabled';
    let notificationAudioContext;

    const getNotificationAudioContext = () => {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return null;
        }

        try {
            notificationAudioContext ||= new AudioContextClass();
            if (notificationAudioContext.state === 'suspended') {
                notificationAudioContext.resume().catch(() => {});
            }

            return notificationAudioContext;
        } catch (error) {
            return null;
        }
    };

    const updateSoundToggle = () => {
        document.querySelectorAll('[data-notification-sound-toggle]').forEach((button) => {
            const label = button.querySelector('[data-notification-sound-label]');
            const stateLabel = notificationSoundEnabled
                ? (button.dataset.labelOn || 'Silenciar')
                : (button.dataset.labelOff || 'Activar sonido');

            button.setAttribute('aria-pressed', notificationSoundEnabled ? 'true' : 'false');
            button.setAttribute('aria-label', notificationSoundEnabled ? 'Silenciar sonido de notificaciones' : 'Activar sonido de notificaciones');
            button.setAttribute('title', notificationSoundEnabled ? 'Silenciar sonido de notificaciones' : 'Activar sonido de notificaciones');
            button.classList.toggle('text-brand-primary', notificationSoundEnabled);

            if (label) {
                label.textContent = stateLabel;
            }
        });
    };

    const playNotificationSound = () => {
        if (!notificationSoundEnabled) {
            return;
        }

        try {
            const audioContext = getNotificationAudioContext();
            if (!audioContext) {
                return;
            }

            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const startsAt = audioContext.currentTime;

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, startsAt);
            oscillator.frequency.setValueAtTime(660, startsAt + 0.12);
            gain.gain.setValueAtTime(0.0001, startsAt);
            gain.gain.exponentialRampToValueAtTime(0.16, startsAt + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, startsAt + 0.28);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(startsAt);
            oscillator.stop(startsAt + 0.3);
        } catch (error) {
            // Browser audio restrictions should never interrupt notification delivery.
        }
    };

    document.querySelectorAll('[data-notification-sound-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            notificationSoundEnabled = !notificationSoundEnabled;
            localStorage.setItem(soundPreferenceKey, notificationSoundEnabled ? 'enabled' : 'muted');
            updateSoundToggle();

            if (notificationSoundEnabled) {
                playNotificationSound();
            }
        });
    });

    updateSoundToggle();

    // A prior opt-in can be unlocked by the user's next normal interaction after reload.
    document.addEventListener('pointerdown', () => {
        if (notificationSoundEnabled) {
            getNotificationAudioContext();
        }
    }, { once: true });

    const updateNotificationBadges = (count) => {
        document.querySelectorAll('[data-notification-count-badge]').forEach((badge) => {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count < 1;
            badge.classList.toggle('hidden', count < 1);
        });
        document.querySelectorAll('[data-notification-count-summary]').forEach((summary) => {
            summary.textContent = String(count);
        });
    };

    // El servidor manda el aviso ya armado (notificaciones/partials/aviso-panel):
    // así el avatar, la insignia y los botones son idénticos a los de la carga inicial.
    const fragmentoAviso = (html) => {
        const plantilla = document.createElement('template');
        plantilla.innerHTML = String(html || '').trim();
        return plantilla.content.firstElementChild;
    };

    const actualizarPorAtender = (total) => {
        const lista = document.querySelector('[data-notification-tasks-list]');
        const cuenta = document.querySelector('[data-notification-tasks-count]');
        const vacio = document.querySelector('[data-notification-tasks-empty]');
        const pendientes = total ?? lista?.children.length ?? 0;

        if (cuenta) {
            cuenta.textContent = pendientes ? `${pendientes} ${pendientes === 1 ? 'pendiente' : 'pendientes'}` : '';
        }
        vacio?.classList.toggle('hidden', pendientes > 0);
    };

    const updateNotificationPreview = (notification) => {
        const notificationId = Number(notification.id || 0);
        const item = fragmentoAviso(notification.html);

        if (!item || !notificationId || document.querySelector(`[data-notification-preview="${notificationId}"]`)) {
            return;
        }

        const list = document.querySelector(notification.por_atender ? '[data-notification-tasks-list]' : '[data-notification-preview-list]');
        if (!list) {
            return;
        }

        list.querySelector('[data-notification-preview-empty]')?.remove();
        list.prepend(item);

        while (list.children.length > 5) {
            list.lastElementChild.remove();
        }

        if (notification.por_atender) {
            actualizarPorAtender();
        }
    };

    const marcarNotificacionLeida = async (url) => {
        if (!url) {
            return null;
        }

        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        return response.ok ? response.json() : null;
    };

    document.querySelectorAll('[data-notification-preview-trigger]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = notificationsBody.dataset.notificationsMarkSeenUrl;
            if (!url) {
                return;
            }

            try {
                const data = await marcarNotificacionLeida(url);
                if (data?.no_vistas !== undefined) {
                    updateNotificationBadges(Number(data.no_vistas));
                }
            } catch (error) {
                // La campana sigue abriendo la bandeja aunque falle temporalmente la red.
            }
        });
    });

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('[data-notification-read-url]');
        if (!link || link.dataset.notificationReadInProgress === 'true') {
            return;
        }

        event.preventDefault();
        link.dataset.notificationReadInProgress = 'true';

        try {
            const data = await marcarNotificacionLeida(link.dataset.notificationReadUrl);
            if (data?.no_vistas !== undefined) {
                updateNotificationBadges(Number(data.no_vistas));
            }
        } catch (error) {
            // La navegación continúa aunque la red impida actualizar el estado visual.
        } finally {
            window.location.assign(link.href);
        }
    });

    // "Ya lo atendí": el aviso pasa de "Por atender" a informativas sin recargar.
    document.addEventListener('click', async (event) => {
        const boton = event.target.closest('[data-notification-attend-url]');
        if (!boton || boton.disabled) {
            return;
        }

        boton.disabled = true;

        try {
            const response = await fetch(boton.dataset.notificationAttendUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const data = response.ok ? await response.json() : null;

            if (!data) {
                boton.disabled = false;
                return;
            }

            const tarjeta = boton.closest('[data-notification-preview]');
            const informativas = document.querySelector('[data-notification-preview-list]');
            const nuevo = fragmentoAviso(data.html);

            tarjeta?.remove();
            if (informativas && nuevo) {
                informativas.querySelector('[data-notification-preview-empty]')?.remove();
                informativas.prepend(nuevo);
            }

            actualizarPorAtender(Number(data.por_atender));
            updateNotificationBadges(Number(data.no_vistas));
        } catch (error) {
            // Sin red el aviso se queda en "Por atender"; se puede volver a intentar.
            boton.disabled = false;
        }
    });

    const showNotificationToast = (notification) => {
        let region = document.querySelector('[data-notification-toast-region]');

        if (!region) {
            region = document.createElement('div');
            region.dataset.notificationToastRegion = '';
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('aria-relevant', 'additions');
            region.className = 'fixed right-4 top-[4.75rem] z-50 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3';
            document.body.append(region);
        }

        const toast = document.createElement('a');
        toast.href = notification.url || '/notificaciones';
        toast.className = 'block rounded-2xl border border-line bg-white p-4 text-left shadow-lift transition-colors hover:border-peach-300';

        const title = document.createElement('span');
        title.className = 'block font-semibold text-ink';
        title.textContent = notification.titulo;

        const message = document.createElement('span');
        message.className = 'mt-1 block text-sm text-ink-soft';
        message.textContent = notification.mensaje;

        toast.append(title, message);
        region.append(toast);

        // Entra desde arriba (el aviso vive arriba a la derecha) y sale por el
        // mismo camino. Con "reducir movimiento" solo hay un fundido breve.
        const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const oculto = reducir ? { opacity: 0 } : { opacity: 0, transform: 'translateY(-8px)' };
        const visible = reducir ? { opacity: 1 } : { opacity: 1, transform: 'none' };
        const curva = 'cubic-bezier(0.23, 1, 0.32, 1)';

        toast.animate?.([oculto, visible], { duration: reducir ? 150 : 220, easing: curva });

        window.setTimeout(() => {
            const salida = toast.animate?.([visible, oculto], { duration: 150, easing: curva, fill: 'forwards' });
            if (salida) {
                salida.onfinish = () => toast.remove();
            } else {
                toast.remove();
            }
        }, 8000);
    };

    const receiveNotification = (notification) => {
        const notificationId = Number(notification.id || 0);
        if (notificationId <= lastNotificationId) {
            return;
        }

        lastNotificationId = notificationId;
        if (notification.no_vistas !== undefined) {
            updateNotificationBadges(Number(notification.no_vistas));
        }
        updateNotificationPreview(notification);
        playNotificationSound();
        showNotificationToast(notification);
    };

    const refreshNotifications = async () => {
        if (pollingNotifications || document.visibilityState === 'hidden') {
            return;
        }

        pollingNotifications = true;

        try {
            const url = new URL(updatesUrl, window.location.origin);
            url.searchParams.set('desde_id', String(lastNotificationId));

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            updateNotificationBadges(Number(data.no_vistas || 0));

            (data.notificaciones || []).forEach((notification) => {
                receiveNotification(notification);
            });

            lastNotificationId = Math.max(lastNotificationId, Number(data.ultima_id || 0));
        } catch (error) {
            // A temporary network failure is harmless; the next poll retries.
        } finally {
            pollingNotifications = false;
        }
    };

    const { realtimeUserId, realtimeKey, realtimeHost, realtimePort, realtimeScheme } = notificationsBody.dataset;
    if (realtimeUserId && realtimeKey && realtimeHost) {
        try {
            const echo = new Echo({
                broadcaster: 'reverb',
                key: realtimeKey,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                },
                wsHost: realtimeHost,
                wsPort: Number(realtimePort || 8080),
                wssPort: Number(realtimePort || 443),
                forceTLS: realtimeScheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            echo.private(`users.${realtimeUserId}`)
                .listen('.notificacion.creada', receiveNotification);

            echo.connector.pusher.connection.bind('state_change', ({ current }) => {
                if (current === 'connected') {
                    refreshNotifications();
                }
            });
        } catch (error) {
            // If WebSockets are unavailable, the short-poll fallback remains active.
        }
    }

    window.setInterval(refreshNotifications, 3000);
    document.addEventListener('visibilitychange', refreshNotifications);
}
