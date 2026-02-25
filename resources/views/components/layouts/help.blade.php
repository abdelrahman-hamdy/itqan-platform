@props([
    'title'       => '',
    'description' => '',
    'role'        => 'student',
])

@php
    $academy = auth()->user()?->academy;
    $pageTitle = $title ?: __('help.title');
    $pageDesc  = $description ?: (__('help.title') . ' - ' . ($academy->name ?? __('common.academy_default')));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <x-app-head :title="$pageTitle" :description="$pageDesc" />
</head>

<body class="bg-gray-50 text-gray-900">

    {{-- Top navigation bar (role-aware, no role-specific sidebar) --}}
    <x-navigation.app-navigation :role="$role" />

    {{-- Main content — full width, no sidebar offset --}}
    <main class="pt-20 min-h-screen transition-all duration-300" id="main-content">
        {{ $slot }}
    </main>

    <x-ui.toast-container />

    @stack('scripts')

</body>

</html>
