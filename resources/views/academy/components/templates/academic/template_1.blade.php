@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];
    $bgGradientLightStyle = "background: linear-gradient(to bottom right, {$gradientFromHex}1a, white, {$gradientToHex}1a);";
@endphp

<!-- Academic Section -->
<section id="academic" class="py-16 sm:py-20 lg:py-24 relative overflow-hidden" style="{{ $bgGradientLightStyle }}">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12 sm:mb-16 lg:mb-20">
      <h2 class="text-2xl sm:text-3xl font-bold text-black mb-4">{{ $heading ?? __('academy.academic_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-700 mb-8">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Interactive Courses Section -->
    <div class="mb-16 sm:mb-20 lg:mb-24">
      <div class="mb-8 sm:mb-12 flex items-start sm:items-center justify-between gap-4">
        <div>
          <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">{{ __('academy.academic_section.courses_title') }}</h3>
          <p class="text-sm sm:text-base text-gray-600">{{ __('academy.academic_section.courses_subtitle') }}</p>
        </div>
        @if($interactiveCourses->count() > 0)
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 text-white rounded-lg text-sm sm:text-base font-semibold transition-colors whitespace-nowrap flex-shrink-0"
           style="background-color: {{ $gradientFromHex }};"
           onmouseenter="this.style.opacity='0.9'"
           onmouseleave="this.style.opacity='1'">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ms-2 ltr:rotate-180"></i>
        </a>
        @endif
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($interactiveCourses->take(3) as $course)
          <x-interactive-course-card :course="$course" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background-color: {{ $gradientFromHex }}1a;">
              <i class="ri-book-open-line text-3xl" style="color: {{ $gradientFromHex }};"></i>
            </div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.academic_section.no_courses_title') }}</h3>
            <p class="text-sm text-gray-600">{{ __('academy.academic_section.no_courses_message') }}</p>
          </div>
        @endforelse
      </div>
      </div>

    <!-- Academic Teachers Section -->
    <div class="mb-8 sm:mb-12">
      <div class="mb-6 sm:mb-8 flex items-start sm:items-center justify-between gap-4">
        <div>
          <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">{{ __('academy.academic_section.teachers_title') }}</h3>
          <p class="text-sm sm:text-base text-gray-600">{{ __('academy.academic_section.teachers_subtitle') }}</p>
        </div>
        @if($academicTeachers->count() > 0)
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 text-white rounded-lg text-sm sm:text-base font-semibold transition-colors whitespace-nowrap flex-shrink-0"
           style="background-color: {{ $gradientToHex }};"
           onmouseenter="this.style.opacity='0.9'"
           onmouseleave="this.style.opacity='1'">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ms-2 ltr:rotate-180"></i>
        </a>
        @endif
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" :subjects="$subjects ?? collect()" :gradeLevels="$gradeLevels ?? collect()" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background-color: {{ $gradientToHex }}1a;">
              <i class="ri-user-star-line text-3xl" style="color: {{ $gradientToHex }};"></i>
            </div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون أكاديميون متاحون حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة المعلمين قريباً</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>
