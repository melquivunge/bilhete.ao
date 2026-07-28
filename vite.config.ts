import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';

export default defineConfig({
    // Espelha o "paths" do tsconfig.json. Sem isto, o TypeScript aceitaria
    // `import x from '@/...'` e o Vite falharia a resolvê-lo no build, com um
    // erro que não aponta para a causa.
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        // O Vite corre dentro do container: sem 0.0.0.0 não seria alcançável a
        // partir do browser do host. A porta é publicada apenas em 127.0.0.1.
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});
