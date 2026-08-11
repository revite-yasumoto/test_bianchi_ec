import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                // モックのデザインシステム（zip内 _ds/.../site.css）の3書体
                bunny('Zen Kaku Gothic New', {
                    weights: [400, 500, 700, 900],
                }),
                bunny('Schibsted Grotesk', {
                    weights: [400, 700, 800],
                }),
                bunny('Space Mono', {
                    weights: [400, 700],
                }),
            ],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        // 既定の localhost ではコンテナ内の 127.0.0.1 にしか listen せず、
        // ホストのブラウザからポートフォワード経由で接続できない
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
