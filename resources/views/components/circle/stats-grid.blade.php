@props([
    'stats' => [],
    'variant' => 'default', // 'default', 'compact', 'minimal'
    'columns' => 'grid-cols-2 lg:grid-cols-4',
    'showIcons' => true,
    'showBorders' => true
])

@php
    $baseClasses = $variant === 'compact' ? 'p-3' : 'p-4';
    $borderClasses = $showBorders ? 'border border-opacity-30' : '';
@endphp

<div class="grid {{ $columns }} gap-4">
    @foreach($stats as $stat)
        @php
            $color = $stat['color'] ?? 'blue';
            $bgGradient = "bg-gradient-to-r from-{$color}-50 to-{$color}-100";
            $borderColor = "border-{$color}-200";
            $iconBg = "bg-{$color}-200";
            $iconColor = "text-{$color}-600";
            $textColor = "text-{$color}-700";
            $valueColor = "text-{$color}-900";
        @endphp
        
        <div class="flex items-center justify-between {{ $baseClasses }} {{ $bgGradient }} rounded-xl shadow-sm {{ $borderClasses }} {{ $borderColor }} transition-all duration-200 hover:shadow-md">
            <div>
                <p class="text-sm font-medium {{ $textColor }}">{{ $stat['label'] }}</p>
                <p class="text-2xl font-bold {{ $valueColor }}">{{ $stat['value'] }}</p>
                @if(isset($stat['subtitle']))
                    <p class="text-xs text-gray-600 mt-1">{{ $stat['subtitle'] }}</p>
                @endif
            </div>
            @if($showIcons && isset($stat['icon']))
                <div class="p-2 {{ $iconBg }} rounded-lg">
                    <i class="{{ $stat['icon'] }} text-xl {{ $iconColor }}"></i>
                </div>
            @endif
        </div>
    @endforeach
</div>
