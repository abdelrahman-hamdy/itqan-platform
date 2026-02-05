@php
    // Get brand color with all shades
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(100);
    $brandColorDarkHex = $brandColor->getHexValue(700);
    $brandColor50Hex = $brandColor->getHexValue(50);
@endphp

<!-- Courses Section - Template 3: Classic Simple Design -->
<section id="courses" class="py-16 sm:py-18 lg:py-20 scroll-mt-20" style="background-color: {{ $brandColor50Hex }};">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Center on Mobile, Right on Desktop -->
    <div class="text-center md:text-right mb-8 sm:mb-10">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'الكورسات المسجلة' }}</h2>
      @if(isset($subheading))
        <p class="text-sm sm:text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Courses Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
      @forelse($recordedCourses->take(6) as $course)
        <x-course-card :course="$course" :academy="$academy" />
      @empty
        <div class="col-span-full text-center py-10 sm:py-12">
          <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
               style="background-color: {{ $brandColorLightHex }};">
            <i class="ri-play-circle-line text-3xl" style="color: {{ $brandColorHex }};"></i>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات مسجلة متاحة حالياً</h3>
          <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
        </div>
      @endforelse
    </div>

    <!-- View More Button -->
    @if($recordedCourses->count() > 0)
    <div class="text-center">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md font-medium transition-colors text-sm"
         style="background-color: {{ $brandColorHex }}1a; color: {{ $brandColorDarkHex }};"
         onmouseenter="this.style.backgroundColor='{{ $brandColorHex }}33'"
         onmouseleave="this.style.backgroundColor='{{ $brandColorHex }}1a'">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ltr:rotate-180"></i>
      </a>
    </div>
    @endif
  </div>
</section>
