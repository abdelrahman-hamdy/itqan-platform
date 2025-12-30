@props([
    'title' => null,
    'subtitle' => '',
    'maxWidth' => 'md', // sm, md, lg, xl
    'academy' => null, // Academy object passed from controller
])

@php

    // PRIMARY COLOR: Use brand_color from academy settings
    $brandColorName = 'sky';
    $primaryColorHex = '#0ea5e9'; // sky-500 default

    if ($academy && $academy->brand_color) {
        $brandColorName = $academy->brand_color->value;
        try {
            $primaryColorHex = $academy->brand_color->getHexValue(500);
        } catch (\Exception $e) {
            // Fallback to default
        }
    }

    // GRADIENT COLORS: Use gradient_palette for gradient buttons only
    $gradientFrom = 'cyan-500';
    $gradientTo = 'blue-600';
    $gradientFromHex = '#06b6d4';
    $gradientToHex = '#2563eb';

    if ($academy && $academy->gradient_palette) {
        $colors = $academy->gradient_palette->getColors();
        $gradientFrom = $colors['from'];
        $gradientTo = $colors['to'];

        // Get hex values for CSS custom properties
        [$fromColor, $fromShade] = explode('-', $colors['from']);
        [$toColor, $toShade] = explode('-', $colors['to']);

        try {
            $gradientFromHex = \App\Enums\TailwindColor::from($fromColor)->getHexValue((int)$fromShade);
            $gradientToHex = \App\Enums\TailwindColor::from($toColor)->getHexValue((int)$toShade);
        } catch (\Exception $e) {
            // Fallback to default colors if conversion fails
        }
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $academy ? $academy->name . ' - ' : '' }}{{ $title ?? __('common.platform_name') }}</title>

    <!-- Vite Assets (Compiled CSS & JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- RemixIcon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

    <!-- Load intl-tel-input BEFORE Alpine.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>

    <script>
        // Store colors for Alpine.js components
        window.academyColors = {
            primary: '{{ $primaryColorHex }}',
            brandColor: '{{ $brandColorName }}',
            gradient: {
                from: '{{ $gradientFrom }}',
                to: '{{ $gradientTo }}',
                fromHex: '{{ $gradientFromHex }}',
                toHex: '{{ $gradientToHex }}'
            }
        };
    </script>

    <style>
        :root {
            --primary-color: {{ $primaryColorHex }};
            --gradient-from: {{ $gradientFromHex }};
            --gradient-to: {{ $gradientToHex }};
            --color-primary-500: {{ $primaryColorHex }};
            --color-secondary-500: {{ $gradientToHex }};
        }

        /* Gradient button styles */
        .btn-gradient {
            background: linear-gradient(to right, var(--gradient-from), var(--gradient-to));
        }

        /* Smooth transitions */
        .transition-smooth {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Input focus glow effect - USE PRIMARY COLOR */
        .input-field:focus {
            box-shadow: 0 0 0 3px {{ $primaryColorHex }}33;
            border-color: {{ $primaryColorHex }};
        }

        /* Loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            inset-inline-start: 20px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to { transform: rotate(360deg); }
        }

        /* Tag input styles */
        .tag {
            animation: tagSlideIn 0.2s ease-out;
        }

        @keyframes tagSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Validation feedback */
        .validation-success {
            animation: validationPulse 0.4s ease;
        }

        @keyframes validationPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Custom button radius */
        .rounded-button {
            border-radius: 8px;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Background Pattern -->
    <div class="fixed inset-0 z-0 opacity-30">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgb(203 213 225 / 40%) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>

    <!-- Main Container -->
    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full @if($maxWidth === 'sm') max-w-md @elseif($maxWidth === 'md') max-w-lg @elseif($maxWidth === 'lg') max-w-2xl @else max-w-4xl @endif">

            <!-- Academy Logo and Title -->
            <div class="text-center mb-8">
                <div class="inline-block mb-4">
                    <x-academy-logo :academy="$academy" size="lg" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ $academy ? $academy->name : __('common.platform_name') }}
                </h1>
                @if($subtitle)
                    <p class="text-gray-600">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>

            <!-- Main Content Card -->
            <div class="auth-card bg-white rounded-2xl shadow-xl p-8">
                {{ $slot }}
            </div>

            <!-- Footer Links -->
            @isset($footer)
                <div class="mt-6 text-center">
                    {{ $footer }}
                </div>
            @endisset

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="{{ route('academy.home', ['subdomain' => optional($academy)->subdomain ?? request()->route('subdomain')]) }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-primary transition-smooth">
                    <i class="ri-arrow-left-line me-2 rtl:rotate-180"></i>
                    {{ __('common.back_to_home') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    @if(session('success'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 left-4 right-4 bg-green-50 border border-green-200 rounded-lg shadow-lg z-50 p-4 flex items-center max-w-md mx-auto">
            <div class="flex-shrink-0">
                <i class="ri-checkbox-circle-fill text-green-500 text-xl"></i>
            </div>
            <div class="me-3 flex-1">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
            <button @click="show = false" class="flex-shrink-0 me-3">
                <i class="ri-close-line text-green-500"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 left-4 right-4 bg-red-50 border border-red-200 rounded-lg shadow-lg z-50 p-4 flex items-center max-w-md mx-auto">
            <div class="flex-shrink-0">
                <i class="ri-error-warning-fill text-red-500 text-xl"></i>
            </div>
            <div class="me-3 flex-1">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
            <button @click="show = false" class="flex-shrink-0 me-3">
                <i class="ri-close-line text-red-500"></i>
            </button>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
