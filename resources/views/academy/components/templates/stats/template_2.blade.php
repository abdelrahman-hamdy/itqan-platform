@php
  $brandColor = $academy?->brand_color?->value ?? 'sky';
@endphp

<!-- Statistics Section - Template 2: Card-Based Clean Design -->
<section id="stats" class="bg-gray-50 py-12 sm:py-14 lg:py-16" role="region" aria-labelledby="stats-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 id="stats-heading" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'إنجازاتنا بالأرقام' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <!-- Students -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center hover:border-{{ $brandColor }}-300 transition-colors duration-200">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-{{ $brandColor }}-50 rounded-lg">
          <i class="ri-user-line text-xl sm:text-2xl text-{{ $brandColor }}-600"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_students ?? 15000 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">طالب وطالبة نشط</h3>
        <p class="text-xs text-gray-500 hidden sm:block">من جميع أنحاء العالم</p>
      </div>

      <!-- Teachers -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center hover:border-{{ $brandColor }}-300 transition-colors duration-200">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-{{ $brandColor }}-50 rounded-lg">
          <i class="ri-user-star-line text-xl sm:text-2xl text-{{ $brandColor }}-600"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_teachers ?? 500 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">معلم متخصص</h3>
        <p class="text-xs text-gray-500 hidden sm:block">حاصلون على شهادات معتمدة</p>
      </div>

      <!-- Courses -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center hover:border-{{ $brandColor }}-300 transition-colors duration-200">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-{{ $brandColor }}-50 rounded-lg">
          <i class="ri-book-line text-xl sm:text-2xl text-{{ $brandColor }}-600"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_courses ?? 1200 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">+</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">كورس تعليمي</h3>
        <p class="text-xs text-gray-500 hidden sm:block">في مختلف التخصصات</p>
      </div>

      <!-- Success Rate -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-4 sm:p-6 text-center hover:border-{{ $brandColor }}-300 transition-colors duration-200">
        <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-{{ $brandColor }}-50 rounded-lg">
          <i class="ri-award-line text-xl sm:text-2xl text-{{ $brandColor }}-600"></i>
        </div>
        <div class="mb-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 stats-counter" data-target="{{ $academy->stats_success_rate ?? 95 }}">0</span>
          <span class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-400">%</span>
        </div>
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">نسبة النجاح</h3>
        <p class="text-xs text-gray-500 hidden sm:block">معدل إتمام الطلاب للكورسات</p>
      </div>
    </div>
  </div>
</section>
