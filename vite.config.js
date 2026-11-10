import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/site.js'],
            refresh: true,
            fonts: [
                google('Almarai', {
                    alias: 'almarai',
                    weights: [300, 400, 700, 800],
                    subsets: ['arabic', 'latin'],
                }),
                google('Space Grotesk', {
                    alias: 'grotesk',
                    weights: [500, 700],
                    subsets: ['latin'],
                    preload: false,
                }),
                google('IBM Plex Mono', {
                    alias: 'plex-mono',
                    weights: [400, 500],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
