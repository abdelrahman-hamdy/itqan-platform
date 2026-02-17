@props([
    'title' => null,
    'subtitle' => '',
    'maxWidth' => 'md',
    'academy' => null,
])

@php
    $brandColorName = 'sky';
    $primaryColorHex = '#0ea5e9';
    $primaryColorHexLight = '#e0f2fe'; // sky-100

    if ($academy && $academy->brand_color) {
        $brandColorName = $academy->brand_color->value;
        try {
            $primaryColorHex = $academy->brand_color->getHexValue(500);
            $primaryColorHexLight = $academy->brand_color->getHexValue(100);
        } catch (\Exception $e) {}
    }

    $gradientFromHex = '#06b6d4';
    $gradientToHex = '#2563eb';

    if ($academy && $academy->gradient_palette) {
        $colors = $academy->gradient_palette->getColors();
        [$fromColor, $fromShade] = explode('-', $colors['from']);
        [$toColor, $toShade] = explode('-', $colors['to']);
        try {
            $gradientFromHex = \App\Enums\TailwindColor::from($fromColor)->getHexValue((int)$fromShade);
            $gradientToHex = \App\Enums\TailwindColor::from($toColor)->getHexValue((int)$toShade);
        } catch (\Exception $e) {}
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $academy ? $academy->name . ' - ' : '' }}{{ $title ?? __('common.platform_name') }}</title>

    <!-- Fonts -->
    @include('partials.fonts')

    <!-- Favicon -->
    {!! getFaviconLinkTag($academy) !!}

    <!-- Phone Input Library is bundled via Vite (resources/js/phone-input.js) -->

    <!-- Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->

    <!-- Vite Assets (includes RemixIcon & Flag-icons) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom Styles (after Vite to take precedence) -->
    <style>
        :root {
            --primary-color: {{ $primaryColorHex }};
            --primary-color-light: {{ $primaryColorHexLight }};
            --gradient-from: {{ $gradientFromHex }};
            --gradient-to: {{ $gradientToHex }};
        }

        /* Primary color utilities */
        .text-primary { color: var(--primary-color) !important; }
        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-primary\/10 { background-color: {{ $primaryColorHex }}1a !important; }
        .border-primary { border-color: var(--primary-color) !important; }
        .ring-primary { --tw-ring-color: var(--primary-color) !important; }
        .focus\:ring-primary:focus { --tw-ring-color: var(--primary-color) !important; }
        .focus\:border-primary:focus { border-color: var(--primary-color) !important; }
        .hover\:text-primary:hover { color: var(--primary-color) !important; }
        .hover\:border-primary:hover { border-color: var(--primary-color) !important; }

        /* Gradient buttons */
        .from-primary { --tw-gradient-from: var(--primary-color) !important; }
        .to-secondary { --tw-gradient-to: var(--gradient-to) !important; }
        .bg-gradient-to-r.from-primary.to-secondary {
            background: linear-gradient(to right, var(--gradient-from), var(--gradient-to)) !important;
        }

        /* Rounded button class */
        .rounded-button { border-radius: 0.5rem !important; }

        /* Smooth transitions */
        .transition-smooth {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Input focus effect */
        .input-field:focus {
            box-shadow: 0 0 0 3px {{ $primaryColorHex }}33;
            border-color: {{ $primaryColorHex }};
            outline: none;
        }

        /* Phone input container - force LTR for number input */
        .phone-input-container { width: 100%; }
        .phone-input-container .iti {
            width: 100%;
            direction: ltr;
        }
        .phone-input-container .iti input[type="tel"] {
            width: 100%;
            padding-left: 110px !important;
            padding-right: 16px !important;
            text-align: left;
            direction: ltr;
            border-radius: 0.5rem !important;
        }
        .phone-input-container .iti__flag-container {
            left: 0;
            right: auto;
            border-radius: 0.5rem 0 0 0.5rem;
        }
        .phone-input-container .iti__selected-flag {
            padding: 0 8px 0 12px;
            border-right: 1px solid #d1d5db;
            border-radius: 0.5rem 0 0 0.5rem;
            background: transparent;
        }
        .phone-input-container .iti__selected-dial-code {
            font-weight: 600;
            color: #1f2937;
            margin-left: 6px;
        }
        .phone-input-container .iti__country-list {
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            border-radius: 0.5rem;
            z-index: 100;
        }
        .phone-input-container .iti__arrow {
            margin-left: 6px;
        }

        /* Date input - hide native calendar icon to prevent duplication */
        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            right: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* Hide Alpine elements until initialized */
        [x-cloak] { display: none !important; }
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
                    <p class="text-gray-600">{{ $subtitle }}</p>
                @endif
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                {{ $slot }}
            </div>

            <!-- Footer Links -->
            @isset($footer)
                <div class="mt-6 text-center">{{ $footer }}</div>
            @endisset

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="{{ route('academy.home', ['subdomain' => optional($academy)->subdomain ?? request()->route('subdomain')]) }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="ri-arrow-left-line me-2 rtl:rotate-180"></i>
                    {{ __('common.back_to_home') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 inset-x-4 bg-green-50 border border-green-200 rounded-lg shadow-lg z-50 p-4 flex items-center max-w-md mx-auto">
            <i class="ri-checkbox-circle-fill text-green-500 text-xl flex-shrink-0"></i>
            <p class="ms-3 text-sm font-medium text-green-800 flex-1">{{ session('success') }}</p>
            <button @click="show = false" class="ms-3 text-green-500"><i class="ri-close-line"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 inset-x-4 bg-red-50 border border-red-200 rounded-lg shadow-lg z-50 p-4 flex items-center max-w-md mx-auto">
            <i class="ri-error-warning-fill text-red-500 text-xl flex-shrink-0"></i>
            <p class="ms-3 text-sm font-medium text-red-800 flex-1">{{ session('error') }}</p>
            <button @click="show = false" class="ms-3 text-red-500"><i class="ri-close-line"></i></button>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
