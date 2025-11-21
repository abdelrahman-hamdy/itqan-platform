@props([
    'title',
    'icon' => 'ri-star-line',
    'color' => 'yellow', // yellow or violet
    'features' => []
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
  <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
    <i class="{{ $icon }} text-{{ $color }}-600"></i>
    {{ $title }}
  </h3>
  <ul class="space-y-3 text-sm">
    @foreach($features as $feature)
      <li class="flex items-start gap-2">
        <i class="ri-check-line text-{{ $color }}-600 mt-0.5"></i>
        <span class="text-gray-700">{{ $feature }}</span>
      </li>
    @endforeach
  </ul>
</div>
