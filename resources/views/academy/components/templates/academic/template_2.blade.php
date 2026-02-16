@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCourses = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;
    $defaultTab = $showCourses ? 'courses' : 'teachers';

    // Slider data: take up to 6 items (flat list, no chunking)
    $courseItems = $interactiveCourses->take(6);
    $teacherItems = $academicTeachers->take(6);
@endphp

<!-- Academic Section - Template 2: Clean Professional Design with Tabs -->
<section id="academic" class="py-16 sm:py-20 lg:py-24 relative overflow-hidden transition-colors duration-500 scroll-mt-20"
         x-data="{ activeTab: '{{ $defaultTab }}' }"
         :style="activeTab === 'courses' ? 'background: linear-gradient(to bottom right, {{ $gradientFromHex }}1a, {{ $gradientFromHex }}0d, white)' : 'background: linear-gradient(to bottom right, {{ $gradientToHex }}1a, {{ $gradientToHex }}0d, white)'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8 sm:mb-10 lg:mb-12">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'البرامج التعليمية' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600 mb-6 sm:mb-8">{{ $subheading }}</p>
      @endif

      <!-- Tab Toggle -->
      @if($showCourses && $showTeachers)
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
      @endif
    </div>

    <!-- Interactive Courses Section -->
    @if($showCourses)
    <div x-show="activeTab === 'courses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">الكورسات التفاعلية المتاحة</h3>
        <p class="text-sm sm:text-base text-gray-600">كورسات شاملة ومتطورة تغطي جميع المواد الدراسية بأسلوب تفاعلي ممتع</p>
      </div>

      @if($courseItems->count() > 0)
      <div x-data="{
             current: 0,
             perPage: window.innerWidth >= 768 ? 2 : 1,
             itemCount: {{ $courseItems->count() }},
             timer: null,
             hovering: false,
             touchStartX: 0,
             get totalSlides() { return Math.ceil(this.itemCount / this.perPage) },
             init() {
               this.checkSize();
               window.addEventListener('resize', () => this.checkSize());
               this.play();
             },
             checkSize() {
               let n = window.innerWidth >= 768 ? 2 : 1;
               if (n !== this.perPage) { this.perPage = n; if (this.current >= this.totalSlides) this.current = this.totalSlides - 1; }
             },
             autoNext() { this.current = (this.current + 1) % this.totalSlides; },
             next() { this.current = (this.current + 1) % this.totalSlides; if (!this.hovering) this.play(); },
             prev() { this.current = (this.current - 1 + this.totalSlides) % this.totalSlides; if (!this.hovering) this.play(); },
             goTo(i) { this.current = i; if (!this.hovering) this.play(); },
             play() { this.stop(); this.timer = setInterval(() => this.autoNext(), 4000); },
             stop() { clearInterval(this.timer); this.timer = null; }
           }"
           @mouseenter="hovering = true; stop()"
           @mouseleave="hovering = false; play()"
           @touchstart.passive="touchStartX = $event.touches[0].clientX"
           @touchend.passive="let diff = $event.changedTouches[0].clientX - touchStartX; if(diff < -50) next(); if(diff > 50) prev();">

        <!-- Slider with Navigation Arrows -->
        <div class="relative mb-8 sm:mb-10 lg:mb-12">
          <!-- Right Arrow (Prev in RTL) -->
          <button x-show="totalSlides > 1" @click="prev()"
                  class="hidden sm:flex absolute top-1/2 -translate-y-1/2 right-1 z-10 w-10 h-10 rounded-full bg-white/90 shadow-md items-center justify-center text-gray-500 hover:text-gray-900 hover:shadow-lg transition-all backdrop-blur-sm">
            <i class="ri-arrow-right-s-line text-xl"></i>
          </button>
          <!-- Left Arrow (Next in RTL) -->
          <button x-show="totalSlides > 1" @click="next()"
                  class="hidden sm:flex absolute top-1/2 -translate-y-1/2 left-1 z-10 w-10 h-10 rounded-full bg-white/90 shadow-md items-center justify-center text-gray-500 hover:text-gray-900 hover:shadow-lg transition-all backdrop-blur-sm">
            <i class="ri-arrow-left-s-line text-xl"></i>
          </button>

          <!-- Slider Track -->
          <div class="overflow-hidden sm:mx-14">
            <div class="flex transition-transform duration-500 ease-in-out"
                 :style="{
                   width: (itemCount / perPage * 100) + '%',
                   transform: 'translateX(-' + (current * 100 / totalSlides) + '%)'
                 }">
              @foreach($courseItems as $course)
                <div class="flex-none px-2 sm:px-3" style="width: {{ round(100 / $courseItems->count(), 4) }}%">
                  <x-interactive-course-card :course="$course" :academy="$academy" />
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <!-- Dots Navigation -->
        <div x-show="totalSlides > 1" class="flex justify-center items-center gap-2 mb-8">
          <template x-for="i in totalSlides" :key="i">
            <button @click="goTo(i - 1)"
                    class="w-3 h-3 rounded-full transition-all duration-300 hover:scale-110 focus:outline-none"
                    :class="current === (i - 1) ? 'scale-125' : 'bg-gray-300 hover:bg-gray-400'"
                    :style="current === (i - 1) ? 'background-color: {{ $gradientFromHex }};' : ''">
            </button>
          </template>
        </div>
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
      @else
      <div class="text-center py-12 mb-8">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-book-open-line text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات تفاعلية متاحة حالياً</h3>
        <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
      </div>
      @endif
    </div>
    @endif

    <!-- Academic Teachers Section -->
    @if($showTeachers)
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-8 sm:mb-10 lg:mb-12 text-center">
        <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-2">المعلمون المتميزون</h3>
        <p class="text-sm sm:text-base text-gray-600">نخبة من أفضل المعلمين المتخصصين في جميع المواد الدراسية</p>
      </div>

      @if($teacherItems->count() > 0)
      <div x-data="{
             current: 0,
             perPage: window.innerWidth >= 768 ? 2 : 1,
             itemCount: {{ $teacherItems->count() }},
             timer: null,
             hovering: false,
             touchStartX: 0,
             get totalSlides() { return Math.ceil(this.itemCount / this.perPage) },
             init() {
               this.checkSize();
               window.addEventListener('resize', () => this.checkSize());
               this.play();
             },
             checkSize() {
               let n = window.innerWidth >= 768 ? 2 : 1;
               if (n !== this.perPage) { this.perPage = n; if (this.current >= this.totalSlides) this.current = this.totalSlides - 1; }
             },
             autoNext() { this.current = (this.current + 1) % this.totalSlides; },
             next() { this.current = (this.current + 1) % this.totalSlides; if (!this.hovering) this.play(); },
             prev() { this.current = (this.current - 1 + this.totalSlides) % this.totalSlides; if (!this.hovering) this.play(); },
             goTo(i) { this.current = i; if (!this.hovering) this.play(); },
             play() { this.stop(); this.timer = setInterval(() => this.autoNext(), 4000); },
             stop() { clearInterval(this.timer); this.timer = null; }
           }"
           @mouseenter="hovering = true; stop()"
           @mouseleave="hovering = false; play()"
           @touchstart.passive="touchStartX = $event.touches[0].clientX"
           @touchend.passive="let diff = $event.changedTouches[0].clientX - touchStartX; if(diff < -50) next(); if(diff > 50) prev();">

        <!-- Slider with Navigation Arrows -->
        <div class="relative mb-8 sm:mb-10 lg:mb-12">
          <!-- Right Arrow (Prev in RTL) -->
          <button x-show="totalSlides > 1" @click="prev()"
                  class="hidden sm:flex absolute top-1/2 -translate-y-1/2 right-1 z-10 w-10 h-10 rounded-full bg-white/90 shadow-md items-center justify-center text-gray-500 hover:text-gray-900 hover:shadow-lg transition-all backdrop-blur-sm">
            <i class="ri-arrow-right-s-line text-xl"></i>
          </button>
          <!-- Left Arrow (Next in RTL) -->
          <button x-show="totalSlides > 1" @click="next()"
                  class="hidden sm:flex absolute top-1/2 -translate-y-1/2 left-1 z-10 w-10 h-10 rounded-full bg-white/90 shadow-md items-center justify-center text-gray-500 hover:text-gray-900 hover:shadow-lg transition-all backdrop-blur-sm">
            <i class="ri-arrow-left-s-line text-xl"></i>
          </button>

          <!-- Slider Track -->
          <div class="overflow-hidden sm:mx-14">
            <div class="flex transition-transform duration-500 ease-in-out"
                 :style="{
                   width: (itemCount / perPage * 100) + '%',
                   transform: 'translateX(-' + (current * 100 / totalSlides) + '%)'
                 }">
              @foreach($teacherItems as $teacher)
                <div class="flex-none px-2 sm:px-3" style="width: {{ round(100 / $teacherItems->count(), 4) }}%">
                  <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" />
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <!-- Dots Navigation -->
        <div x-show="totalSlides > 1" class="flex justify-center items-center gap-2 mb-8">
          <template x-for="i in totalSlides" :key="i">
            <button @click="goTo(i - 1)"
                    class="w-3 h-3 rounded-full transition-all duration-300 hover:scale-110 focus:outline-none"
                    :class="current === (i - 1) ? 'scale-125' : 'bg-gray-300 hover:bg-gray-400'"
                    :style="current === (i - 1) ? 'background-color: {{ $gradientToHex }};' : ''">
            </button>
          </template>
        </div>
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
      @else
      <div class="text-center py-12 mb-8">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-user-star-line text-3xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون متاحون حالياً</h3>
        <p class="text-sm text-gray-600">سيتم إضافة المعلمين قريباً</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
