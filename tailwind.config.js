import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRatio from '@tailwindcss/aspect-ratio';

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
        'md:ml-80',
        'md:ml-20',
        // RTL/LTR variant classes
        'rtl:right-0',
        'ltr:left-0',
        'rtl:left-0',
        'ltr:right-0',
        'rtl:mr-0',
        'rtl:md:mr-80',
        'rtl:md:mr-20',
        'ltr:ml-0',
        'ltr:md:ml-80',
        'ltr:md:ml-20',
        'rtl:border-l',
        'ltr:border-r',
        'rtl:-left-10',
        'ltr:-right-10',
        'rtl:rounded-l-lg',
        'ltr:rounded-r-lg',
        'rtl:border-l-0',
        'ltr:border-r-0',
        'rtl:right-24',
        'ltr:left-24',

        // Filament topbar button colors
        'bg-amber-600',
        'bg-amber-500',
        'hover:bg-amber-500',
        'bg-emerald-600',
        'bg-emerald-500',
        'hover:bg-emerald-500',
        'bg-blue-600',
        'bg-blue-500',
        'hover:bg-blue-500',
        'bg-green-600',
        'bg-green-500',
        'hover:bg-green-500',
        'focus:ring-amber-500',
        'focus:ring-emerald-500',
        'focus:ring-blue-500',
        'focus:ring-green-500',
        'dark:bg-amber-500',
        'dark:bg-emerald-500',
        'dark:bg-blue-500',
        'dark:bg-green-500',
        'dark:hover:bg-amber-400',
        'dark:hover:bg-emerald-400',
        'dark:hover:bg-blue-400',
        'dark:hover:bg-green-400',
        'text-amber-600',
        'text-emerald-600',
        'text-blue-600',
        'text-green-600',

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
                sans: ['Tajawal', ...defaultTheme.fontFamily.sans],
                arabic: ['Tajawal'],
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
        aspectRatio,
        require('tailwindcss-rtl'),
    ],

    // Dark mode support
    darkMode: 'class',
}; 