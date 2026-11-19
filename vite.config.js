import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/html-workspace.css', 'resources/js/html-workspace.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
    server: {
        host: '127.0.0.1',
        hmr: { host: '127.0.0.1' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
