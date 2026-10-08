import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), 'VITE_');
    const publicPort = Number(process.env.VITE_PORT || env.VITE_PORT || 15430);
    const internalPort = Number(process.env.VITE_INTERNAL_PORT || publicPort);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            port: internalPort,
            strictPort: true,
            origin: `http://127.0.0.1:${publicPort}`,
            hmr: {
                host: '127.0.0.1',
                clientPort: publicPort,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
