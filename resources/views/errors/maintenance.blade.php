<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $academy->name ?? __('common.academy_default') }} - {{ __('common.maintenance.title') }}</title>
    <meta name="description" content="{{ __('common.maintenance.subtitle') }}">
    <meta http-equiv="refresh" content="60">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vite Assets (Compiled CSS & JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- RemixIcon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">

    <style>
        :root {
            --color-primary-500: {{ $academy->brand_color?->getHexValue(500) ?? '#0ea5e9' }};
            --color-primary-100: {{ $academy->brand_color?->getHexValue(100) ?? '#e0f2fe' }};
            --color-primary-700: {{ $academy->brand_color?->getHexValue(700) ?? '#0369a1' }};
            --color-secondary-500: {{ $academy->secondary_color?->getHexValue(500) ?? '#10B981' }};
        }

        .gear-rotate {
            animation: rotate 4s linear infinite;
        }

        .pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .bg-pattern {
            background-image:
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }

        .countdown-item {
            background: linear-gradient(135deg, white 0%, rgba(255, 255, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
        }

        /* Primary color utilities for maintenance page */
        .from-primary { --tw-gradient-from: var(--color-primary-500); }
        .to-primaryDark { --tw-gradient-to: var(--color-primary-700); }
        .bg-primaryLight\/30 { background-color: color-mix(in srgb, var(--color-primary-100) 30%, transparent); }
        .border-primaryLight { border-color: var(--color-primary-100); }
        .bg-primary\/10 { background-color: color-mix(in srgb, var(--color-primary-500) 10%, transparent); }
        .text-primary { color: var(--color-primary-500); }
        .bg-secondary\/10 { background-color: color-mix(in srgb, var(--color-secondary-500) 10%, transparent); }
        .border-secondary\/20 { border-color: color-mix(in srgb, var(--color-secondary-500) 20%, transparent); }
        .text-secondary { color: var(--color-secondary-500); }
        .from-secondary { --tw-gradient-from: var(--color-secondary-500); }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center bg-pattern">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Logo Section -->
        @if($academy ?? null)
        <div class="text-center mb-8">
            @if($academy->logo)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-20 mx-auto mb-4">
            @else
            <h1 class="text-3xl font-bold text-gradient from-primary to-primaryDark font-cairo">
                {{ $academy->name }}
            </h1>
            @endif
        </div>
        @endif

        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header Section with Gradient -->
            <div class="bg-gradient-to-br from-primary to-primaryDark p-8 text-white text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 rounded-full mb-4 backdrop-blur-sm">
                    <i class="ri-settings-4-line text-5xl gear-rotate"></i>
                </div>
                <h2 class="text-3xl font-bold mb-2 font-cairo">{{ __('common.maintenance.page_title') }}</h2>
                <p class="text-white/90 text-lg">{{ __('common.maintenance.subtitle') }}</p>
            </div>

            <!-- Content Section -->
            <div class="p-8">
                <!-- Custom Message or Default -->
                <div class="text-center mb-8">
                    @if($message ?? null)
                    <p class="text-gray-700 text-lg leading-relaxed">{{ $message }}</p>
                    @else
                    <p class="text-gray-700 text-lg leading-relaxed mb-4">
                        {{ __('common.maintenance.default_message') }}
                    </p>
                    <p class="text-gray-600">
                        {{ __('common.maintenance.back_soon') }}
                    </p>
                    @endif
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="text-center p-4 rounded-2xl bg-primaryLight/30 border border-primaryLight">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-primary/10 rounded-full mb-3">
                            <i class="ri-shield-check-line text-primary text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">{{ __('common.maintenance.features.security_title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('common.maintenance.features.security_description') }}</p>
                    </div>

                    <div class="text-center p-4 rounded-2xl bg-secondary/10 border border-secondary/20">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-secondary/10 rounded-full mb-3">
                            <i class="ri-speed-up-line text-secondary text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">{{ __('common.maintenance.features.performance_title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('common.maintenance.features.performance_description') }}</p>
                    </div>

                    <div class="text-center p-4 rounded-2xl bg-purple-50 border border-purple-100">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full mb-3">
                            <i class="ri-magic-line text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">{{ __('common.maintenance.features.new_features_title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('common.maintenance.features.new_features_description') }}</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">{{ __('common.maintenance.progress_label') }}</span>
                        <span class="text-sm font-semibold text-primary pulse-slow">{{ __('common.maintenance.in_progress') }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full progress-bar"
                             style="width: 65%; animation: progress 2s ease-in-out infinite alternate;">
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="bg-gray-50 rounded-2xl p-6 text-center">
                    <p class="text-gray-600 mb-4">
                        {{ __('common.maintenance.contact.message') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @if($academy->email ?? null)
                        <a href="mailto:{{ $academy->email }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
                            <i class="ri-mail-line text-primary"></i>
                            <span class="text-gray-700">{{ $academy->email }}</span>
                        </a>
                        @endif

                        @if($academy->phone ?? null)
                        <a href="tel:{{ $academy->phone }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
                            <i class="ri-phone-line text-secondary"></i>
                            <span class="text-gray-700 font-tajawal" dir="ltr">{{ $academy->phone }}</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Auto Refresh Notice -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        <i class="ri-refresh-line"></i>
                        {{ __('common.maintenance.auto_refresh') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600">
            <p class="text-sm">
                © {{ date('Y') }} {{ $academy->name ?? __('common.academy_default') }}. {{ __('common.footer.all_rights_reserved') }}.
            </p>
        </div>
    </div>

    <style>
        @keyframes progress {
            0% { width: 45%; }
            100% { width: 85%; }
        }
    </style>

    <script>
        // Auto-reload page every 60 seconds to check if maintenance is over
        setTimeout(function() {
            window.location.reload();
        }, 60000);

        // Add subtle animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.rounded-2xl');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>