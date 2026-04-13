@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex100 = $brandColor->getHexValue(100);
@endphp

<section id="courses" class="py-16 sm:py-20 lg:py-24 scroll-mt-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12 sm:mb-14">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 mb-3">{{ $heading ?? __('academy.courses_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">{{ $subheading }}</p>
      @else
        <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">{{ __('academy.courses_section.default_subheading') }}</p>
      @endif
    </div>

    @php $courseList = $recordedCourses->take(6); @endphp

    @if($courseList->count() > 0)
    <div id="recorded-courses-scroll" class="relative">
      <div class="overflow-x-auto pb-4 -mx-2 px-2 scroll-smooth t2-hide-scrollbar">
        <div class="flex gap-5" style="scroll-snap-type: x mandatory;">
          @foreach($courseList as $course)
          @php
            $thumbnail = $course->getFirstMediaUrl('thumbnails') ?: ($course->thumbnail_url ?? null);
          @endphp
          <div class="flex-shrink-0 w-[280px] sm:w-[320px] snap-start">
            <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
               class="group block relative h-[400px] sm:h-[440px] rounded-xl overflow-hidden"
               style="box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
              {{-- Background --}}
              @if($thumbnail)
              <img src="{{ $thumbnail }}" alt="{{ $course->title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
              @else
              <div class="absolute inset-0" style="background: linear-gradient(160deg, {{ $brandHex500 }}, {{ $gradientToHex }}, {{ $brandHex600 }});">
                <div class="absolute inset-0 flex items-center justify-center opacity-20">
                  <i class="ri-play-circle-line text-[120px] text-white"></i>
                </div>
              </div>
              @endif

              {{-- Dark gradient overlay --}}
              <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

              {{-- Top badges --}}
              <div class="absolute top-4 inset-x-4 flex items-start justify-between">
                @if($course->is_free)
                <span class="px-3 py-1 rounded-full text-xs font-black bg-green-500 text-white">{{ __('academy.cards.free') }}</span>
                @elseif($course->price > 0)
                <span class="px-3 py-1 rounded-full text-xs font-black text-white" style="background: {{ $brandHex600 }};">
                  {{ number_format($course->price) }} {{ getCurrencySymbol() }}
                </span>
                @else
                <span></span>
                @endif

                @if(($course->avg_rating ?? 0) > 0)
                <span class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-black bg-black/40 backdrop-blur-sm text-white">
                  <i class="ri-star-fill text-amber-400"></i>
                  {{ number_format($course->avg_rating, 1) }}
                </span>
                @endif
              </div>

              {{-- Bottom content overlay --}}
              <div class="absolute bottom-0 inset-x-0 p-5">
                {{-- Difficulty badge --}}
                @if($course->difficulty_level)
                <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md mb-3
                  {{ $course->difficulty_level === 'easy' ? 'bg-green-500/20 text-green-300' :
                     ($course->difficulty_level === 'medium' ? 'bg-amber-500/20 text-amber-300' :
                     ($course->difficulty_level === 'hard' ? 'bg-red-500/20 text-red-300' : 'bg-white/10 text-white/60')) }}">
                  @switch($course->difficulty_level)
                    @case('easy') {{ __('academy.cards.difficulty_easy') }} @break
                    @case('medium') {{ __('academy.cards.difficulty_medium') }} @break
                    @case('hard') {{ __('academy.cards.difficulty_hard') }} @break
                    @default {{ $course->difficulty_level }}
                  @endswitch
                </span>
                @endif

                <h4 class="text-base sm:text-lg font-black text-white line-clamp-2 mb-2 leading-tight">{{ $course->title }}</h4>

                @if($course->description)
                <p class="text-xs text-white/50 line-clamp-2 mb-4">{{ $course->description }}</p>
                @endif

                <div class="flex items-center justify-between">
                  @if($course->total_enrollments)
                  <span class="text-xs text-white/40 flex items-center gap-1">
                    <i class="ri-group-line"></i>
                    {{ __('academy.cards.enrollments', ['count' => $course->total_enrollments]) }}
                  </span>
                  @endif

                  <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-white text-gray-900 ms-auto transition-all group-hover:shadow-lg">
                    {{ __('academy.cards.view_course') }}
                    <i class="ri-arrow-left-s-line ltr:rotate-180"></i>
                  </span>
                </div>
              </div>
            </a>
          </div>
          @endforeach
        </div>
      </div>

      {{-- Navigation --}}
      @if($courseList->count() > 2)
      <button class="scroll-prev hidden sm:flex absolute top-1/2 -translate-y-1/2 -start-3 lg:-start-5 w-10 h-10 bg-white rounded-full items-center justify-center z-10 transition-all hover:shadow-lg"
              style="color: {{ $brandHex500 }}; box-shadow: 0 2px 12px rgba(0,0,0,0.1);">
        <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
      </button>
      <button class="scroll-next hidden sm:flex absolute top-1/2 -translate-y-1/2 -end-3 lg:-end-5 w-10 h-10 bg-white rounded-full items-center justify-center z-10 transition-all hover:shadow-lg"
              style="color: {{ $brandHex500 }}; box-shadow: 0 2px 12px rgba(0,0,0,0.1);">
        <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
      </button>
      @endif
    </div>

    @if($recordedCourses->count() > 0)
    <div class="text-center mt-8">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:gap-3"
         style="color: {{ $brandHex500 }};">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ltr:rotate-180"></i>
      </a>
    </div>
    @endif
    @else
    <div class="text-center py-12">
      <p class="text-sm text-gray-500">{{ __('academy.courses_section.no_courses_title') }}</p>
    </div>
    @endif
  </div>
</section>

<style>
  .t2-hide-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
  .t2-hide-scrollbar::-webkit-scrollbar { display: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var c = document.getElementById('recorded-courses-scroll');
    if (!c) return;
    var s = c.querySelector('.overflow-x-auto'), p = c.querySelector('.scroll-prev'), n = c.querySelector('.scroll-next');
    if (!s) return;
    var amt = 340, rtl = document.documentElement.dir === 'rtl';
    if (p) p.addEventListener('click', function() { s.scrollBy({ left: rtl ? amt : -amt, behavior: 'smooth' }); });
    if (n) n.addEventListener('click', function() { s.scrollBy({ left: rtl ? -amt : amt, behavior: 'smooth' }); });
    var tx = 0;
    s.addEventListener('touchstart', function(e) { tx = e.changedTouches[0].screenX; }, { passive: true });
    s.addEventListener('touchend', function(e) {
        var d = tx - e.changedTouches[0].screenX;
        if (Math.abs(d) > 50) s.scrollBy({ left: ((d > 0 && !rtl) || (d < 0 && rtl)) ? amt : -amt, behavior: 'smooth' });
    }, { passive: true });
});
</script>
