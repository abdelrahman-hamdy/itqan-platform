@props([
    'title',
    'collapsible' => false,
    'defaultOpen' => true,
])

<div class="nav-section mb-6" {{ $attributes }}>
    @if($collapsible)
        <div x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-2 py-1.5 min-h-[36px] text-xs font-medium text-gray-400 uppercase tracking-wider transition-colors hover:text-gray-600">
                <span class="nav-section-title">{{ $title }}</span>
                <i :class="open ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-base"></i>
            </button>
            <div x-show="open" x-collapse class="space-y-1 mt-2">
                {{ $slot }}
            </div>
        </div>
    @else
        <h4 class="nav-section-title px-2 text-xs font-medium text-gray-400 uppercase tracking-wider mb-3 transition-all duration-300">
            {{ $title }}
        </h4>
        <div class="space-y-1">
            {{ $slot }}
        </div>
    @endif
</div>
