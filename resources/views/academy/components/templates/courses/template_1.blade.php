@php
    // Get brand color with all shades
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(100);
    $brandColorDarkHex = $brandColor->getHexValue(700);
    $brandColor50Hex = $brandColor->getHexValue(50);
@endphp

<!-- Recorded Courses Section -->
<section id="courses" class="py-16 sm:py-18 lg:py-20 bg-gradient-to-b from-gray-50 to-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl font-bold text-black mb-4">{{ $heading ?? 'الدورات المسجلة' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-700 mb-8">{{ $subheading }}</p>
      @endif
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-8 sm:mb-12">
      @forelse($recordedCourses as $course)
        <x-course-card :course="$course" :academy="$academy" />
      @empty
        <div class="col-span-full text-center py-12">
          <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
               style="background-color: {{ $brandColorLightHex }};">
            <i class="ri-play-circle-line text-3xl" style="color: {{ $brandColorHex }};"></i>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('common.empty_states.no_recorded_courses') }}</h3>
          <p class="text-sm text-gray-600">{{ __('common.empty_states.courses_coming_soon') }}</p>
        </div>
      @endforelse
    </div>
    @if($recordedCourses->count() > 0)
    <div class="text-center mt-8">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center px-6 py-3 text-white rounded-lg font-semibold transition-colors"
         style="background-color: {{ $brandColorHex }};"
         onmouseenter="this.style.opacity='0.9'"
         onmouseleave="this.style.opacity='1'">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ms-2 ltr:rotate-180"></i>
      </a>
    </div>
    @endif
  </div>
</section>
