@props([
    'academy',
    'size' => 'md',
    'showName' => false,
    'href' => null,
    'nameClass' => '',
    'iconOnly' => false
])

@php
    // Get academy details - use localized_name accessor for proper locale handling
    $academyName = $academy
        ? $academy->localized_name
        : __('components.academy_logo.default_name');
    $brandColor = $academy && $academy->brand_color ? $academy->brand_color->value : 'sky';

    // Get hex colors for inline styles
    $brandColorHex = '#0ea5e9'; // sky-500 default
    $brandColorLightHex = '#f0f9ff'; // sky-50 default

    if ($academy && $academy->brand_color) {
        try {
            $brandColorHex = $academy->brand_color->getHexValue(600);
            $brandColorLightHex = $academy->brand_color->getHexValue(100);
        } catch (\Exception $e) {
            // Fallback to defaults
        }
    }

    // Determine logo URL - logo_url is an accessor that generates full URL from logo column
    $logoUrl = null;
    if ($academy && $academy->logo) {
        $logoUrl = $academy->logo_url;
    }

    // Size mappings
    $sizes = [
        'sm' => [
            'container' => 'w-8 h-8',
            'image' => 'h-8 w-auto',
            'icon' => 'text-lg',
            'name' => 'text-base',
            'gap' => 'gap-2'
        ],
        'md' => [
            'container' => 'w-10 h-10',
            'image' => 'h-10 w-auto',
            'icon' => 'text-xl',
            'name' => 'text-xl',
            'gap' => 'gap-3'
        ],
        'lg' => [
            'container' => 'w-12 h-12',
            'image' => 'h-12 w-auto',
            'icon' => 'text-2xl',
            'name' => 'text-2xl',
            'gap' => 'gap-3'
        ]
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $defaultNameClass = 'font-bold text-gray-900';
    $finalNameClass = $nameClass ?: $defaultNameClass;
@endphp

@if($href)
    <a href="{{ $href }}" class="flex items-center {{ $sizeClasses['gap'] }}">
        @if($logoUrl)
            <img src="{{ $logoUrl }}"
                 alt="{{ $academyName }}"
                 class="{{ $sizeClasses['image'] }} flex-shrink-0">
        @else
            <div class="{{ $sizeClasses['container'] }} flex items-center justify-center rounded-lg flex-shrink-0"
                 style="background-color: {{ $brandColorLightHex }};">
                <i class="ri-book-open-line {{ $sizeClasses['icon'] }}"
                   style="color: {{ $brandColorHex }};"></i>
            </div>
        @endif

        @if($showName && !$iconOnly)
            <span class="{{ $sizeClasses['name'] }} {{ $finalNameClass }}">{{ $academyName }}</span>
        @endif
    </a>
@else
    <div class="flex items-center {{ $sizeClasses['gap'] }}">
        @if($logoUrl)
            <img src="{{ $logoUrl }}"
                 alt="{{ $academyName }}"
                 class="{{ $sizeClasses['image'] }} flex-shrink-0">
        @else
            <div class="{{ $sizeClasses['container'] }} flex items-center justify-center rounded-lg flex-shrink-0"
                 style="background-color: {{ $brandColorLightHex }};">
                <i class="ri-book-open-line {{ $sizeClasses['icon'] }}"
                   style="color: {{ $brandColorHex }};"></i>
            </div>
        @endif

        @if($showName && !$iconOnly)
            <span class="{{ $sizeClasses['name'] }} {{ $finalNameClass }}">{{ $academyName }}</span>
        @endif
    </div>
@endif
