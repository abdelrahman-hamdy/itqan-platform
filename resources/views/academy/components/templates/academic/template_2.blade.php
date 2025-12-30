@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];
@endphp

<!-- Academic Section - Template 2: Clean Professional Design with Tabs -->
<section id="academic" class="py-16 sm:py-20 lg:py-24 relative overflow-hidden transition-colors duration-500"
         x-data="{ activeTab: 'courses' }"
         :style="activeTab === 'courses' ? 'background: linear-gradient(to bottom right, {{ $gradientFromHex }}1a, {{ $gradientFromHex }}0d, white)' : 'background: linear-gradient(to bottom right, {{ $gradientToHex }}1a, {{ $gradientToHex }}0d, white)'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'البرامج الأكاديمية' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600 mb-6 sm:mb-8">{{ $subheading }}</p>
      @endif

      <!-- Tab Toggle -->
      <div class="inline-flex bg-white rounded-xl sm:rounded-2xl p-1 sm:p-1.5 shadow-md border border-gray-200">
        <button
          @click="activeTab = 'courses'"
          :style="activeTab === 'courses' ? 'background-color: {{ $gradientFromHex }}; color: white;' : ''"
          :class="activeTab === 'courses' ? 'shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-4 sm:px-6 lg:px-8 py-2 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-book-open-line ms-1 sm:ms-2"></i>
          <span class="hidden sm:inline">الكورسات التفاعلية</span>
          <span class="sm:hidden">الكورسات</span>
        </button>
        <button
          @click="activeTab = 'teachers'"
          :style="activeTab === 'teachers' ? 'background-color: {{ $gradientToHex }}; color: white;' : ''"
          :class="activeTab === 'teachers' ? 'shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-4 sm:px-6 lg:px-8 py-2 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-user-star-line ms-1 sm:ms-2"></i>
          المعلمون
        </button>
      </div>
    </div>

    <!-- Interactive Courses Section -->
    <div x-show="activeTab === 'courses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">الكورسات التفاعلية المتاحة</h3>
        <p class="text-sm sm:text-base text-gray-600">كورسات شاملة ومتطورة تغطي جميع المواد الأكاديمية بأسلوب تفاعلي ممتع</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-8 sm:mb-10 lg:mb-12">
        @forelse($interactiveCourses->take(3) as $course)
          <x-interactive-course-card :course="$course" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
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
           class="inline-flex items-center gap-2 font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
    </div>

    <!-- Academic Teachers Section -->
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">المعلمون الأكاديميون المتميزون</h3>
        <p class="text-sm sm:text-base text-gray-600">نخبة من أفضل المعلمين المتخصصين في جميع المواد الأكاديمية</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8 sm:mb-10 lg:mb-12">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" />
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

      @if($academicTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
