import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    cacheDir: './.vitecache',

    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layout-redesign.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],

    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
})
