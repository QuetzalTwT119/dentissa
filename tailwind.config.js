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
                // Tipografía del sistema: aparece igual desde el primer instante,
                // incluso con una conexión lenta o sin Internet.
                sans: [...defaultTheme.fontFamily.sans],
                cursive: ['"Alex Brush"', 'cursive'],
            },
            colors: {
                // Paleta corporativa Melissa López N. — Odontología Integral
                brand: {
                    primary: '#d75078',
                    secondary: '#f2b0a6',
                    50: '#fdf1f4',
                    100: '#fbe1e8',
                    200: '#f6c2d1',
                    300: '#ef9bb5',
                    400: '#e57398',
                    500: '#d75078',
                    600: '#c13866',
                    700: '#a12a54',
                    800: '#832545',
                    900: '#6d213c',
                },
            },
        },
    },

    plugins: [forms],
};
