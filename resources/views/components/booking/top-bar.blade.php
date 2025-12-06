@props(['academy', 'title' => 'حجز جديد', 'backRoute' => '#'])

@php
  // Get academy branding
  $brandColor = $academy && $academy->brand_color ? $academy->brand_color->value : 'sky';
  $brandColorClass = "text-{$brandColor}-600";
  $academyName = $academy ? $academy->name : 'أكاديمية إتقان';
@endphp

<!-- Header -->
<header class="bg-white shadow-sm">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
      <!-- Academy Logo and Name -->
      <div class="flex items-center space-x-3 space-x-reverse">
        <x-academy-logo
          :academy="$academy"
          size="md"
          :iconOnly="true" />
        <div>
          <h1 class="text-xl font-bold {{ $brandColorClass }}">{{ $academyName }}</h1>
        </div>
      </div>

      <!-- Return Button -->
      <a href="{{ $backRoute }}"
         class="flex items-center gap-2 text-gray-600 hover:{{ $brandColorClass }} transition-colors">
        <span class="text-sm font-medium">العودة</span>
        <i class="ri-arrow-left-line text-xl"></i>
      </a>
    </div>
  </div>
</header>
