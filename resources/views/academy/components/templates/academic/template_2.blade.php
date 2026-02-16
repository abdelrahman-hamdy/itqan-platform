@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCourses = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;
    $defaultTab = $showCourses ? 'courses' : 'teachers';

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
      <div id="academic-courses-carousel" class="relative mb-8 sm:mb-10 lg:mb-12">
        <div class="overflow-hidden mx-10 sm:mx-14">
          <div class="carousel-track flex transition-transform duration-300 ease-in-out">
            @foreach($courseItems as $course)
              <div class="carousel-slide flex-shrink-0 w-full md:w-1/2 px-2 sm:px-3">
                <x-interactive-course-card :course="$course" :academy="$academy" />
              </div>
            @endforeach
          </div>
        </div>

        <!-- Navigation Buttons -->
        <button class="carousel-prev absolute start-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $gradientFromHex }};" aria-label="السابق">
          <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
        </button>
        <button class="carousel-next absolute end-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $gradientFromHex }};" aria-label="التالي">
          <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
        </button>

        <!-- Dots -->
        <div class="carousel-dots flex justify-center items-center gap-3 mt-6"></div>
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
      <div id="academic-teachers-carousel" class="relative mb-8 sm:mb-10 lg:mb-12">
        <div class="overflow-hidden mx-10 sm:mx-14">
          <div class="carousel-track flex transition-transform duration-300 ease-in-out">
            @foreach($teacherItems as $teacher)
              <div class="carousel-slide flex-shrink-0 w-full md:w-1/2 px-2 sm:px-3">
                <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" />
              </div>
            @endforeach
          </div>
        </div>

        <!-- Navigation Buttons -->
        <button class="carousel-prev absolute start-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $gradientToHex }};" aria-label="السابق">
          <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
        </button>
        <button class="carousel-next absolute end-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $gradientToHex }};" aria-label="التالي">
          <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
        </button>

        <!-- Dots -->
        <div class="carousel-dots flex justify-center items-center gap-3 mt-6"></div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    function initSectionSlider(containerId, brandColor) {
        var container = document.getElementById(containerId);
        if (!container) return;

        var track = container.querySelector('.carousel-track');
        var prevBtn = container.querySelector('.carousel-prev');
        var nextBtn = container.querySelector('.carousel-next');
        var dotContainer = container.querySelector('.carousel-dots');
        var items = track.querySelectorAll('.carousel-slide');

        if (!track || !items.length) return;

        var currentIndex = 0;
        var isAnimating = false;
        var totalItems = items.length;

        function getItemsPerView() {
            return window.innerWidth >= 768 ? 2 : 1;
        }

        function getMaxIndex() {
            return Math.max(0, totalItems - getItemsPerView());
        }

        function updateCarousel() {
            if (isAnimating) return;
            isAnimating = true;

            var itemsPerView = getItemsPerView();
            var maxIndex = getMaxIndex();
            currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));

            var itemWidthPercent = 100 / itemsPerView;
            var translatePercent = currentIndex * itemWidthPercent;
            var isRTL = document.documentElement.dir === 'rtl';

            if (isRTL) {
                track.style.transform = 'translateX(' + translatePercent + '%)';
            } else {
                track.style.transform = 'translateX(-' + translatePercent + '%)';
            }

            updateDots();
            updateButtons();

            setTimeout(function() { isAnimating = false; }, 350);
        }

        function updateButtons() {
            var maxIndex = getMaxIndex();
            if (prevBtn) {
                prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
                prevBtn.style.cursor = currentIndex === 0 ? 'default' : 'pointer';
            }
            if (nextBtn) {
                nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
                nextBtn.style.cursor = currentIndex >= maxIndex ? 'default' : 'pointer';
            }
        }

        function createDots() {
            if (!dotContainer) return;
            var maxIndex = getMaxIndex();
            var numDots = maxIndex + 1;
            dotContainer.innerHTML = '';
            for (var i = 0; i < numDots; i++) {
                (function(idx) {
                    var dot = document.createElement('button');
                    dot.className = 'w-3 h-3 rounded-full transition-all duration-300 cursor-pointer';
                    dot.style.backgroundColor = idx === currentIndex ? brandColor : '#d1d5db';
                    if (idx === currentIndex) dot.style.transform = 'scale(1.3)';
                    dot.setAttribute('aria-label', 'الانتقال إلى ' + (idx + 1));
                    dot.addEventListener('click', function() {
                        if (isAnimating || idx === currentIndex) return;
                        currentIndex = idx;
                        updateCarousel();
                        restartAutoplay();
                    });
                    dotContainer.appendChild(dot);
                })(i);
            }
        }

        function updateDots() {
            if (!dotContainer) return;
            var dots = dotContainer.querySelectorAll('button');
            dots.forEach(function(dot, index) {
                dot.style.backgroundColor = index === currentIndex ? brandColor : '#d1d5db';
                dot.style.transform = index === currentIndex ? 'scale(1.3)' : 'scale(1)';
            });
        }

        function goNext() {
            if (isAnimating) return;
            var maxIndex = getMaxIndex();
            if (currentIndex < maxIndex) currentIndex++;
            else currentIndex = 0;
            updateCarousel();
        }

        function goPrev() {
            if (isAnimating) return;
            if (currentIndex > 0) currentIndex--;
            else currentIndex = getMaxIndex();
            updateCarousel();
        }

        if (nextBtn) nextBtn.addEventListener('click', function() { goNext(); restartAutoplay(); });
        if (prevBtn) prevBtn.addEventListener('click', function() { goPrev(); restartAutoplay(); });

        // Autoplay
        var autoTimer = null;
        function startAutoplay() {
            stopAutoplay();
            autoTimer = setInterval(goNext, 4000);
        }
        function stopAutoplay() {
            if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
        }
        function restartAutoplay() { startAutoplay(); }

        container.addEventListener('mouseenter', stopAutoplay);
        container.addEventListener('mouseleave', startAutoplay);

        // Resize
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                var maxIndex = getMaxIndex();
                if (currentIndex > maxIndex) currentIndex = maxIndex;
                createDots();
                isAnimating = false;
                updateCarousel();
            }, 150);
        });

        // Touch/swipe
        var touchStartX = 0;
        track.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        track.addEventListener('touchend', function(e) {
            var diff = touchStartX - e.changedTouches[0].screenX;
            var isRTL = document.documentElement.dir === 'rtl';
            if (Math.abs(diff) > 50) {
                if ((diff > 0 && !isRTL) || (diff < 0 && isRTL)) goNext();
                else goPrev();
            }
            startAutoplay();
        }, { passive: true });

        // Initialize
        createDots();
        updateCarousel();
        startAutoplay();
    }

    initSectionSlider('academic-courses-carousel', '{{ $gradientFromHex }}');
    initSectionSlider('academic-teachers-carousel', '{{ $gradientToHex }}');
});
</script>
