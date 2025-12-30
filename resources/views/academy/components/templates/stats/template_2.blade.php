@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color with all shades
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColor50Hex = $brandColor->getHexValue(50);
    $brandColor600Hex = $brandColor->getHexValue(600);
@endphp

<!-- Statistics Section - Template 2: Card-Based Clean Design -->
<section id="stats" class="bg-gray-50 py-12 sm:py-14 lg:py-16" role="region" aria-labelledby="stats-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 id="stats-heading" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? __('academy.stats.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <!-- Students -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center transition-colors duration-200 hover:border-opacity-80"
           style="--hover-border-color: {{ $brandColorHex }};"
           onmouseenter="this.style.borderColor='{{ $brandColorHex }}'"
           onmouseleave="this.style.borderColor=''">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 rounded-lg"
             style="background-color: {{ $brandColor50Hex }};">
          <i class="ri-user-line text-xl sm:text-2xl" style="color: {{ $brandColor600Hex }};"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_students ?? 15000 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">{{ __('academy.stats.students.title') }}</h3>
        <p class="text-xs text-gray-500 hidden sm:block">{{ __('academy.stats.students.description') }}</p>
      </div>

      <!-- Teachers -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center transition-colors duration-200"
           onmouseenter="this.style.borderColor='{{ $brandColorHex }}'"
           onmouseleave="this.style.borderColor=''">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 rounded-lg"
             style="background-color: {{ $brandColor50Hex }};">
          <i class="ri-user-star-line text-xl sm:text-2xl" style="color: {{ $brandColor600Hex }};"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_teachers ?? 500 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">{{ __('academy.stats.teachers.title') }}</h3>
        <p class="text-xs text-gray-500 hidden sm:block">{{ __('academy.stats.teachers.description') }}</p>
      </div>

      <!-- Courses -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center transition-colors duration-200"
           onmouseenter="this.style.borderColor='{{ $brandColorHex }}'"
           onmouseleave="this.style.borderColor=''">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 rounded-lg"
             style="background-color: {{ $brandColor50Hex }};">
          <i class="ri-book-line text-xl sm:text-2xl" style="color: {{ $brandColor600Hex }};"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_courses ?? 1200 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">{{ __('academy.stats.courses.title') }}</h3>
        <p class="text-xs text-gray-500 hidden sm:block">{{ __('academy.stats.courses.description') }}</p>
      </div>

      <!-- Success Rate -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center transition-colors duration-200"
           onmouseenter="this.style.borderColor='{{ $brandColorHex }}'"
           onmouseleave="this.style.borderColor=''">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 rounded-lg"
             style="background-color: {{ $brandColor50Hex }};">
          <i class="ri-award-line text-xl sm:text-2xl" style="color: {{ $brandColor600Hex }};"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_success_rate ?? 95 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">%</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">{{ __('academy.stats.success_rate.title') }}</h3>
        <p class="text-xs text-gray-500 hidden sm:block">{{ __('academy.stats.success_rate.description') }}</p>
      </div>
    </div>
  </div>
</section>
