@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];
    $bgGradientLightStyle = "background: linear-gradient(to bottom right, {$gradientFromHex}1a, white, {$gradientToHex}1a);";

    // Get brand color with all shades
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(100);
@endphp

<!-- Courses Section - Template 2: Clean Simple Design -->
<section id="courses" class="py-16 sm:py-20 lg:py-24 scroll-mt-20" style="{{ $bgGradientLightStyle }}">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'الكورسات المسجلة' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Courses Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-8 sm:mb-10 lg:mb-12">
      @forelse($recordedCourses->take(6) as $course)
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

    <!-- View More Button -->
    @if($recordedCourses->count() > 0)
    <div class="text-center">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 font-semibold transition-colors hover:gap-3"
         style="color: {{ $brandColorHex }};">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ltr:rotate-180"></i>
      </a>
    </div>
    @endif
  </div>
</section>
