@props([
    'icon' => 'ri-inbox-line',
    'title',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'actionIcon' => null,
    'iconBgColor' => 'amber', // amber, blue, green, gray, purple
    'variant' => 'card', // card, inline
])

@php
    $bgColors = [
        'amber' => 'bg-amber-50',
        'blue' => 'bg-blue-50',
        'green' => 'bg-green-50',
        'gray' => 'bg-gray-100',
        'purple' => 'bg-purple-50',
    ];
    $iconColors = [
        'amber' => 'text-amber-400',
        'blue' => 'text-blue-400',
        'green' => 'text-green-400',
        'gray' => 'text-gray-400',
        'purple' => 'text-purple-400',
    ];
    $buttonColors = [
        'amber' => 'bg-amber-500 hover:bg-amber-600',
        'blue' => 'bg-blue-500 hover:bg-blue-600',
        'green' => 'bg-green-500 hover:bg-green-600',
        'gray' => 'bg-gray-500 hover:bg-gray-600',
        'purple' => 'bg-purple-500 hover:bg-purple-600',
    ];
@endphp

@if($variant === 'card')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
@else
<div class="bg-gray-50 rounded-xl p-12 text-center">
@endif
    <div class="max-w-md mx-auto">
        <div class="w-20 h-20 {{ $bgColors[$iconBgColor] ?? 'bg-amber-50' }} rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="{{ $icon }} text-4xl {{ $iconColors[$iconBgColor] ?? 'text-amber-400' }}"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        @if($description)
            <p class="text-gray-600 mb-6">{{ $description }}</p>
        @endif
        @if($actionUrl && $actionLabel)
            <a href="{{ $actionUrl }}"
               class="inline-flex items-center px-6 py-3 {{ $buttonColors[$iconBgColor] ?? 'bg-amber-500 hover:bg-amber-600' }} text-white rounded-xl transition font-medium">
                @if($actionIcon)
                    <i class="{{ $actionIcon }} ml-2 text-lg"></i>
                @endif
                {{ $actionLabel }}
            </a>
        @endif
    </div>
</div>
