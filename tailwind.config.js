import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
        './resources/js/**/*.jsx',
        './resources/js/**/*.ts',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                assai: {
                    primary: '#0D47A1',
                    secondary: '#1976D2',
                    accent: '#4CAF50',
                    surface: '#F3F4F6',
                },
                manchester: {
                    red: '#EF4444',
                    orange: '#F97316',
                    yellow: '#EAB308',
                    green: '#22C55E',
                    blue: '#3B82F6',
                }
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
        },
    },
    plugins: [forms],
};
