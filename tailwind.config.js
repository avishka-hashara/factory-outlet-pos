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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                montserrat: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'brand-orange': {
                    DEFAULT: '#F5A623',
                    light: '#F7B84D',
                    dark: '#D4891A',
                    shine: '#FCCF6E',
                },
                'dark-navy': {
                    DEFAULT: '#1B2A4A',
                    deep: '#000000',
                },
                navy: '#2C3E6B',
            },
        },
    },

    plugins: [forms],
};
