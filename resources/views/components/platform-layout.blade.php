@php
    $platformSettings = \App\Models\PlatformSettings::instance();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="منصة إتقان - تمكين التعليم الإسلامي من خلال التكنولوجيا">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'منصة إتقان - منصة التعليم الإسلامي التقني')</title>

    <!-- Favicon - Uses unified favicon system with academy context -->
    {!! getFaviconLinkTag() !!}

    <!-- Fonts -->
    @include('partials.fonts')

    <!-- Icons (RemixIcon & Flag-icons) are bundled via Vite in app.css -->
    <!-- Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /* Hide Alpine.js elements until loaded */
        [x-cloak] {
            display: none !important;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Modern gradient backgrounds */
        .gradient-bg-1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-bg-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .gradient-bg-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        /* Enhanced animations */
        .animate-float-slow {
            animation: float 8s ease-in-out infinite;
        }
        
        .animate-float-fast {
            animation: float 4s ease-in-out infinite;
        }
        
        /* Modern button effects */
        .btn-glow {
            position: relative;
            overflow: hidden;
        }
        
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-glow:hover::before {
            left: 100%;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900" style="font-family: 'Tajawal', sans-serif;">
    @include('components.platform-header', ['platformSettings' => $platformSettings])

    <main>
        @yield('content')
    </main>

    @include('components.platform-footer', ['platformSettings' => $platformSettings])
</body>

</html>