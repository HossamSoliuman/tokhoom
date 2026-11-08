import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import toasts from './toasts';
import copy from './copy';
import loadLeaflet from './leaflet';
import claim from './claim';

Alpine.store('toasts', toasts);
Alpine.store('claim', claim);

window.addEventListener('site-address', (event) => Alpine.store('claim').update(event.detail));

Alpine.magic('copy', () => copy(Alpine));

/**
 * $track(type, listing): count a contact or share click without holding up the link.
 */
const track = (type, listing = null) => {
    const data = new FormData();
    data.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
    data.append('type', type);

    if (listing) {
        data.append('listing', listing);
    }

    navigator.sendBeacon?.('/events', data);
};

Alpine.magic('track', () => track);

/**
 * $share(url, title, listing): the phone's share sheet, or copy the link.
 */
Alpine.magic('share', () => async (url, title, listing = null) => {
    track('share', listing);

    if (navigator.share) {
        try {
            await navigator.share({ title, url });
        } catch (error) {
            // The visitor closed the share sheet.
        }

        return;
    }

    copy(Alpine)(url, 'تم نسخ رابط العقار');
});

/**
 * Listing photos: the swipeable strip on phones and the full-screen viewer.
 */
Alpine.data('gallery', (photos) => ({
    photos,
    index: 0,
    open: false,

    get counter() {
        return `${this.index + 1} / ${this.photos.length}`;
    },

    show(index) {
        if (!this.photos.length) {
            return;
        }

        this.index = index;
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },

    close() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    },

    next() {
        this.index = (this.index + 1) % this.photos.length;
    },

    prev() {
        this.index = (this.index - 1 + this.photos.length) % this.photos.length;
    },

    slid(strip) {
        this.index = Math.round(Math.abs(strip.scrollLeft) / strip.clientWidth);
    },
}));

/**
 * Map showing a circle around the listing instead of its exact spot.
 */
Alpine.data('approximateMap', ({ latitude, longitude, color }) => ({
    failed: false,
    map: null,

    async init() {
        try {
            const L = await loadLeaflet();

            this.map = L.map(this.$refs.map, {
                center: [latitude, longitude],
                zoom: 14,
                zoomControl: false,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 17,
                attribution: '© OpenStreetMap',
            }).addTo(this.map);

            L.circle([latitude, longitude], {
                radius: 450,
                color,
                weight: 1.5,
                opacity: 0.55,
                fillColor: color,
                fillOpacity: 0.22,
            }).addTo(this.map);
        } catch (error) {
            this.failed = true;
        }
    },

    destroy() {
        this.map?.remove();
    },
}));

window.addEventListener('toast', (event) => Alpine.store('toasts').push(event.detail));

Livewire.start();
