<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <x-app-head :title="$title ?? 'الرسائل - ' . config('app.name', 'منصة إتقان')" description="نظام الرسائل والمحادثات">
        @livewireStyles
        @wirechatStyles
    </x-app-head>
</head>

<body class="antialiased bg-gray-50" x-data x-cloak>
    <!-- Include appropriate navigation based on user role -->
    @php
        $userRole = auth()->user()->role ?? auth()->user()->user_type ?? 'student';
    @endphp

    @if($userRole === 'parent')
        <x-navigation.app-navigation role="parent" />
        @include('components.sidebar.parent-sidebar')
    @elseif(auth()->user()->hasRole('student'))
        <x-navigation.app-navigation role="student" />
    @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
        <x-navigation.app-navigation role="teacher" />
    @endif

    <!-- Main Chat Container - Takes Full Viewport Height Minus Navigation -->
    @php
        $hasParentSidebar = $userRole === 'parent';
    @endphp
    <main class="fixed inset-0 pt-20 bg-gray-50 {{ $hasParentSidebar ? 'transition-all duration-300' : '' }}"
          @if($hasParentSidebar) style="margin-right: 320px;" @endif>
        <div class="h-full w-full max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="h-full">
                {{ $slot }}
            </div>
        </div>
    </main>

    @livewireScripts
    @wirechatAssets

    {{-- Custom Confirmation Modal --}}
    <x-wirechat::confirmation-modal />

    {{-- Mobile Sidebar Toggle (for parent users) --}}
    @if($userRole === 'parent')
        <button id="sidebar-toggle-mobile" class="fixed bottom-6 right-6 md:hidden bg-purple-600 text-white p-3 rounded-full shadow-lg z-50">
            <i class="ri-menu-line text-xl"></i>
        </button>
    @endif

</body>
</html>
