@props([
    'icon' => 'ri-inbox-line',
    'title',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'actionIcon' => null,
    'color' => 'gray', // gray, amber, blue, green, purple, red
    'variant' => 'card', // card, inline, compact
    'filament' => false, // Wrap in Filament section
])

@php
    $bgColors = [
        'amber' => 'bg-amber-50',
        'blue' => 'bg-blue-50',
        'green' => 'bg-green-50',
        'gray' => 'bg-gray-100',
        'purple' => 'bg-purple-50',
        'red' => 'bg-red-50',
    ];
    $iconColors = [
        'amber' => 'text-amber-400',
        'blue' => 'text-blue-400',
        'green' => 'text-green-400',
        'gray' => 'text-gray-400',
        'purple' => 'text-purple-400',
        'red' => 'text-red-400',
    ];
    $buttonColors = [
        'amber' => 'bg-amber-500 hover:bg-amber-600',
        'blue' => 'bg-blue-500 hover:bg-blue-600',
        'green' => 'bg-green-500 hover:bg-green-600',
        'gray' => 'bg-gray-500 hover:bg-gray-600',
        'purple' => 'bg-purple-500 hover:bg-purple-600',
        'red' => 'bg-red-500 hover:bg-red-600',
    ];

    $isCompact = $variant === 'compact';
    $containerClass = $isCompact ? 'py-8' : 'py-12';
    $iconSize = $isCompact ? 'w-16 h-16' : 'w-20 h-20';
    $iconTextSize = $isCompact ? 'text-2xl' : 'text-3xl';
    $titleSize = $isCompact ? 'text-base' : 'text-lg';

    // Detect if icon is a heroicon or remixicon
    $isHeroicon = str_starts_with($icon, 'heroicon-');
@endphp

@if($filament)
<div class="col-span-full">
    <x-filament::section>
@endif

@if($variant === 'card')
<div {{ $attributes->merge(['class' => "bg-white rounded-xl shadow-sm border border-gray-200 {$containerClass} text-center"]) }}>
@elseif($variant === 'inline')
<div {{ $attributes->merge(['class' => "bg-gray-50 rounded-xl {$containerClass} text-center"]) }}>
@else
<div {{ $attributes->merge(['class' => "text-center {$containerClass}"]) }}>
@endif
    <div class="max-w-md mx-auto px-4">
        <div class="{{ $iconSize }} {{ $bgColors[$color] ?? 'bg-gray-100' }} rounded-full flex items-center justify-center mx-auto mb-4">
            @if($isHeroicon)
                @svg($icon, "w-8 h-8 {$iconColors[$color] ?? 'text-gray-400'}")
            @else
                <i class="{{ $icon }} {{ $iconTextSize }} {{ $iconColors[$color] ?? 'text-gray-400' }}"></i>
            @endif
        </div>

        <h3 class="{{ $titleSize }} font-semibold text-gray-900 mb-2">{{ $title }}</h3>

        @if($description)
            <p class="text-sm text-gray-600 mb-6">{{ $description }}</p>
        @endif

        @if($actionUrl && $actionLabel)
            <a href="{{ $actionUrl }}"
               class="inline-flex items-center px-6 py-3 min-h-[44px] {{ $buttonColors[$color] ?? 'bg-gray-500 hover:bg-gray-600' }} text-white rounded-xl transition font-medium">
                @if($actionIcon)
                    <i class="{{ $actionIcon }} ml-2 text-lg"></i>
                @endif
                {{ $actionLabel }}
            </a>
        @endif

        {{ $slot }}
    </div>
</div>

@if($filament)
    </x-filament::section>
</div>
@endif
