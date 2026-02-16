@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCircles = $academy->quran_show_circles ?? true;
    $showTeachers = $academy->quran_show_teachers ?? true;
    $defaultTab = $showCircles ? 'circles' : 'teachers';

    // Slider data: take 6 items, chunk into pairs (2 per slide)
    $circleItems = $quranCircles->take(6);
    $circleChunks = $circleItems->chunk(2);
    $circleSlidePercent = $circleChunks->count() > 0 ? round(100 / $circleChunks->count(), 4) : 100;

    $teacherItems = $quranTeachers->take(6);
    $teacherChunks = $teacherItems->chunk(2);
    $teacherSlidePercent = $teacherChunks->count() > 0 ? round(100 / $teacherChunks->count(), 4) : 100;
@endphp

<!-- Quran Section - Template 2: Clean Professional Design with Tabs -->
<section id="quran" class="py-16 sm:py-20 lg:py-24 relative overflow-hidden transition-colors duration-500 scroll-mt-20"
         x-data="{ activeTab: '{{ $defaultTab }}' }"
         :style="activeTab === 'circles' ? 'background: linear-gradient(to bottom right, {{ $gradientFromHex }}1a, {{ $gradientFromHex }}0d, white)' : 'background: linear-gradient(to bottom right, {{ $gradientToHex }}1a, {{ $gradientToHex }}0d, white)'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? __('academy.quran_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600 mb-6 sm:mb-8">{{ $subheading }}</p>
      @endif

      <!-- Tab Toggle -->
      @if($showCircles && $showTeachers)
      <div class="inline-flex bg-white rounded-xl sm:rounded-2xl p-1 sm:p-1.5 shadow-md border border-gray-200">
        <button
          @click="activeTab = 'circles'"
          :style="activeTab === 'circles' ? 'background-color: {{ $gradientFromHex }}; color: white;' : ''"
          :class="activeTab === 'circles' ? 'shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-4 sm:px-6 lg:px-8 py-2 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-group-line ms-1 sm:ms-2"></i>
          <span class="hidden sm:inline">{{ __('academy.quran_section.tabs.circles') }}</span>
          <span class="sm:hidden">{{ __('academy.quran_section.tabs.circles_short') }}</span>
        </button>
        <button
          @click="activeTab = 'teachers'"
          :style="activeTab === 'teachers' ? 'background-color: {{ $gradientToHex }}; color: white;' : ''"
          :class="activeTab === 'teachers' ? 'shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-4 sm:px-6 lg:px-8 py-2 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-user-star-line ms-1 sm:ms-2"></i>
          {{ __('academy.quran_section.tabs.teachers') }}
        </button>
      </div>
      @endif
    </div>

    <!-- Quran Group Circles Section -->
    @if($showCircles)
    <div x-show="activeTab === 'circles'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">{{ __('academy.quran_section.circles_title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600">{{ __('academy.quran_section.circles_subtitle') }}</p>
      </div>

      @if($circleItems->count() > 0)
      <div x-data="{ current: 0, total: {{ $circleChunks->count() }}, touchStartX: 0, next() { if (this.current < this.total - 1) this.current++; }, prev() { if (this.current > 0) this.current--; } }"
           @touchstart.passive="touchStartX = $event.touches[0].clientX"
           @touchend.passive="let diff = $event.changedTouches[0].clientX - touchStartX; if(diff < -50) next(); if(diff > 50) prev();">
        <div class="overflow-hidden mb-8 sm:mb-10 lg:mb-12">
          <div class="flex transition-transform duration-500 ease-in-out"
               :style="{ width: '{{ $circleChunks->count() * 100 }}%', transform: 'translateX(-' + (current * {{ $circleSlidePercent }}) + '%)' }">
            @foreach($circleChunks as $chunk)
              <div class="flex-none px-1 sm:px-2" style="width: {{ $circleSlidePercent }}%">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  @foreach($chunk as $circle)
                    <x-quran-circle-card-list :circle="$circle" :academy="$academy" />
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <!-- Dots Navigation -->
        @if($circleChunks->count() > 1)
        <div class="flex justify-center items-center gap-2 mb-8">
          @foreach($circleChunks as $index => $chunk)
            <button @click="current = {{ $index }}"
                    class="w-3 h-3 rounded-full transition-all duration-300 hover:scale-110 focus:outline-none"
                    :class="current === {{ $index }} ? 'scale-125' : 'bg-gray-300 hover:bg-gray-400'"
                    :style="current === {{ $index }} ? 'background-color: {{ $gradientFromHex }};' : ''">
            </button>
          @endforeach
        </div>
        @endif
      </div>

      @if($quranCircles->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-12 mb-8">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-group-line text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.quran_section.no_circles_title') }}</h3>
        <p class="text-sm text-gray-600">{{ __('academy.quran_section.no_circles_message') }}</p>
      </div>
      @endif
    </div>
    @endif

    <!-- Quran Teachers Section -->
    @if($showTeachers)
    <div id="quran-teachers" class="scroll-mt-24" x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">{{ __('academy.quran_section.teachers_title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600">{{ __('academy.quran_section.teachers_subtitle') }}</p>
      </div>

      @if($teacherItems->count() > 0)
      <div x-data="{ current: 0, total: {{ $teacherChunks->count() }}, touchStartX: 0, next() { if (this.current < this.total - 1) this.current++; }, prev() { if (this.current > 0) this.current--; } }"
           @touchstart.passive="touchStartX = $event.touches[0].clientX"
           @touchend.passive="let diff = $event.changedTouches[0].clientX - touchStartX; if(diff < -50) next(); if(diff > 50) prev();">
        <div class="overflow-hidden mb-8 sm:mb-10 lg:mb-12">
          <div class="flex transition-transform duration-500 ease-in-out"
               :style="{ width: '{{ $teacherChunks->count() * 100 }}%', transform: 'translateX(-' + (current * {{ $teacherSlidePercent }}) + '%)' }">
            @foreach($teacherChunks as $chunk)
              <div class="flex-none px-1 sm:px-2" style="width: {{ $teacherSlidePercent }}%">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  @foreach($chunk as $teacher)
                    <x-quran-teacher-card-list :teacher="$teacher" :academy="$academy" />
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <!-- Dots Navigation -->
        @if($teacherChunks->count() > 1)
        <div class="flex justify-center items-center gap-2 mb-8">
          @foreach($teacherChunks as $index => $chunk)
            <button @click="current = {{ $index }}"
                    class="w-3 h-3 rounded-full transition-all duration-300 hover:scale-110 focus:outline-none"
                    :class="current === {{ $index }} ? 'scale-125' : 'bg-gray-300 hover:bg-gray-400'"
                    :style="current === {{ $index }} ? 'background-color: {{ $gradientToHex }};' : ''">
            </button>
          @endforeach
        </div>
        @endif
      </div>

      @if($quranTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-12 mb-8">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-user-star-line text-3xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.quran_section.no_teachers_title') }}</h3>
        <p class="text-sm text-gray-600">{{ __('academy.quran_section.no_teachers_message') }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
