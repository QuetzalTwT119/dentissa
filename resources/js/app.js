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

    const updateNotificationPreview = (notification) => {
        const list = document.querySelector('[data-notification-preview-list]');
        const notificationId = Number(notification.id || 0);

        if (!list || !notificationId || list.querySelector(`[data-notification-preview="${notificationId}"]`)) {
            return;
        }

        list.querySelector('[data-notification-preview-empty]')?.remove();

        const item = document.createElement('a');
        item.dataset.notificationPreview = String(notificationId);
        item.dataset.notificationReadUrl = notification.leida_url || notificationsBody.dataset.notificationsReadUrl?.replace('__NOTIFICATION_ID__', String(notificationId)) || '';
        item.href = notification.url || '/notificaciones';
        item.className = 'flex gap-3 px-4 py-3.5 transition hover:bg-cream focus:bg-cream focus:outline-none';

        const dot = document.createElement('span');
        dot.dataset.notificationPreviewDot = '';
        dot.className = 'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-600 ring-4 ring-brand-100';
        dot.setAttribute('aria-label', 'Notificación nueva');

        const content = document.createElement('span');
        content.className = 'min-w-0 flex-1';

        const heading = document.createElement('span');
        heading.className = 'flex items-start justify-between gap-3';

        const title = document.createElement('span');
        title.dataset.notificationPreviewTitle = '';
        title.className = 'notification-preview-title text-sm font-semibold text-ink';
        title.textContent = notification.titulo || 'Nueva notificación';

        const time = document.createElement('time');
        time.className = 'shrink-0 text-xs text-muted';
        if (notification.creada) {
            time.dateTime = notification.creada;
        }
        time.textContent = 'Ahora';

        const message = document.createElement('span');
        message.dataset.notificationPreviewMessage = '';
        message.className = 'notification-preview-message mt-1 text-sm leading-5 text-ink-soft';
        message.textContent = notification.mensaje || '';

        heading.append(title, time);
        content.append(heading, message);
        item.append(dot, content);
        list.prepend(item);

        while (list.children.length > 5) {
            list.lastElementChild.remove();
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
