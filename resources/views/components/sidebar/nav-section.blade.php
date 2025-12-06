@props([
    'title',
])

<div class="mb-6">
  <h4 class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">{{ $title }}</h4>
  <div class="space-y-1">
    {{ $slot }}
  </div>
</div>
