import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * Sistema visual "Boutique cálida" de Dentissa.
 *
 * Los colores neutros se leen de variables CSS (definidas en
 * resources/css/app.css). Así toda la paleta se ajusta desde un solo lugar
 * y Tailwind sigue permitiendo opacidades como `border-line/60`.
 */
const token = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    future: {
        // En pantallas táctiles, `hover:` no debe quedarse "pegado" tras un toque.
        // Tailwind envuelve los estilos hover en @media (hover: hover).
        hoverOnlyWhenSupported: true,
    },

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Texto de interfaz: Figtree, legible en tamaños pequeños.
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Títulos: Fraunces, serif cálida para el tono boutique.
                serif: ['Fraunces', 'Georgia', 'Cambria', 'serif'],
            },
            /*
             * Escala tipográfica (Fase 1). Cada nivel define tamaño, interlineado
             * y espaciado entre letras juntos: los títulos grandes llevan letras
             * más juntas e interlineado corto; los textos chicos, un poco más de
             * aire. Los títulos crecen con la pantalla mediante clamp().
             * Tamaño mínimo de lectura: 12 px (caption).
             */
            fontSize: {
                display: ['clamp(2.5rem, 1.6rem + 3.6vw, 3.75rem)', { lineHeight: '1.05', letterSpacing: '-0.025em' }],
                'title-1': ['clamp(2rem, 1.55rem + 1.8vw, 2.625rem)', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
                'title-2': ['clamp(1.6875rem, 1.45rem + 0.9vw, 2rem)', { lineHeight: '1.15', letterSpacing: '-0.015em' }],
                'title-3': ['clamp(1.25rem, 1.15rem + 0.4vw, 1.4375rem)', { lineHeight: '1.25', letterSpacing: '-0.01em' }],
                headline: ['1.0625rem', { lineHeight: '1.4', letterSpacing: '-0.005em' }],
                // Párrafo introductorio (bajo el título principal), solo en pantallas medianas o más.
                lead: ['1.125rem', { lineHeight: '1.6', letterSpacing: '-0.003em' }],
                body: ['1rem', { lineHeight: '1.65', letterSpacing: '0' }],
                callout: ['0.9375rem', { lineHeight: '1.55', letterSpacing: '0' }],
                footnote: ['0.8125rem', { lineHeight: '1.45', letterSpacing: '0.005em' }],
                caption: ['0.75rem', { lineHeight: '1.35', letterSpacing: '0.01em' }],
            },
            letterSpacing: {
                // Único valor para etiquetas en mayúsculas (eyebrows, cabeceras de tabla).
                label: '0.12em',
            },
            colors: {
                // Neutros semánticos (ver :root en app.css).
                cream: token('cream'),      // fondo de página
                shell: token('shell'),      // relleno sutil (cabeceras de tabla, chips)
                line: token('line'),        // bordes y divisores
                field: token('field'),      // borde de campos de formulario (contraste 3:1)
                ink: token('ink'),          // texto principal
                'ink-soft': token('ink-soft'), // texto secundario
                muted: token('muted'),      // texto de apoyo (cumple 4.5:1 sobre crema)

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
                peach: {
                    50: '#fdf5f2',
                    100: '#fbe9e4',
                    200: '#f7d4cb',
                    300: '#f2b0a6',
                    400: '#e8907f',
                    500: '#d9705d',
                },
                sage: {
                    50: '#eef6f1',
                    100: '#d9ebe1',
                    500: '#4e8d72',
                    600: '#3f7660',
                    700: '#335f4e',
                },
            },
            borderRadius: {
                '4xl': '2rem',
            },
            boxShadow: {
                xs: '0 1px 2px 0 rgb(43 34 36 / 0.04)',
                '2xs': '0 1px 1px 0 rgb(43 34 36 / 0.03)',
                soft: '0 1px 2px rgb(43 34 36 / 0.04), 0 8px 24px -12px rgb(120 60 70 / 0.14)',
                lift: '0 2px 4px rgb(43 34 36 / 0.04), 0 18px 40px -16px rgb(120 60 70 / 0.22)',
                brand: '0 8px 20px -8px rgb(193 56 102 / 0.55)',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.23, 1, 0.32, 1)',
            },
        },
    },

    plugins: [forms],
};
