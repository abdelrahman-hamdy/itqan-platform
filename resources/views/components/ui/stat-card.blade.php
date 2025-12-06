@props([
    'label' => '',
    'value' => '',
    'color' => 'blue',
    'icon' => 'ri-star-line',
    'trend' => null, // 'up', 'down', or null
    'trendValue' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-200 p-6']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm text-gray-600 mb-2">{{ $label }}</p>
            <p class="text-3xl font-bold text-{{ $color }}-600">{{ $value }}</p>

            @if($trend && $trendValue)
                <div class="flex items-center gap-1 mt-2">
                    <i class="ri-arrow-{{ $trend }}-line text-{{ $trend === 'up' ? 'green' : 'red' }}-600 text-sm"></i>
                    <span class="text-sm text-{{ $trend === 'up' ? 'green' : 'red' }}-600">{{ $trendValue }}</span>
                </div>
            @endif
        </div>

        <div class="w-14 h-14 bg-{{ $color }}-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="{{ $icon }} text-{{ $color }}-600 text-2xl"></i>
        </div>
    </div>

    {{ $slot }}
</div>
