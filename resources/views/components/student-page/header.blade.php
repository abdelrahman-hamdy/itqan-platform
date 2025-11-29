@props([
    'title',
    'description' => null,
    'countLabel' => null,
    'count' => null,
    'countColor' => 'amber', // amber, blue, green, purple
    'secondaryCountLabel' => null,
    'secondaryCount' => null,
    'secondaryCountColor' => 'green',
])

@php
    $colorClasses = [
        'amber' => 'text-amber-600',
        'blue' => 'text-blue-600',
        'green' => 'text-green-600',
        'purple' => 'text-purple-600',
    ];
@endphp

<div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
            @if($description)
                <p class="text-gray-600">{{ $description }}</p>
            @endif
        </div>
        <div class="flex gap-4">
            @if($count !== null && $count > 0)
                <div class="bg-white rounded-lg px-4 py-2 border border-gray-200 shadow-sm">
                    <span class="text-sm text-gray-600">{{ $countLabel }}: </span>
                    <span class="font-bold text-xl {{ $colorClasses[$countColor] ?? 'text-amber-600' }}">{{ $count }}</span>
                </div>
            @endif
            @if($secondaryCount !== null && $secondaryCount > 0)
                <div class="bg-white rounded-lg px-4 py-2 border border-gray-200 shadow-sm">
                    <span class="text-sm text-gray-600">{{ $secondaryCountLabel }}: </span>
                    <span class="font-bold text-xl {{ $colorClasses[$secondaryCountColor] ?? 'text-green-600' }}">{{ $secondaryCount }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
