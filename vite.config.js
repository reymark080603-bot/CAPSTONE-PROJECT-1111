import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pdf-viewer.css',
                'resources/css/view-only-pdf.css',
                'resources/css/components/book-details.css',
                'resources/css/components/books.css',
                'resources/css/components/borrow-book.css',
                'resources/js/app.js',
                'resources/js/pdf-viewer.js',
                'resources/js/view-only-pdf.js',
                'resources/js/read-book.js',
                'resources/js/components/book-details.js',
                'resources/js/components/borrow-book.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            external: ['pdfjs-dist/build/pdf.worker.js'],
        },
    },
});
