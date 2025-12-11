@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'iconClass' => 'text-primary',
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6']) }}>
    <div class="flex items-center gap-3">
        @if($icon)
            <div class="flex-shrink-0 w-10 h-10 md:w-12 md:h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <i class="{{ $icon }} text-xl md:text-2xl {{ $iconClass }}"></i>
            </div>
        @endif
        <div>
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex items-center gap-2 flex-wrap">
            {{ $actions }}
        </div>
    @endisset
</div>
