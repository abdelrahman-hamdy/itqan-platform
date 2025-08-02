<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Itqan Platform'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    @if($academy->logo_url)
        <link rel="icon" type="image/x-icon" href="{{ $academy->logo_url }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- Meta Tags -->
    @yield('meta')

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Academy Custom Styles -->
    <style>
        :root {
            --academy-primary: {{ $academy->brand_color ?? '#0ea5e9' }};
            --academy-secondary: {{ $academy->secondary_color ?? '#10b981' }};
        }
        
        .academy-primary {
            color: var(--academy-primary);
        }
        
        .academy-bg-primary {
            background-color: var(--academy-primary);
        }
        
        .academy-border-primary {
            border-color: var(--academy-primary);
        }
        
        .academy-secondary {
            color: var(--academy-secondary);
        }
        
        .academy-bg-secondary {
            background-color: var(--academy-secondary);
        }
        
        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.8s ease-out;
        }
        
        .animate-scale-in {
            animation: scaleIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes scaleIn {
            from { 
                opacity: 0; 
                transform: scale(0.9); 
            }
            to { 
                opacity: 1; 
                transform: scale(1); 
            }
        }
        
        /* Hover effects */
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* RTL Support */
        [dir="rtl"] .text-left {
            text-align: right;
        }
        
        [dir="rtl"] .text-right {
            text-align: left;
        }

        /* Fix for elements with opacity-0 initially */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
        }

        .animate-on-scroll.animate-fade-in {
            opacity: 1;
            transform: translateY(0);
        }

        /* Ensure RTL flex and grid layouts work properly */
        [dir="rtl"] .flex-row {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .justify-start {
            justify-content: flex-end;
        }

        [dir="rtl"] .justify-end {
            justify-content: flex-start;
        }
    </style>

    @stack('styles')
</head>

<body class="font-arabic bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Navigation -->
    @include('academy.partials.navigation')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Scripts -->
    @yield('scripts')
    @stack('scripts')
</body>
</html>