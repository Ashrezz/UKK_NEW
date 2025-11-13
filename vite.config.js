import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    // 👇 Pindahkan cache keluar dari node_modules
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
})
