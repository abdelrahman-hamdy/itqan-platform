@props([
    'size' => 'md',
    'color' => 'primary',
    'text' => null,
    'overlay' => false,
    'center' => false,
])

@php
    $sizeClasses = match($size) {
        'xs' => 'h-4 w-4',
        'sm' => 'h-6 w-6',
        'md' => 'h-8 w-8',
        'lg' => 'h-12 w-12',
        'xl' => 'h-16 w-16',
        default => 'h-8 w-8',
    };

    $colorClasses = match($color) {
        'primary' => 'text-primary-600',
        'secondary' => 'text-gray-600',
        'success' => 'text-green-600',
        'danger' => 'text-red-600',
        'warning' => 'text-yellow-600',
        'white' => 'text-white',
        default => 'text-primary-600',
    };

    $containerClasses = $center ? 'flex flex-col items-center justify-center' : 'inline-flex items-center gap-2';
@endphp

@if($overlay)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" role="status" aria-label="{{ $text ?? __('جاري التحميل') }}">
        <div class="flex flex-col items-center gap-3 rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
            <svg class="{{ $sizeClasses }} {{ $colorClasses }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            @if($text)
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $text }}</span>
            @endif
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => $containerClasses]) }} role="status" aria-label="{{ $text ?? __('جاري التحميل') }}">
        <svg class="{{ $sizeClasses }} {{ $colorClasses }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $text }}</span>
        @endif
        <span class="sr-only">{{ $text ?? __('جاري التحميل') }}</span>
    </div>
@endif
