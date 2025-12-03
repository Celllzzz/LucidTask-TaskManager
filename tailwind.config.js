import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Opsi: Gunakan font yang lebih modern seperti 'Inter' atau 'Outfit' jika mau
            },
            colors: {
                // KITA DEFINISIKAN WARNA BRAND LUCIDTASK
                lucid: {
                    50:  '#ecfeff', // Backgrounds yang sangat terang
                    100: '#cffafe',
                    200: '#a5f3fc',
                    300: '#67e8f9',
                    400: '#22d3ee', // Cyan cerah untuk aksen/hover
                    500: '#06b6d4', // Primary brand color (tombol utama)
                    600: '#0891b2',
                    700: '#0e7490',
                    800: '#155e75', // Warna sidebar
                    900: '#164e63', // Teks sangat gelap
                    950: '#083344',
                },
                // Warna sekunder untuk variasi (misal: badge role)
                violet: {
                    50: '#f5f3ff',
                    // ... (gunakan default tailwind colors untuk violet jika tidak didefinisikan sendiri)
                    500: '#8b5cf6',
                }
            }
        },
    },

    plugins: [forms],
};