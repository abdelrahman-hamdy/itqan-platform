@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCourses = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;
    $defaultTab = $showCourses ? 'courses' : 'teachers';
@endphp

<!-- Academic Section - Template 3: Classic Design with Dynamic Colored Background -->
<section id="academic" class="py-16 sm:py-18 lg:py-20 relative overflow-hidden transition-colors duration-500 scroll-mt-20" x-data="{ activeTab: '{{ $defaultTab }}' }"
         :style="activeTab === 'courses' ? 'background-color: {{ $gradientFromHex }}12' : 'background-color: {{ $gradientToHex }}12'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header with Tabs Alongside -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8 sm:mb-10">
      <div class="text-center md:text-right">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'البرامج التعليمية' }}</h2>
        @if(isset($subheading))
          <p class="text-sm sm:text-base text-gray-600">{{ $subheading }}</p>
        @endif
      </div>

      <!-- Tab Toggle - Centered on Mobile -->
      @if($showCourses && $showTeachers)
      <div class="flex justify-center md:justify-end">
        <div class="inline-flex gap-1 sm:gap-2 bg-white rounded-lg p-1 shadow-sm border border-gray-200">
          <button
            @click="activeTab = 'courses'"
            :style="activeTab === 'courses' ? 'background-color: {{ $gradientFromHex }}; color: white;' : ''"
            :class="activeTab === 'courses' ? '' : 'text-gray-600 hover:text-gray-900'"
            class="px-3 sm:px-5 py-2 rounded-md font-medium transition-all duration-200 text-xs sm:text-sm whitespace-nowrap">
            <i class="ri-book-open-line ms-1"></i>
            <span class="hidden sm:inline">الكورسات التفاعلية</span>
            <span class="sm:hidden">الكورسات</span>
          </button>
          <button
            @click="activeTab = 'teachers'"
            :style="activeTab === 'teachers' ? 'background-color: {{ $gradientToHex }}; color: white;' : ''"
            :class="activeTab === 'teachers' ? '' : 'text-gray-600 hover:text-gray-900'"
            class="px-3 sm:px-5 py-2 rounded-md font-medium transition-all duration-200 text-xs sm:text-sm whitespace-nowrap">
            <i class="ri-user-star-line ms-1"></i>
            المعلمون
          </button>
        </div>
      </div>
      @endif
    </div>

    <!-- Interactive Courses Section -->
    @if($showCourses)
    <div x-show="activeTab === 'courses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        @forelse($interactiveCourses->take(3) as $course)
          <x-interactive-course-card :course="$course" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-10 sm:py-12">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background-color: {{ $gradientFromHex }}1a;">
              <i class="ri-book-open-line text-3xl" style="color: {{ $gradientFromHex }};"></i>
            </div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات تفاعلية متاحة حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
          </div>
        @endforelse
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="text-center">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md font-medium transition-colors text-sm"
           style="background-color: {{ $gradientFromHex }}1a; color: {{ $gradientFromHex }};"
           onmouseenter="this.style.backgroundColor='{{ $gradientFromHex }}33'"
           onmouseleave="this.style.backgroundColor='{{ $gradientFromHex }}1a'">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
    </div>
    @endif

    <!-- Academic Teachers Section -->
    @if($showTeachers)
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" :subjects="$subjects ?? collect()" :gradeLevels="$gradeLevels ?? collect()" />
        @empty
          <div class="col-span-full text-center py-10 sm:py-12">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background-color: {{ $gradientToHex }}1a;">
              <i class="ri-user-star-line text-3xl" style="color: {{ $gradientToHex }};"></i>
            </div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون متاحون حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة المعلمين قريباً</p>
          </div>
        @endforelse
      </div>

      @if($academicTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md font-medium transition-colors text-sm"
           style="background-color: {{ $gradientToHex }}1a; color: {{ $gradientToHex }};"
           onmouseenter="this.style.backgroundColor='{{ $gradientToHex }}33'"
           onmouseleave="this.style.backgroundColor='{{ $gradientToHex }}1a'">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
