@props([
    'value' => 0,        // 0-100 percentage
    'size' => 'md',      // sm, md, lg, xl
    'color' => 'blue',   // blue, green, yellow, red, purple
    'label' => '',
    'sublabel' => '',
    'showPercentage' => true
])

@php
$sizeClasses = [
    'sm' => 'w-16 h-16 md:w-20 md:h-20',
    'md' => 'w-24 h-24 md:w-32 md:h-32',
    'lg' => 'w-32 h-32 md:w-40 md:h-40',
    'xl' => 'w-40 h-40 md:w-48 md:h-48',
];

$textSizeClasses = [
    'sm' => 'text-base md:text-lg',
    'md' => 'text-xl md:text-2xl',
    'lg' => 'text-2xl md:text-3xl',
    'xl' => 'text-3xl md:text-4xl',
];

$radius = 54;
$circumference = 2 * pi() * $radius;
$offset = $circumference * (1 - ($value / 100));

$colorMap = [
    'blue' => '#3b82f6',
    'green' => '#22c55e',
    'yellow' => '#eab308',
    'red' => '#ef4444',
    'purple' => '#a855f7',
];

$strokeColor = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2']) }}>
    <div class="relative {{ $sizeClasses[$size] }}">
        <svg class="transform -rotate-90 w-full h-full" viewBox="0 0 120 120">
            <!-- Background circle -->
            <circle cx="60" cy="60" r="{{ $radius }}"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-width="8">
            </circle>

            <!-- Progress circle -->
            <circle cx="60" cy="60" r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $strokeColor }}"
                    stroke-width="8"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $offset }}"
                    stroke-linecap="round"
                    class="transition-all duration-500">
            </circle>
        </svg>

        <!-- Center text -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            @if($showPercentage)
                <span class="{{ $textSizeClasses[$size] }} font-bold text-gray-900">{{ number_format($value, 0) }}%</span>
            @endif
            @if($label)
                <span class="text-xs md:text-sm text-gray-600 mt-0.5 md:mt-1">{{ $label }}</span>
            @endif
        </div>
    </div>

    @if($sublabel)
        <span class="text-xs md:text-sm text-gray-500 text-center">{{ $sublabel }}</span>
    @endif
</div>
