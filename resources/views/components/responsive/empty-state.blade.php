@props([
    'icon' => 'ri-inbox-line',
    'title' => 'لا توجد بيانات',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'compact' => false,
])

@php
    $containerClass = $compact ? 'py-8' : 'py-12 md:py-16';
    $iconSize = $compact ? 'w-16 h-16' : 'w-20 h-20 md:w-24 md:h-24';
    $iconTextSize = $compact ? 'text-2xl' : 'text-3xl md:text-4xl';
    $titleSize = $compact ? 'text-base' : 'text-lg md:text-xl';
@endphp

<div {{ $attributes->merge(['class' => "text-center {$containerClass}"]) }}>
    <div class="{{ $iconSize }} mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center shadow-inner">
        <i class="{{ $icon }} {{ $iconTextSize }} text-gray-400"></i>
    </div>

    <h3 class="{{ $titleSize }} font-bold text-gray-700 mb-2">{{ $title }}</h3>

    @if($description)
        <p class="text-sm text-gray-500 max-w-md mx-auto mb-4">{{ $description }}</p>
    @endif

    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}"
           class="inline-flex items-center gap-2 px-4 py-2 min-h-[44px] bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors">
            {{ $actionLabel }}
        </a>
    @endif

    {{ $slot }}
</div>
