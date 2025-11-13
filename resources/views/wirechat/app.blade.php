<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>المحادثات - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- RemixIcon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @livewireStyles
    @wirechatStyles

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        /* RTL Adjustments for WireChat */
        [dir="rtl"] .wirechat-container {
            direction: rtl;
        }

        [dir="rtl"] .wirechat-messages {
            text-align: right;
        }

        [dir="rtl"] .wirechat-sender {
            flex-direction: row-reverse;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        {{ $slot }}
    </div>

    @livewireScripts
    @wirechatAssets
    @vite(['resources/js/app.js'])

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