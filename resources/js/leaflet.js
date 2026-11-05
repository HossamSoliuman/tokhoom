const LEAFLET_VERSION = '1.9.4';

let leafletLoading = null;

/**
 * Load Leaflet from the CDN the first time a map is shown.
 */
export default () => {
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
