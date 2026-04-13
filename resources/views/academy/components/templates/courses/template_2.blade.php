@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex100 = $brandColor->getHexValue(100);
@endphp

<!-- Courses Section - Template 2: Horizontal Scroll with Vertical Cards -->
<section id="courses" class="py-16 sm:py-20 lg:py-24 scroll-mt-20" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}0a, white, {{ $gradientToHex }}0a);">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-10 sm:mb-12">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">{{ $heading ?? __('academy.courses_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @else
        <p class="text-base sm:text-lg text-gray-600">{{ __('academy.courses_section.default_subheading') }}</p>
      @endif
    </div>

    @php $courseList = $recordedCourses->take(6); @endphp

    @if($courseList->count() > 0)
    <div id="recorded-courses-scroll" class="relative">
      {{-- Scroll Container --}}
      <div class="overflow-x-auto pb-4 -mx-2 px-2 scroll-smooth" style="scrollbar-width: thin; scrollbar-color: {{ $brandHex500 }} #f3f4f6;">
        <div class="flex gap-4 sm:gap-5" style="scroll-snap-type: x mandatory;">
          @foreach($courseList as $course)
          @php
            $thumbnail = $course->getFirstMediaUrl('thumbnails') ?: ($course->thumbnail_url ?? null);
          @endphp
          <div class="flex-shrink-0 w-[260px] sm:w-[300px] snap-start">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:border-gray-200 h-full flex flex-col">
              {{-- Thumbnail --}}
              <div class="relative h-40 sm:h-44 overflow-hidden">
                @if($thumbnail)
                <img src="{{ $thumbnail }}" alt="{{ $course->title }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, {{ $brandHex500 }}25, {{ $gradientToHex }}20, {{ $brandHex500 }}10);">
                  <i class="ri-play-circle-line text-5xl opacity-30" style="color: {{ $brandHex500 }};"></i>
                </div>
                @endif

                {{-- Price Badge --}}
                <div class="absolute top-3 end-3">
                  @if($course->is_free)
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-sm">
                    {{ __('academy.cards.free') }}
                  </span>
                  @elseif($course->price > 0)
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-sm"
                        style="background-color: {{ $brandHex600 }};">
                    {{ number_format($course->price) }} {{ getCurrencySymbol() }}
                  </span>
                  @endif
                </div>

                {{-- Gradient Overlay at bottom --}}
                <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/20 to-transparent"></div>
              </div>

              {{-- Content --}}
              <div class="p-4 flex flex-col flex-1">
                {{-- Title --}}
                <h4 class="text-sm font-bold text-gray-900 line-clamp-2 mb-2">{{ $course->title }}</h4>

                @if($course->description)
                <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $course->description }}</p>
                @endif

                {{-- Spacer --}}
                <div class="flex-grow"></div>

                {{-- Meta Info --}}
                <div class="flex items-center flex-wrap gap-2 mb-3">
                  @if($course->difficulty_level)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium
                    {{ $course->difficulty_level === 'easy' ? 'bg-green-50 text-green-700' :
                       ($course->difficulty_level === 'medium' ? 'bg-amber-50 text-amber-700' :
                       ($course->difficulty_level === 'hard' ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-600')) }}">
                    @switch($course->difficulty_level)
                      @case('easy') {{ __('academy.cards.difficulty_easy') }} @break
                      @case('medium') {{ __('academy.cards.difficulty_medium') }} @break
                      @case('hard') {{ __('academy.cards.difficulty_hard') }} @break
                      @default {{ $course->difficulty_level }}
                    @endswitch
                  </span>
                  @endif

                  @if($course->total_enrollments)
                  <span class="flex items-center gap-1 text-[10px] text-gray-500">
                    <i class="ri-group-line"></i>
                    {{ __('academy.cards.enrollments', ['count' => $course->total_enrollments]) }}
                  </span>
                  @endif

                  @if(($course->avg_rating ?? 0) > 0)
                  <span class="flex items-center gap-0.5 text-[10px]">
                    <i class="ri-star-fill text-amber-400"></i>
                    <span class="text-gray-600 font-medium">{{ number_format($course->avg_rating, 1) }}</span>
                  </span>
                  @endif
                </div>

                {{-- CTA --}}
                <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
                   class="inline-flex items-center justify-center gap-1.5 w-full px-3 py-2 rounded-lg text-xs font-semibold text-white transition-all duration-200 hover:opacity-90"
                   style="background-color: {{ $brandHex500 }};">
                  {{ __('academy.cards.view_course') }}
                  <i class="ri-arrow-left-s-line text-sm ltr:rotate-180"></i>
                </a>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      {{-- Navigation Arrows --}}
      @if($courseList->count() > 2)
      <button class="scroll-prev hidden sm:flex absolute top-1/2 -translate-y-1/2 -start-3 lg:-start-5 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 items-center justify-center z-10"
              style="color: {{ $brandHex500 }};" aria-label="{{ __('academy.actions.view_more') }}">
        <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
      </button>
      <button class="scroll-next hidden sm:flex absolute top-1/2 -translate-y-1/2 -end-3 lg:-end-5 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 items-center justify-center z-10"
              style="color: {{ $brandHex500 }};" aria-label="{{ __('academy.actions.view_more') }}">
        <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
      </button>
      @endif
    </div>

    <!-- View More Button -->
    @if($recordedCourses->count() > 0)
    <div class="text-center mt-6">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:gap-3"
         style="color: {{ $brandHex500 }};">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ltr:rotate-180"></i>
      </a>
    </div>
    @endif
    @else
    <div class="text-center py-12">
      <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3"
           style="background-color: {{ $brandHex100 }};">
        <i class="ri-play-circle-line text-2xl" style="color: {{ $brandHex500 }};"></i>
      </div>
      <p class="text-sm font-medium text-gray-700">{{ __('academy.courses_section.no_courses_title') }}</p>
      <p class="text-xs text-gray-500 mt-1">{{ __('academy.courses_section.no_courses_message') }}</p>
    </div>
    @endif
  </div>
</section>

<style>
  #recorded-courses-scroll .overflow-x-auto::-webkit-scrollbar {
    height: 6px;
  }
  #recorded-courses-scroll .overflow-x-auto::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
  }
  #recorded-courses-scroll .overflow-x-auto::-webkit-scrollbar-thumb {
    background: {{ $brandHex500 }};
    border-radius: 3px;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function initHorizontalScroll(containerId, amount) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var scrollEl = container.querySelector('.overflow-x-auto');
        if (!scrollEl) return;
        var prevBtn = container.querySelector('.scroll-prev');
        var nextBtn = container.querySelector('.scroll-next');
        var isRTL = document.documentElement.dir === 'rtl';

        if (prevBtn) prevBtn.addEventListener('click', function() {
            scrollEl.scrollBy({ left: isRTL ? amount : -amount, behavior: 'smooth' });
        });
        if (nextBtn) nextBtn.addEventListener('click', function() {
            scrollEl.scrollBy({ left: isRTL ? -amount : amount, behavior: 'smooth' });
        });

        var touchStartX = 0;
        scrollEl.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        scrollEl.addEventListener('touchend', function(e) {
            var diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                scrollEl.scrollBy({ left: ((diff > 0 && !isRTL) || (diff < 0 && isRTL)) ? amount : -amount, behavior: 'smooth' });
            }
        }, { passive: true });
    }

    initHorizontalScroll('recorded-courses-scroll', 320);
});
</script>
