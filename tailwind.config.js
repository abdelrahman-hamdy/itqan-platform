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

    // Safelist commonly used gradient combinations to prevent purging
    safelist: [
        // Sidebar margin classes (dynamically applied via JavaScript)
        'md:mr-80',
        'md:mr-20',

        // Education section gradients
        'from-indigo-600',
        'via-blue-600',
        'to-indigo-700',
        'bg-gradient-to-br',
        
        // Business section gradients
        'from-teal-600',
        'via-cyan-600',
        'to-blue-600',
        
        // Common gradient combinations
        'from-slate-800',
        'via-blue-900',
        'to-indigo-900',
        'from-emerald-800',
        'via-green-900',
        'to-teal-900',
        'from-blue-800',
        'via-cyan-900',
        'to-teal-900',
        'from-purple-800',
        'via-violet-900',
        'to-indigo-900',
        'from-cyan-800',
        'via-teal-900',
        'to-emerald-900',
        'from-green-800',
        'via-emerald-900',
        'to-teal-900',
        'from-teal-800',
        'via-cyan-900',
        'to-emerald-900',
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