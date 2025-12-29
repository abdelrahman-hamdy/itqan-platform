<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <x-app-head title="{{ $title ?? 'بوابة ولي الأمر' }}">
        @stack('styles')
    </x-app-head>
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <x-navigation.app-navigation role="parent" />

    <!-- Sidebar -->
    @include('components.sidebar.parent-sidebar')

    <!-- Main Content -->
    <main class="pt-20 min-h-screen transition-all duration-300 mr-0 md:mr-80" id="main-content">
        <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            <!-- Page Content -->
            {{ $slot }}
        </div>
    </main>

    <!-- Unified Toast Notification System (handles session flash messages automatically) -->
    <x-ui.toast-container />

    <!-- Unified Confirmation Modal -->
    <x-ui.confirmation-modal />

    @stack('scripts')
</body>
</html>
