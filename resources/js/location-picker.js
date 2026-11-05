const LEAFLET_VERSION = '1.9.4';

let leafletLoading = null;

/**
 * Load Leaflet from the CDN the first time a map is shown.
 */
const loadLeaflet = () => {
    if (window.L) {
        return Promise.resolve(window.L);
    }

    leafletLoading ??= new Promise((resolve, reject) => {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = `https://unpkg.com/leaflet@${LEAFLET_VERSION}/dist/leaflet.css`;
        document.head.appendChild(css);

        const script = document.createElement('script');
        script.src = `https://unpkg.com/leaflet@${LEAFLET_VERSION}/dist/leaflet.js`;
        script.onload = () => resolve(window.L);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return leafletLoading;
};

/**
 * Map with a fixed centre pin: the agent pans the map under the pin and the
 * centre is written back to the Livewire latitude and longitude properties.
 */
export default ({ latitude, longitude }) => ({
    ready: false,
    failed: false,
    map: null,

    async init() {
        try {
            const L = await loadLeaflet();

            this.map = L.map(this.$refs.map, {
                center: [latitude ?? 24.7136, longitude ?? 46.6753],
                zoom: latitude ? 15 : 11,
                zoomControl: false,
                attributionControl: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.map);

            this.map.on('moveend', () => {
                const centre = this.map.getCenter();

                this.$wire.latitude = Number(centre.lat.toFixed(7));
                this.$wire.longitude = Number(centre.lng.toFixed(7));
            });

            this.ready = true;
        } catch (error) {
            this.failed = true;
        }
    },

    destroy() {
        this.map?.remove();
    },
});
