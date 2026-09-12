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
            },
            colors: {
                // Paleta extraída da logo da Maria Casamenteira Assessoria (bordô/vinho).
                brand: {
                    50: '#f7e9ee',
                    100: '#f0d1dd',
                    200: '#e5aec3',
                    300: '#d883a3',
                    400: '#c8517e',
                    500: '#ad3462',
                    600: '#8f2850',
                    700: '#792042',
                    800: '#611a35',
                    900: '#481428',
                },
            },
        },
    },

    plugins: [forms],
};
