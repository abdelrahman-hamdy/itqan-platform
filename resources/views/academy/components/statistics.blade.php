@php
  // Get gradient palette for this academy
  $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
  $colors = $gradientPalette->getColors();
  $gradientFrom = $colors['from'];
  $gradientTo = $colors['to'];
  $textGradientClass = $gradientPalette->getTextGradientClass();
@endphp

<!-- Statistics Section -->
<section class="bg-white py-16" role="region" aria-labelledby="stats-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-12">
      <h2 id="stats-heading" class="text-3xl font-bold text-gray-900 mb-4">إنجازاتنا بالأرقام</h2>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Students -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
          <i class="ri-user-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_students ?? 15000 }}">0</span>
          <span class="text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mt-2">طالب وطالبة نشط</h3>
        <p class="text-sm text-gray-600 mt-1">من جميع أنحاء العالم</p>
      </div>
      
      <!-- Teachers -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
          <i class="ri-user-star-line text-3xl text-{{ $gradientTo }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_teachers ?? 500 }}">0</span>
          <span class="text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mt-2">معلم متخصص</h3>
        <p class="text-sm text-gray-600 mt-1">ذوو خبرة وكفاءة عالية</p>
      </div>
      
      <!-- Courses -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
          <i class="ri-book-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_courses ?? 1200 }}">0</span>
          <span class="text-3xl font-semibold text-gray-500">+</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mt-2">كورس تعليمي</h3>
        <p class="text-sm text-gray-600 mt-1">في مختلف التخصصات</p>
      </div>
      
      <!-- Success Rate -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
          <i class="ri-award-line text-3xl text-{{ $gradientTo }}"></i>
        </div>
        <div class="flex items-baseline justify-center gap-1 mb-2">
          <span class="text-5xl font-extrabold {{ $textGradientClass }} stats-counter" data-target="{{ $academy->stats_success_rate ?? 95 }}">0</span>
          <span class="text-3xl font-semibold text-gray-500">%</span>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mt-2">نسبة النجاح</h3>
        <p class="text-sm text-gray-600 mt-1">معدل إتمام الطلاب للكورسات</p>
      </div>
    </div>
  </div>
</section> 