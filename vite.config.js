import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layout-redesign.css',
                'resources/js/app.js'
            ],
            refresh: true,
            buildDirectory: 'build', // WAJIB DAN BENAR
        }),
    ],

    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
    },
})

