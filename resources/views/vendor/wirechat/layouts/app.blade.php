<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <x-app-head :title="$title ?? 'الرسائل - ' . config('app.name', 'منصة إتقان')" description="نظام الرسائل والمحادثات">
        @livewireStyles
        @wirechatStyles
    </x-app-head>
</head>

<body class="antialiased bg-gray-50" x-data x-cloak>
    <!-- Include appropriate navigation based on user role -->
    @if(auth()->user()->hasRole('student'))
        <x-navigation.app-navigation role="student" />
    @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
        <x-navigation.app-navigation role="teacher" />
    @endif

    <!-- Main Chat Container - Takes Full Viewport Height Minus Navigation -->
    <main class="fixed inset-0 pt-20 bg-gray-50">
        <div class="h-full w-full max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="h-full">
                {{ $slot }}
            </div>
        </div>
    </main>

    @livewireScripts
    @wirechatAssets

    {{-- Custom Confirmation Modal --}}
    <x-wirechat::confirmation-modal />

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
