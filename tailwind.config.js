import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Tajawal', 'Cairo', 'Amiri', ...defaultTheme.fontFamily.sans],
                arabic: ['Tajawal', 'Cairo', 'Amiri'],
            },
            colors: {
                'itqan-primary': '#0ea5e9',
                'itqan-secondary': '#0f172a',
                'itqan-accent': '#10b981',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
            },
        },
    },

    plugins: [
        forms,
        typography,
        require('tailwindcss-rtl'),
    ],

    // Dark mode support
    darkMode: 'class',
}; 