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
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sa: {
                    primary: '#0a8f7b',
                    'primary-deep': '#056e60',
                    'primary-light': '#e8f5f1',
                    accent: '#2db5ff',
                    'accent-light': '#e5f4ff',
                    ink: '#071b2a',
                    'ink-soft': '#3d5a6f',
                    paper: '#f0f6fb',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
        },
    },

    plugins: [forms],
};
