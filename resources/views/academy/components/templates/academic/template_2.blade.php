@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors       = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex   = $hexColors['to'];

    $brandColor  = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50  = $brandColor->getHexValue(50);
    $brandHex950 = $brandColor->getHexValue(950);

    $showCourses  = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;

    $courseItems  = $interactiveCourses->take(6);
    $teacherItems = $academicTeachers->take(6);
@endphp

<section id="academic" class="py-20 sm:py-24 lg:py-28 scroll-mt-20" style="background: {{ $brandHex50 }};">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Section header --}}
    <div class="max-w-2xl mb-14 sm:mb-16">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-[3px] rounded-full" style="background: {{ $gradientFromHex }};"></div>
        <span class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400">{{ __('academy.nav.sections.academic') }}</span>
      </div>
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 leading-tight">{{ $heading ?? __('academy.academic_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base text-gray-400 mt-3 leading-relaxed">{{ $subheading }}</p>
      @endif
    </div>

    {{-- ═══ Interactive Courses ═══ --}}
    @if($showCourses)
    <div class="mb-14 lg:mb-18">
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('academy.academic_section.interactive_courses_title') }}</h3>
      <p class="text-sm text-gray-400 mb-8">{{ __('academy.academic_section.interactive_courses_subtitle') }}</p>

      @if($courseItems->count() > 0)
      <div class="space-y-5">
        @foreach($courseItems as $course)
        <article class="group bg-white rounded-sm overflow-hidden flex flex-col md:flex-row transition-shadow duration-300 hover:shadow-xl" style="box-shadow: 0 1px 12px rgba(0,0,0,0.05);">

          {{-- Colored side panel --}}
          <div class="relative md:w-[280px] lg:w-[320px] shrink-0 overflow-hidden" style="background: {{ $gradientFromHex }};">
            {{-- Angled edge on desktop --}}
            <div class="hidden md:block absolute inset-y-0 end-0 w-10 bg-white" style="clip-path: polygon(100% 0, 100% 100%, 0 100%);"></div>

            <div class="relative z-[1] flex flex-col items-center justify-center text-center p-6 h-48 md:h-full md:min-h-[220px]">
              @if($course->subject)
              <span class="text-xs font-bold uppercase tracking-[0.15em] text-white/60 mb-3">{{ $course->subject->name }}</span>
              @endif
              <div class="w-14 h-14 rounded-sm bg-white/15 backdrop-blur-sm flex items-center justify-center mb-3">
                <i class="ri-slideshow-3-line text-2xl text-white"></i>
              </div>
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-sm {{ $course->is_published ? 'bg-white/20 text-white' : 'bg-black/20 text-white/50' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $course->is_published ? 'bg-green-300' : 'bg-gray-400' }}"></span>
                {{ $course->is_published ? __('components.cards.interactive_course.status_available') : __('components.cards.interactive_course.status_unavailable') }}
              </span>
            </div>
          </div>

          {{-- Content --}}
          <div class="flex-1 p-6 sm:p-7 flex flex-col">
            <div class="flex items-start justify-between gap-4 mb-2">
              <h4 class="text-lg sm:text-xl font-black text-gray-900 leading-tight line-clamp-2">{{ $course->title }}</h4>
              @if(($course->avg_rating ?? 0) > 0)
              <span class="shrink-0 flex items-center gap-1 text-sm font-black" style="color: {{ $gradientFromHex }};">
                <i class="ri-star-fill text-amber-400 text-xs"></i>{{ number_format($course->avg_rating, 1) }}
              </span>
              @endif
            </div>

            @if($course->description)
            <p class="text-sm text-gray-400 line-clamp-2 mb-5">{{ $course->description }}</p>
            @endif

            {{-- Meta line --}}
            <div class="text-sm text-gray-500 mb-4 flex items-center flex-wrap gap-x-1">
              @if($course->assignedTeacher)
              <span class="font-semibold text-gray-700">{{ $course->assignedTeacher->full_name }}</span>
              <span class="text-gray-300 mx-1">/</span>
              @endif
              @if($course->gradeLevel)
              <span>{{ $course->gradeLevel->getDisplayName() }}</span>
              <span class="text-gray-300 mx-1">/</span>
              @endif
              <span>{{ __('academy.cards.sessions', ['count' => $course->total_sessions ?? 0]) }}</span>
              <span class="text-gray-300 mx-1">/</span>
              <span>{{ __('academy.cards.weeks', ['count' => $course->duration_weeks ?? 0]) }}</span>
            </div>

            @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
            <div class="flex items-center gap-2 flex-wrap mb-5">
              @foreach(array_slice($course->schedule, 0, 4) as $item)
              <span class="text-[11px] font-semibold px-2 py-1 bg-gray-50 text-gray-500 rounded-sm">
                {{ is_array($item) ? ($item['day'] ?? '') : ($item->day ?? '') }}@if(is_array($item) ? ($item['time'] ?? null) : ($item->time ?? null)): {{ is_array($item) ? $item['time'] : $item->time }}@endif
              </span>
              @endforeach
            </div>
            @endif

            <div class="flex-grow"></div>

            {{-- Price + CTA --}}
            <div class="flex items-center justify-between gap-4 pt-5 border-t border-gray-100">
              @if($course->student_price > 0)
              <div class="flex items-baseline gap-1.5">
                @if($course->hasDiscount())
                <span class="text-2xl font-black" style="color: {{ $gradientFromHex }};">{{ number_format($course->sale_price) }}</span>
                <span class="text-sm text-gray-300 line-through">{{ number_format($course->student_price) }}</span>
                @else
                <span class="text-2xl font-black" style="color: {{ $gradientFromHex }};">{{ number_format($course->student_price) }}</span>
                @endif
                <span class="text-xs text-gray-400">{{ getCurrencySymbol() }}</span>
              </div>
              @else
              <span class="text-lg font-black text-green-600">{{ __('academy.cards.free') }}</span>
              @endif

              <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
                 class="inline-flex items-center gap-2 px-5 py-2.5 rounded-sm text-sm font-bold transition-all duration-200 t2-ghost-btn"
                 style="color: {{ $gradientFromHex }}; border: 2px solid {{ $gradientFromHex }}; --t2-fill: {{ $gradientFromHex }};">
                {{ __('academy.cards.view_details') }}
                <i class="ri-arrow-left-s-line text-base ltr:rotate-180"></i>
              </a>
            </div>
          </div>
        </article>
        @endforeach
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="mt-6"><a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}" class="text-sm font-bold transition-all hover:gap-3 inline-flex items-center gap-2" style="color: {{ $gradientFromHex }};">{{ __('academy.actions.view_more') }} <i class="ri-arrow-left-line ltr:rotate-180"></i></a></div>
      @endif
      @else
      <p class="text-sm text-gray-400 py-8 text-center">{{ __('academy.academic_section.no_courses_title') }}</p>
      @endif
    </div>
    @endif

    {{-- ═══ Academic Teachers ═══ --}}
    @if($showTeachers)
    <div>
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('academy.academic_section.academic_teachers_title') }}</h3>
      <p class="text-sm text-gray-400 mb-6">{{ __('academy.academic_section.academic_teachers_subtitle') }}</p>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($teacherItems as $teacher)
        @php
          $rating    = $teacher->average_rating ?? $teacher->rating ?? 0;
          $tName     = $teacher->full_name ?? '';
          $qual      = $teacher->educational_qualification;
          $qualLabel = $qual instanceof \App\Enums\EducationalQualification
                         ? $qual->label()
                         : ($qual ? \App\Enums\EducationalQualification::getLabel($qual) : null);
          $tSubjects = $teacher->relationLoaded('subjects')
                         ? $teacher->subjects
                         : (($teacher->subject_ids && is_array($teacher->subject_ids))
                             ? \App\Models\AcademicSubject::whereIn('id', $teacher->subject_ids)->limit(3)->get()
                             : collect());
        @endphp
        <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
           class="group flex items-center gap-3 p-3 bg-white hover:bg-gray-50/50 transition-all duration-200"
           style="border-inline-start: 4px solid {{ $gradientToHex }};">
          <x-avatar :user="$teacher" size="sm" userType="academic_teacher"
                     :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" class="shrink-0" />
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <span class="text-sm font-bold text-gray-900 truncate">{{ $tName }}</span>
              @if($rating > 0)
              <span class="shrink-0 text-[11px] font-black px-1.5 py-0.5 rounded-sm" style="background: {{ $gradientToHex }}12; color: {{ $gradientToHex }};">{{ number_format($rating, 1) }}</span>
              @endif
            </div>
            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
              @if($qualLabel)
              <span class="text-[10px] font-semibold px-1.5 py-0.5 bg-gray-200/60 text-gray-500 rounded-sm">{{ $qualLabel }}</span>
              @endif
              @if($teacher->teaching_experience_years)
              <span class="text-[10px] text-gray-400">{{ __('academy.cards.experience_years', ['years' => $teacher->teaching_experience_years]) }}</span>
              @endif
              @if($tSubjects->isNotEmpty())
              <span class="text-[10px] text-gray-400">{{ $tSubjects->first()->name }}@if($tSubjects->count() > 1) +{{ $tSubjects->count() - 1 }}@endif</span>
              @endif
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($academicTeachers->count() > 0)
      <div class="mt-5"><a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}" class="text-sm font-bold transition-all hover:gap-3 inline-flex items-center gap-2" style="color: {{ $gradientToHex }};">{{ __('academy.actions.view_more') }} <i class="ri-arrow-left-line ltr:rotate-180"></i></a></div>
      @endif
      @else
      <p class="text-sm text-gray-400 py-8 text-center">{{ __('academy.academic_section.no_teachers_title') }}</p>
      @endif
    </div>
    @endif
  </div>
</section>

<style>
  .t2-ghost-btn {
    background: transparent;
    transition: background .2s, color .2s;
  }
  .t2-ghost-btn:hover {
    background: var(--t2-fill);
    color: #fff;
  }
</style>
