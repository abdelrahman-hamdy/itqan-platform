{{--
    Progress Stat Card Component
    Displays a statistic with icon and optional subtitle
--}}

@props([
    'label',
    'value',
    'subtitle' => null,
    'subtitleIcon' => null,
    'icon' => 'ri-bar-chart-line',
    'color' => 'blue',
    'showProgressBar' => false,
    'progressValue' => 0,
    'colSpan' => ''
])

@php
    $colorClasses = [
        'blue' => [
            'border' => 'border-blue-100',
            'text' => 'text-blue-600',
            'value' => 'text-blue-900',
            'bg' => 'bg-blue-100',
            'progress' => 'bg-blue-500'
        ],
        'green' => [
            'border' => 'border-green-100',
            'text' => 'text-green-600',
            'value' => 'text-green-900',
            'bg' => 'bg-green-100',
            'progress' => 'bg-green-500'
        ],
        'purple' => [
            'border' => 'border-purple-100',
            'text' => 'text-purple-600',
            'value' => 'text-purple-900',
            'bg' => 'bg-purple-100',
            'progress' => 'bg-purple-500'
        ],
        'orange' => [
            'border' => 'border-orange-100',
            'text' => 'text-orange-600',
            'value' => 'text-orange-900',
            'bg' => 'bg-orange-100',
            'progress' => 'bg-orange-500'
        ],
        'emerald' => [
            'border' => 'border-emerald-100',
            'text' => 'text-emerald-600',
            'value' => 'text-emerald-900',
            'bg' => 'bg-emerald-100',
            'progress' => 'bg-emerald-500'
        ],
    ];
    $colors = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div class="bg-white rounded-xl md:rounded-2xl shadow-lg border {{ $colors['border'] }} p-3 md:p-6 hover:shadow-xl transition-all duration-300 group {{ $colSpan }}">
    <div class="flex items-center justify-between gap-2">
        <div class="min-w-0 @if($showProgressBar) flex-1 @endif">
            <p class="text-xs md:text-sm font-medium {{ $colors['text'] }} mb-0.5 md:mb-1 truncate">{{ $label }}</p>
            <p class="text-xl md:text-3xl font-bold {{ $colors['value'] }} mb-1 md:mb-2">{{ $value }}</p>
            @if($showProgressBar)
                <div class="w-full {{ $colors['bg'] }} rounded-full h-1.5 md:h-2">
                    <div class="{{ $colors['progress'] }} h-1.5 md:h-2 rounded-full transition-all duration-500"
                         style="width: {{ $progressValue }}%"></div>
                </div>
            @elseif($subtitle)
                <div class="flex items-center text-[10px] md:text-xs {{ $colors['text'] }}">
                    @if($subtitleIcon)
                    <i class="{{ $subtitleIcon }} ms-0.5 md:ms-1"></i>
                    @endif
                    <span>{{ $subtitle }}</span>
                </div>
            @endif
        </div>
        <div class="w-8 h-8 md:w-12 md:h-12 {{ $colors['bg'] }} rounded-lg md:rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
            <i class="{{ $icon }} text-lg md:text-2xl {{ $colors['text'] }}"></i>
        </div>
    </div>
</div>
