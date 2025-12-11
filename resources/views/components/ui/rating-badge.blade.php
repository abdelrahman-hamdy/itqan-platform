@props([
    'degree' => 0,      // 0-10 scale
    'showLabel' => true,
    'size' => 'md'      // sm, md, lg
])

@php
$label = match(true) {
    $degree >= 8 => 'ممتاز',
    $degree >= 6 => 'جيد',
    $degree >= 4 => 'مقبول',
    default => 'ضعيف'
};

$color = match(true) {
    $degree >= 8 => 'green',
    $degree >= 6 => 'blue',
    $degree >= 4 => 'yellow',
    default => 'red'
};

$sizeClasses = [
    'sm' => 'text-[10px] md:text-xs px-1.5 md:px-2 py-0.5 md:py-1',
    'md' => 'text-xs md:text-sm px-2 md:px-3 py-1',
    'lg' => 'text-sm md:text-base px-3 md:px-4 py-1.5 md:py-2',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full bg-{$color}-100 text-{$color}-700 font-medium {$sizeClasses[$size]}"]) }}>
    <span>{{ number_format($degree, 1) }}</span>
    @if($showLabel)
        <span class="mr-1">{{ $label }}</span>
    @endif
</span>
