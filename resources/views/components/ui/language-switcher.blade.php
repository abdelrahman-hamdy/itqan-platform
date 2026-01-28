@props([
    'dropdown' => true,
    'showLabel' => true,
    'size' => 'md', // sm, md, lg
])

@php
    $currentLocale = app()->getLocale();
    $locales = [
        'ar' => [
            'name' => 'العربية',
            'short' => 'Ar',
            'dir' => 'rtl',
        ],
        'en' => [
            'name' => 'English',
            'short' => 'En',
            'dir' => 'ltr',
        ],
    ];

    $currentLocaleData = $locales[$currentLocale] ?? $locales['ar'];
    $otherLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $otherLocaleData = $locales[$otherLocale];

    $sizes = [
        'sm' => 'text-xs px-2 py-1',
        'md' => 'text-sm px-3 py-2',
        'lg' => 'text-base px-4 py-2.5',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($dropdown)
    {{-- Dropdown Style --}}
    <div x-data="{ open: false }" class="relative inline-block text-left" {{ $attributes }}>
        <button
            @click="open = !open"
            @click.outside="open = false"
            type="button"
            class="inline-flex items-center justify-center gap-2 {{ $sizeClass }} font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
            aria-expanded="true"
            aria-haspopup="true"
        >
            <span class="font-semibold">{{ $currentLocaleData['short'] }}</span>
            @if($showLabel)
                <span>{{ $currentLocaleData['name'] }}</span>
            @endif
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute {{ $currentLocale === 'ar' ? 'left-0' : 'right-0' }} z-50 mt-2 w-40 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            role="menu"
            aria-orientation="vertical"
            tabindex="-1"
        >
            <div class="py-1" role="none">
                @foreach($locales as $locale => $data)
                    <a
                        href="{{ route('language.switch', $locale) }}"
                        class="flex items-center gap-3 px-4 py-2.5 {{ $locale === $currentLocale ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }} transition-colors"
                        role="menuitem"
                        tabindex="-1"
                    >
                        <span class="font-semibold">{{ $data['short'] }}</span>
                        <span class="font-medium">{{ $data['name'] }}</span>
                        @if($locale === $currentLocale)
                            <svg class="w-4 h-4 {{ $currentLocale === 'ar' ? 'mr-auto' : 'ml-auto' }} text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@else
    {{-- Toggle Button Style --}}
    <a
        href="{{ route('language.switch', $otherLocale) }}"
        class="inline-flex items-center gap-2 {{ $sizeClass }} font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
        title="{{ $currentLocale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}"
        {{ $attributes }}
    >
        <span class="font-semibold">{{ $otherLocaleData['short'] }}</span>
        @if($showLabel)
            <span>{{ $otherLocaleData['name'] }}</span>
        @endif
    </a>
@endif
