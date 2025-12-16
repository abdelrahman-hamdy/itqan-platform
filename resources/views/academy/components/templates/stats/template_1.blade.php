@php
  // Get gradient palette for this academy
  $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
  $colors = $gradientPalette->getColors();
  $gradientFrom = $colors['from'];
  $gradientTo = $colors['to'];
  $textGradientClass = $gradientPalette->getTextGradientClass();
@endphp

<!-- Statistics Section -->
<section id="stats" class="bg-white py-12 sm:py-14 lg:py-16" role="region" aria-labelledby="stats-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 id="stats-heading" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'إنجازاتنا بالأرقام' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
      <!-- Students -->
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center mx-auto mb-3 sm:mb-4">
          <i class="ri-user-line text-2xl sm:text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-3xl sm:text-4xl lg:text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_students ?? 15000 }}">0</span>
          <span class="text-xl sm:text-2xl lg:text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-900 mt-2">طالب وطالبة نشط</h3>
        <p class="text-xs sm:text-sm text-gray-600 mt-1 hidden sm:block">من جميع أنحاء العالم</p>
      </div>

      <!-- Teachers -->
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center mx-auto mb-3 sm:mb-4">
          <i class="ri-user-star-line text-2xl sm:text-3xl text-{{ $gradientTo }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-3xl sm:text-4xl lg:text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_teachers ?? 500 }}">0</span>
          <span class="text-xl sm:text-2xl lg:text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-900 mt-2">معلم متخصص</h3>
        <p class="text-xs sm:text-sm text-gray-600 mt-1 hidden sm:block">حاصلون على شهادات معتمدة</p>
      </div>

      <!-- Courses -->
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center mx-auto mb-3 sm:mb-4">
          <i class="ri-book-line text-2xl sm:text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-3xl sm:text-4xl lg:text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_courses ?? 1200 }}">0</span>
          <span class="text-xl sm:text-2xl lg:text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-900 mt-2">كورس تعليمي</h3>
        <p class="text-xs sm:text-sm text-gray-600 mt-1 hidden sm:block">في مختلف التخصصات</p>
      </div>

      <!-- Success Rate -->
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center mx-auto mb-3 sm:mb-4">
          <i class="ri-award-line text-2xl sm:text-3xl text-{{ $gradientTo }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-3xl sm:text-4xl lg:text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_success_rate ?? 95 }}">0</span>
          <span class="text-xl sm:text-2xl lg:text-3xl font-semibold text-gray-500">%</span>
        </div>
        <h3 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-900 mt-2">نسبة النجاح</h3>
        <p class="text-xs sm:text-sm text-gray-600 mt-1 hidden sm:block">معدل إتمام الطلاب للكورسات</p>
      </div>
    </div>
  </div>
</section> 