@props(['academy', 'title' => 'اشتراك جديد', 'backRoute' => null])

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
          <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? 'أكاديمية إتقان' }}</h1>
          <p class="text-sm text-gray-600">{{ $title }}</p>
        </div>
      </div>

      <!-- Back Button -->
      @if($backRoute)
        <a href="{{ $backRoute }}" 
           class="flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
           <span class="text-sm font-medium">العودة</span>
           <i class="ri-arrow-left-line text-xl"></i>
        </a>
      @endif
    </div>
  </div>
</header>
