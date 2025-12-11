<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <x-app-head title="{{ $title ?? 'بوابة ولي الأمر' }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <x-navigation.app-navigation role="parent" />

    <!-- Sidebar -->
    @include('components.sidebar.parent-sidebar')

    <!-- Main Content -->
    <main class="pt-20 min-h-screen transition-all duration-300 mr-0 md:mr-80" id="main-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Page Content -->
            {{ $slot }}
        </div>
    </main>

    <!-- Mobile Sidebar Toggle -->
    <button id="sidebar-toggle-mobile"
            class="fixed bottom-6 right-6 md:hidden bg-purple-600 text-white p-4 rounded-full shadow-lg z-50 min-h-[56px] min-w-[56px] flex items-center justify-center hover:scale-105 active:scale-95 transition-transform"
            aria-label="فتح القائمة الجانبية">
        <i class="ri-menu-line text-2xl"></i>
    </button>

    <!-- Unified Confirmation Modal -->
    <x-ui.confirmation-modal />

    @stack('scripts')
</body>
</html>
