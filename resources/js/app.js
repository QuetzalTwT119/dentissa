import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
