<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'الرسائل - ' . config('app.name', 'منصة إتقان') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/itqan-logo.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @wirechatStyles
</head>

<body class="font-arabic antialiased bg-gray-50" x-data x-cloak>
    <!-- Include appropriate navigation based on user role -->
    @if(auth()->user()->hasRole('student'))
        <x-navigation.student-nav />
    @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
        <x-navigation.teacher-nav />
    @endif

    <!-- Main Chat Container with Top Padding for Fixed Navigation -->
    <main class="pt-20 h-screen overflow-hidden bg-gray-50">
        <div class="h-[calc(100vh-5rem)] w-full">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
    @wirechatAssets

    {{-- WireChat Debug Script (Local/Development Only) --}}
    @if(config('app.debug'))
        <script src="{{ asset('js/chat-debug.js') }}"></script>
        <script>
            console.log('%c🎯 WireChat Debug Loaded', 'font-size: 14px; color: #8b5cf6; font-weight: bold;');
            console.log('%c💡 Type wirechatDebug.help() for available commands', 'color: #6b7280;');
        </script>
    @endif
</body>
</html>
