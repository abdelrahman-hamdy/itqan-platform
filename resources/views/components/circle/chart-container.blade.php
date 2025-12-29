{{--
    Chart Container Component
    Wraps Chart.js canvases with consistent styling
--}}

@props([
    'chartId',
    'title',
    'subtitle' => null,
    'iconClass' => 'ri-bar-chart-line',
    'iconColor' => 'text-blue-600',
    'height' => '200px',
    'mdHeight' => '300px'
])

<div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8">
    <div class="flex items-center justify-between mb-4 md:mb-6 gap-2">
        <div class="min-w-0">
            <h3 class="text-base md:text-xl font-bold text-gray-900 truncate">{{ $title }}</h3>
            @if($subtitle)
            <p class="text-gray-600 text-xs md:text-sm">{{ $subtitle }}</p>
            @endif
        </div>
        <i class="{{ $iconClass }} text-xl md:text-2xl {{ $iconColor }} flex-shrink-0"></i>
    </div>
    <div class="chart-container h-[{{ $height }}] md:h-[{{ $mdHeight }}]">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
</div>
