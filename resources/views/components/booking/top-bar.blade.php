@props(['academy', 'title' => 'حجز جديد', 'backRoute' => '#'])

<!-- Header -->
<header class="bg-white shadow-sm">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
      <!-- Academy Logo and Name -->
      <x-academy-logo
        :academy="$academy"
        size="md"
        :showName="true"
        :href="route('academy.home', ['subdomain' => $academy->subdomain])"
        nameClass="text-xl font-bold text-primary" />

      <!-- Return Button -->
      <a href="{{ $backRoute }}"
         class="flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
        <span class="text-sm font-medium">{{ __('common.navigation.back') }}</span>
        <i class="ri-arrow-left-line text-xl"></i>
      </a>
    </div>
  </div>
</header>
