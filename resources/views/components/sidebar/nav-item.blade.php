@props([
    'href',
    'label',
    'icon',
    'active' => false,
    'external' => false,
    'tooltip' => null,
    'badge' => null,
    'badgeColor' => 'primary',
])

@php
    $badgeColors = [
        'primary' => 'bg-primary text-white',
        'success' => 'bg-green-500 text-white',
        'warning' => 'bg-yellow-500 text-white',
        'danger' => 'bg-red-500 text-white',
        'info' => 'bg-blue-500 text-white',
    ];
@endphp

<a href="{{ $href }}"
   @if($external) target="_blank" rel="noopener noreferrer" @endif
   class="nav-item flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-base rounded-lg transition-all duration-200
          {{ $active
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-gray-700 hover:bg-gray-50 hover:text-primary'
          }}"
   data-tooltip="{{ $tooltip ?? $label }}"
   aria-current="{{ $active ? 'page' : 'false' }}">

    <i class="{{ $icon }} text-lg flex-shrink-0"></i>

    <span class="nav-text flex-1 truncate transition-all duration-300">{{ $label }}</span>

    @if($badge)
        <span class="nav-text px-2 py-0.5 text-xs font-medium rounded-full {{ $badgeColors[$badgeColor] ?? $badgeColors['primary'] }}">
            {{ $badge }}
        </span>
    @endif

    @if($external)
        <i class="ri-external-link-line text-xs text-gray-400 flex-shrink-0"></i>
    @endif
</a>
