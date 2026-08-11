import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        // El frontend corre en el host, no en un contenedor (ver README) —
        // 'localhost' basta. 0.0.0.0 hacía que Laravel escribiera esa
        // dirección literal en `public/hot`, y el navegador la rechaza
        // (ERR_ADDRESS_INVALID: 0.0.0.0 es una dirección de bind, no de
        // destino).
        host: 'localhost',
        port: 5173,
    },
});
