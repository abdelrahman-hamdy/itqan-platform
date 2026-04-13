@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors       = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex   = $hexColors['to'];

    $brandColor  = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex950 = $brandColor->getHexValue(950);
@endphp

<section id="courses" class="py-20 sm:py-24 lg:py-28 scroll-mt-20" style="background: {{ $brandHex950 }};">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="max-w-2xl mb-12 sm:mb-14">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-[3px] rounded-full" style="background: {{ $brandHex500 }};"></div>
        <span class="text-xs font-bold uppercase text-white/30">{{ __('academy.nav.sections.courses') }}</span>
      </div>
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white leading-tight">{{ $heading ?? __('academy.courses_section.default_heading') }}</h2>
      <p class="text-base text-white/35 mt-3 leading-relaxed">
        @if(isset($subheading)) {{ $subheading }} @else {{ __('academy.courses_section.default_subheading') }} @endif
      </p>
    </div>

    @php $courseList = $recordedCourses->take(6); @endphp

    @if($courseList->count() > 0)
    <div id="t2-courses-scroll" class="relative group/scroll">
      <div class="overflow-x-auto pb-6 t2-no-scrollbar scroll-smooth">
        <div class="flex gap-5" style="scroll-snap-type: x mandatory;">
          @foreach($courseList as $course)
          @php $thumb = $course->getFirstMediaUrl('thumbnails') ?: ($course->thumbnail_url ?? null); @endphp
          <div class="shrink-0 w-[270px] sm:w-[300px] snap-start">
            <a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
               class="group block relative h-[380px] sm:h-[420px] rounded-lg overflow-hidden">

              {{-- Image / gradient fallback --}}
              @if($thumb)
              <img src="{{ $thumb }}" alt="{{ $course->title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
              @else
              <div class="absolute inset-0" style="background: linear-gradient(160deg, {{ $brandHex500 }}, {{ $gradientToHex }});">
                <div class="absolute inset-0 flex items-center justify-center opacity-10">
                  <i class="ri-play-circle-line text-[100px] text-white"></i>
                </div>
              </div>
              @endif

              {{-- Overlay --}}
              <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>

              {{-- Top badges --}}
              <div class="absolute top-4 inset-x-4 flex justify-between">
                @if($course->is_free)
                <span class="text-[10px] font-black uppercase px-2.5 py-1 bg-green-500 text-white rounded-lg">{{ __('academy.cards.free') }}</span>
                @elseif($course->price > 0)
                <span class="text-[10px] font-black uppercase px-2.5 py-1 text-white rounded-lg" style="background: {{ $brandHex600 }};">{{ number_format($course->price) }} {{ getCurrencySymbol() }}</span>
                @else
                <span></span>
                @endif

                @if(($course->avg_rating ?? 0) > 0)
                <span class="flex items-center gap-1 text-[11px] font-black px-2 py-1 bg-black/50 backdrop-blur-sm text-white rounded-lg">
                  <i class="ri-star-fill text-amber-400 text-[10px]"></i>{{ number_format($course->avg_rating, 1) }}
                </span>
                @endif
              </div>

              {{-- Bottom content --}}
              <div class="absolute bottom-0 inset-x-0 p-5">
                @if($course->difficulty_level)
                <span class="inline-block text-[9px] font-black uppercase px-2 py-0.5 rounded-lg mb-3
                  {{ $course->difficulty_level === 'easy' ? 'bg-emerald-500/25 text-emerald-300' :
                     ($course->difficulty_level === 'medium' ? 'bg-amber-500/25 text-amber-300' :
                     ($course->difficulty_level === 'hard' ? 'bg-red-500/25 text-red-300' : 'bg-white/10 text-white/50')) }}">
                  @switch($course->difficulty_level)
                    @case('easy') {{ __('academy.cards.difficulty_easy') }} @break
                    @case('medium') {{ __('academy.cards.difficulty_medium') }} @break
                    @case('hard') {{ __('academy.cards.difficulty_hard') }} @break
                    @default {{ $course->difficulty_level }}
                  @endswitch
                </span>
                @endif

                <h4 class="text-base font-black text-white line-clamp-2 leading-snug mb-2">{{ $course->title }}</h4>

                @if($course->description)
                <p class="text-[11px] text-white/40 line-clamp-2 mb-4">{{ $course->description }}</p>
                @endif

                <div class="flex items-center justify-between">
                  @if($course->total_enrollments)
                  <span class="text-[10px] text-white/30 flex items-center gap-1">
                    <i class="ri-group-line"></i>{{ __('academy.cards.enrollments', ['count' => $course->total_enrollments]) }}
                  </span>
                  @endif
                  <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-gray-900 text-[11px] font-bold rounded-lg ms-auto transition-transform duration-200 group-hover:translate-x-0.5 ltr:group-hover:-translate-x-0.5">
                    {{ __('academy.cards.view_course') }}
                    <i class="ri-arrow-left-s-line text-xs ltr:rotate-180"></i>
                  </span>
                </div>
              </div>
            </a>
          </div>
          @endforeach
        </div>
      </div>

      {{-- Nav arrows --}}
      @if($courseList->count() > 2)
      <button class="t2-scroll-prev hidden sm:flex absolute top-1/2 -translate-y-1/2 -start-3 lg:-start-5 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg items-center justify-center z-10 text-white/70 transition-all hover:bg-white/20 opacity-0 group-hover/scroll:opacity-100">
        <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
      </button>
      <button class="t2-scroll-next hidden sm:flex absolute top-1/2 -translate-y-1/2 -end-3 lg:-end-5 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg items-center justify-center z-10 text-white/70 transition-all hover:bg-white/20 opacity-0 group-hover/scroll:opacity-100">
        <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
      </button>
      @endif
    </div>

    @if($recordedCourses->count() > 0)
    <div class="mt-10 text-center">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg text-white/70 bg-white/[0.06] transition-all duration-200 hover:bg-white/[0.12] hover:text-white hover:gap-3">
        {{ __('academy.actions.view_more') }}
        <i class="ri-arrow-left-line ltr:rotate-180"></i>
      </a>
    </div>
    @endif
    @else
    <p class="text-sm text-white/30 py-12 text-center">{{ __('academy.courses_section.no_courses_title') }}</p>
    @endif
  </div>
</section>

<style>
  .t2-no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
  .t2-no-scrollbar::-webkit-scrollbar { display: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var w = document.getElementById('t2-courses-scroll');
  if (!w) return;
  var s = w.querySelector('.overflow-x-auto'), p = w.querySelector('.t2-scroll-prev'), n = w.querySelector('.t2-scroll-next');
  if (!s) return;
  var a = 320, r = document.documentElement.dir === 'rtl';
  if (p) p.onclick = function(){ s.scrollBy({left: r ? a : -a, behavior:'smooth'}); };
  if (n) n.onclick = function(){ s.scrollBy({left: r ? -a : a, behavior:'smooth'}); };
  var tx = 0;
  s.addEventListener('touchstart', function(e){ tx = e.changedTouches[0].screenX; }, {passive:true});
  s.addEventListener('touchend', function(e){
    var d = tx - e.changedTouches[0].screenX;
    if (Math.abs(d) > 50) s.scrollBy({left: ((d>0&&!r)||(d<0&&r)) ? a : -a, behavior:'smooth'});
  }, {passive:true});
});
</script>
