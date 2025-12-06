@props([
    'percentage' => 0,
    'label' => '',
    'color' => 'blue',
    'showPercentage' => true,
    'height' => 'h-2',
    'showLabel' => true,
])

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($showLabel && $label)
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-gray-600">{{ $label }}</span>
            @if($showPercentage)
                <span class="text-sm font-medium text-gray-900">{{ number_format($percentage, 0) }}%</span>
            @endif
        </div>
    @endif

    <div class="w-full bg-gray-200 rounded-full {{ $height }} overflow-hidden">
        <div class="{{ $height }} rounded-full transition-all duration-500 bg-{{ $color }}-500"
             style="width: {{ min($percentage, 100) }}%">
        </div>
    </div>
</div>
