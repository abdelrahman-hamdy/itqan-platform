@props(['title' => '', 'description' => '', 'role' => 'student'])

@php
    $isParent = $role === 'parent';
    $academy = auth()->user()->academy;
    $subdomain = $academy->subdomain ?? 'itqan-academy';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <x-app-head :title="$title ?: ($academy->name ?? 'أكاديمية إتقان')" :description="$description ?: ('منصة التعلم الإلكتروني - ' . ($academy->name ?? 'أكاديمية إتقان'))">
        <style>
            /* Card Hover Effects */
            .card-hover {
                transition: all 0.3s ease;
            }

            .card-hover:hover {
                transform: translateY(-4px);
                box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
            }

            /* Stats Counter - Uses Tajawal font */
            .stats-counter {
                font-family: 'Tajawal', sans-serif;
                font-weight: bold;
            }

            /* Focus indicators */
            .focus\:ring-custom:focus {
                outline: 2px solid var(--color-primary-500);
                outline-offset: 2px;
            }
        </style>

        {{ $head ?? '' }}
        @stack('styles')
    </x-app-head>
</head>

<body class="bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <x-navigation.app-navigation :role="$role" />

    <!-- Sidebar -->
    @if($isParent)
        @include('components.sidebar.parent-sidebar')
    @else
        @include('components.sidebar.student-sidebar')
    @endif

    <!-- Main Content -->
    <main class="pt-20 min-h-screen transition-all duration-300 mr-0 md:mr-80" id="main-content">
        <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Page Content -->
            {{ $slot }}
        </div>
    </main>

    @stack('scripts')
</body>
</html>
