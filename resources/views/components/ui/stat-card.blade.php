@props([
    'label' => '',
    'value' => '',
    'color' => 'blue',
    'icon' => 'ri-star-line',
    'trend' => null, // 'up', 'down', or null
    'trendValue' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6']) }}>
    <div class="flex items-center justify-between gap-2 md:gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2 truncate">{{ $label }}</p>
            <p class="text-xl md:text-2xl lg:text-3xl font-bold text-{{ $color }}-600">{{ $value }}</p>

            @if($trend && $trendValue)
                <div class="flex items-center gap-1 mt-1 md:mt-2">
                    <i class="ri-arrow-{{ $trend }}-line text-{{ $trend === 'up' ? 'green' : 'red' }}-600 text-xs md:text-sm"></i>
                    <span class="text-xs md:text-sm text-{{ $trend === 'up' ? 'green' : 'red' }}-600">{{ $trendValue }}</span>
                </div>
            @endif
        </div>

        <div class="w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 bg-{{ $color }}-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="{{ $icon }} text-{{ $color }}-600 text-lg md:text-xl lg:text-2xl"></i>
        </div>
    </div>

    {{ $slot }}
</div>
