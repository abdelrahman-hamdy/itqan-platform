@props([
    'title' => '',
])

@php
    $user    = auth()->user();
    $academy = $user?->academy;

    // Resolve display name across all roles without touching role-specific profiles
    $displayName = $user?->name ?? __('components.navigation.app.guest');

    // Resolve "back to panel" URL based on user role
    if ($user?->hasRole('super_admin') || $user?->hasRole('admin')) {
        // Super admin panel has no tenant prefix; academy admin has tenant slug
        $backUrl   = $user->hasRole('super_admin') ? '/admin' : '/panel/' . ($academy?->subdomain ?? '');
        $backLabel = __('العودة إلى لوحة الإدارة');
    } elseif ($user?->hasRole('quran_teacher')) {
        $backUrl   = '/teacher-panel';
        $backLabel = __('العودة إلى لوحة المعلم');
    } elseif ($user?->hasRole('academic_teacher')) {
        $backUrl   = '/academic-teacher-panel';
        $backLabel = __('العودة إلى لوحة المعلم');
    } elseif ($user?->hasRole('supervisor')) {
        $backUrl   = '/supervisor-panel';
        $backLabel = __('العودة إلى لوحة المشرف');
    } else {
        $backUrl   = '/';
        $backLabel = __('العودة إلى الرئيسية');
    }

    $pageTitle = ($title ? $title . ' — ' : '') . __('help.title');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <x-app-head :title="$pageTitle" />
</head>

<body class="bg-gray-50 text-gray-900">

    {{-- ── Help center top nav bar ──────────────────────────────────────────── --}}
    <header class="bg-white border-b border-gray-200 fixed top-0 inset-x-0 z-40 h-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between gap-4">

            {{-- Brand: Help Center for Itqan --}}
            <a href="{{ route('help.index') }}"
               class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                    <i class="ri-question-answer-line text-white text-base"></i>
                </div>
                <span class="font-bold text-gray-900 text-base whitespace-nowrap">
                    {{ __('help.brand_title') }}
                </span>
            </a>

            {{-- Right side: back link + user + logout --}}
            <div class="flex items-center gap-3 flex-shrink-0">

                {{-- Back to panel --}}
                <a href="{{ $backUrl }}"
                   class="hidden md:inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary transition-colors">
                    <i class="ri-arrow-left-line rtl:rotate-180"></i>
                    {{ $backLabel }}
                </a>

                {{-- Mobile back button (icon only) --}}
                <a href="{{ $backUrl }}"
                   class="md:hidden flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-primary hover:bg-gray-100 transition-colors"
                   :title="'{{ $backLabel }}'">
                    <i class="ri-arrow-left-line rtl:rotate-180"></i>
                </a>

                <div class="h-5 w-px bg-gray-200 hidden md:block"></div>

                {{-- User name + logout --}}
                <div class="relative flex items-center gap-2"
                     x-data="{ open: false }"
                     @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i class="ri-user-3-line text-primary text-base"></i>
                        </div>
                        <span class="hidden sm:block font-medium max-w-[12rem] truncate">{{ $displayName }}</span>
                        <i class="ri-arrow-down-s-line text-gray-400 text-base hidden sm:block"></i>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute top-full end-0 mt-1 w-52 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
                         @click="open = false">

                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <p class="text-xs text-gray-400">{{ __('مسجل الدخول كـ') }}</p>
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $displayName }}</p>
                        </div>

                        <a href="{{ $backUrl }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="ri-dashboard-line text-gray-400"></i>
                            {{ $backLabel }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <i class="ri-logout-box-r-line"></i>
                                {{ __('components.navigation.app.user_menu.logout') }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </header>

    {{-- ── Page content (top-padded for fixed header) ────────────────────── --}}
    <main class="pt-16 min-h-screen" id="help-main-content">
        {{ $slot }}
    </main>

    <x-ui.toast-container />

    @stack('scripts')

</body>
</html>
