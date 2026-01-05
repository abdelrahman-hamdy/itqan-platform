<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('common.platform_name') }} - {{ __('common.platform_tagline') }}</title>
    <meta name="description" content="{{ __('common.platform_description') }}">

    <!-- Fonts -->
    @include('partials.fonts')

    <!-- Styles / Scripts (includes RemixIcon & Flag-icons) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #10b981 0%, #0ea5e9 50%, #6366f1 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-sky-500 rounded-lg flex items-center justify-center">
                        <i class="ri-book-open-line text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">{{ __('common.platform_name') }}</span>
                </div>

                <!-- Navigation -->
                @if (Route::has('login'))
                    <nav class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                <i class="ri-dashboard-line"></i>
                                {{ __('common.navigation.dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                                {{ __('common.navigation.login') }}
                            </a>
                            @if (Route::has('student.register'))
                                <a href="{{ route('student.register') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                    {{ __('common.navigation.register') }}
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __('common.welcome.hero_title') }}</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto mb-8">{{ __('common.welcome.hero_subtitle') }}</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-white text-emerald-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="ri-play-circle-line"></i>
                    {{ __('common.welcome.get_started') }}
                </a>
                <a href="#features" class="inline-flex items-center justify-center gap-2 px-8 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition-colors">
                    <i class="ri-information-line"></i>
                    {{ __('common.welcome.learn_more') }}
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ __('common.welcome.features_title') }}</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">{{ __('common.welcome.features_subtitle') }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Quran Learning -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="ri-book-read-line text-emerald-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('common.welcome.feature_quran_title') }}</h3>
                    <p class="text-gray-600">{{ __('common.welcome.feature_quran_description') }}</p>
                </div>

                <!-- Academic Learning -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-sky-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="ri-graduation-cap-line text-sky-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('common.welcome.feature_academic_title') }}</h3>
                    <p class="text-gray-600">{{ __('common.welcome.feature_academic_description') }}</p>
                </div>

                <!-- Interactive Courses -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="ri-video-chat-line text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('common.welcome.feature_interactive_title') }}</h3>
                    <p class="text-gray-600">{{ __('common.welcome.feature_interactive_description') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center gap-3 mb-4 md:mb-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-sky-500 rounded-lg flex items-center justify-center">
                        <i class="ri-book-open-line text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold">{{ __('common.platform_name') }}</span>
                </div>
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} {{ __('common.platform_name') }}. {{ __('common.footer.all_rights_reserved') }}
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
