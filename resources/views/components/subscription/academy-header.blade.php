@props(['academy', 'title' => null, 'backRoute' => null])

@php
    $title = $title ?? __('components.subscription.academy_header.new_subscription');
@endphp

<header class="bg-white shadow-sm">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
      <!-- Logo and Academy Name -->
      <div class="flex items-center space-x-3 space-x-reverse">
        <x-academy-logo
          :academy="$academy"
          size="md"
          :iconOnly="true" />
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? __('components.subscription.academy_header.default_academy_name') }}</h1>
          <p class="text-sm text-gray-600">{{ $title }}</p>
        </div>
      </div>

      <!-- Back Button -->
      @if($backRoute)
        <a href="{{ $backRoute }}"
           class="flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
           <span class="text-sm font-medium">{{ __('components.common.back') }}</span>
           <i class="ri-arrow-left-line text-xl rtl:rotate-180"></i>
        </a>
      @endif
    </div>
  </div>
</header>
