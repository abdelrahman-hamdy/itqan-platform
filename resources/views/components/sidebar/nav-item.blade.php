@props([
    'href',
    'label',
    'icon',
    'active' => false,
    'external' => false,
    'tooltip' => null,
])

<a href="{{ $href }}"
   @if($external) target="_blank" @endif
   class="nav-item flex items-center px-3 py-2 text-base text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ $active ? 'bg-gray-100 text-primary' : '' }}"
   data-tooltip="{{ $tooltip ?? $label }}">
  <i class="{{ $icon }} ml-3 text-lg"></i>
  <span class="nav-text transition-all duration-300">{{ $label }}</span>
  @if($external)
    <i class="ri-external-link-line mr-auto text-xs"></i>
  @endif
</a>
