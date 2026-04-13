@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors       = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex   = $hexColors['to'];

    $brandColor  = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex950 = $brandColor->getHexValue(950);

    $showCourses  = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;

    $courseItems  = $interactiveCourses->take(6);
    $teacherItems = $academicTeachers->take(6);
@endphp

<section id="academic" class="py-20 sm:py-24 lg:py-28 scroll-mt-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Section header --}}
    <div class="max-w-2xl mb-14 sm:mb-16">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-[3px] rounded-full" style="background: {{ $gradientFromHex }};"></div>
        <span class="text-xs font-bold uppercase text-gray-400">{{ __('academy.nav.sections.academic') }}</span>
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
      <div class="space-y-6">
        @foreach($courseItems as $course)
        @php $courseImage = $course->featured_image ? \Illuminate\Support\Facades\Storage::url($course->featured_image) : null; @endphp
        <article class="group bg-white rounded-lg overflow-hidden flex flex-col md:flex-row transition-shadow duration-300 hover:shadow-xl" style="box-shadow: 0 1px 12px rgba(0,0,0,0.05);">

          {{-- Image / colored panel --}}
          <div class="relative md:w-[340px] lg:w-[400px] shrink-0 overflow-hidden">
            @if($courseImage)
            <img src="{{ $courseImage }}" alt="{{ $course->title }}" class="w-full h-52 md:h-full md:min-h-[280px] object-cover transition-transform duration-500 group-hover:scale-105">
            @else
            <div class="w-full h-52 md:h-full md:min-h-[280px] flex flex-col items-center justify-center text-center p-6 bg-blue-600">
              <div class="w-16 h-16 rounded-lg bg-white/15 backdrop-blur-sm flex items-center justify-center mb-3">
                <i class="ri-slideshow-3-line text-3xl text-white"></i>
              </div>
              @if($course->subject)
              <span class="text-sm font-bold text-white/70">{{ $course->subject->name }}</span>
              @endif
            </div>
            @endif
            {{-- Status badge --}}
            <div class="absolute top-3 start-3">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-lg backdrop-blur-sm {{ $course->is_published ? 'bg-white/90 text-green-700' : 'bg-white/90 text-gray-500' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $course->is_published ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                {{ $course->is_published ? __('components.cards.interactive_course.status_available') : __('components.cards.interactive_course.status_unavailable') }}
              </span>
            </div>
          </div>

          {{-- Content --}}
          <div class="flex-1 p-6 sm:p-7 lg:p-8 flex flex-col">
            {{-- Title + Rating --}}
            <div class="flex items-start justify-between gap-4 mb-3">
              <h4 class="text-xl sm:text-2xl font-bold text-gray-900 leading-snug line-clamp-2">{{ $course->title }}</h4>
              @if(($course->avg_rating ?? 0) > 0)
              <span class="shrink-0 flex items-center gap-1 text-sm font-semibold px-2 py-1 rounded-md bg-amber-50 text-amber-600">
                <i class="ri-star-fill text-amber-400 text-xs"></i>{{ number_format($course->avg_rating, 1) }}
              </span>
              @endif
            </div>

            @if($course->description)
            <p class="text-base text-gray-500 line-clamp-3 leading-7 mb-5">{{ $course->description }}</p>
            @endif

            {{-- Teacher --}}
            @if($course->assignedTeacher)
            <div class="flex items-center gap-3 mb-5">
              <x-avatar :user="$course->assignedTeacher" size="sm" userType="academic_teacher"
                         :gender="$course->assignedTeacher->gender ?? $course->assignedTeacher->user?->gender ?? 'male'" class="shrink-0" />
              <div class="min-w-0">
                <span class="text-sm font-semibold text-gray-800 block truncate">{{ $course->assignedTeacher->full_name }}</span>
                @if($course->assignedTeacher->teaching_experience_years)
                <span class="text-xs text-gray-400">{{ __('academy.cards.experience_years', ['years' => $course->assignedTeacher->teaching_experience_years]) }}</span>
                @endif
              </div>
            </div>
            @endif

            {{-- Info labels --}}
            <div class="flex items-center flex-wrap gap-2 mb-5">
              @if($course->subject)
              <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-md text-gray-600 bg-blue-50 border border-blue-100">
                <i class="ri-book-open-line text-sm text-blue-500"></i>
                {{ $course->subject->name }}
              </span>
              @endif
              @if($course->gradeLevel)
              <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-md text-gray-600 bg-blue-50 border border-blue-100">
                <i class="ri-graduation-cap-line text-sm text-blue-500"></i>
                {{ $course->gradeLevel->getDisplayName() }}
              </span>
              @endif
              <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-md bg-amber-50 text-amber-700 border border-amber-100">
                <i class="ri-vidicon-line text-sm text-amber-500"></i>
                {{ __('academy.cards.sessions', ['count' => $course->total_sessions ?? 0]) }}
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100">
                <i class="ri-calendar-check-line text-sm text-emerald-500"></i>
                {{ __('academy.cards.weeks', ['count' => $course->duration_weeks ?? 0]) }}
              </span>
            </div>

            {{-- Schedule --}}
            @php $formattedSchedule = $course->formatted_schedule; @endphp
            @if(count($formattedSchedule) > 0)
            <div class="flex items-center gap-1.5 flex-wrap mb-5">
              @foreach(array_slice($formattedSchedule, 0, 4) as $entry)
              <span class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-white text-blue-700 border border-blue-200">
                {{ $entry['day'] }}: {{ $entry['time'] }}
              </span>
              @endforeach
            </div>
            @endif

            <div class="flex-grow"></div>

            {{-- Price + CTA --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-5 border-t border-gray-100">
              @if($course->student_price > 0)
              <div>
                @if($course->hasDiscount())
                <div class="flex items-baseline gap-3">
                  <span class="text-3xl font-bold text-blue-600">{{ number_format($course->sale_price) }} {{ getCurrencySymbol() }}</span>
                  <span class="text-lg text-gray-300 line-through">{{ number_format($course->student_price) }} {{ getCurrencySymbol() }}</span>
                  <span class="text-xs font-semibold px-2 py-1 rounded bg-red-50 text-red-500 border border-red-100">-{{ round((1 - $course->sale_price / $course->student_price) * 100) }}%</span>
                </div>
                @else
                <span class="text-3xl font-bold text-blue-600">{{ number_format($course->student_price) }} {{ getCurrencySymbol() }}</span>
                @endif
              </div>
              @else
              <span class="text-2xl font-bold text-green-600">{{ __('academy.cards.free') }}</span>
              @endif

              <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
                 class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 rounded-lg text-sm font-semibold transition-colors duration-200 bg-blue-500 text-white hover:bg-blue-600">
                <i class="ri-information-line"></i>
                {{ __('academy.cards.view_details') }}
              </a>
            </div>
          </div>
        </article>
        @endforeach
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="mt-8 text-center">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg text-blue-600 bg-blue-50/60 transition-all duration-200 hover:bg-blue-100/80 hover:gap-3">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
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
              <span class="shrink-0 text-[11px] font-black px-1.5 py-0.5 rounded-lg" style="background: {{ $gradientToHex }}12; color: {{ $gradientToHex }};">{{ number_format($rating, 1) }}</span>
              @endif
            </div>
            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
              @if($qualLabel)
              <span class="text-[10px] font-semibold px-1.5 py-0.5 bg-gray-200/60 text-gray-500 rounded-lg">{{ $qualLabel }}</span>
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
      <div class="mt-8 text-center">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 hover:gap-3"
           style="color: {{ $gradientToHex }}; background: {{ $gradientToHex }}0a;" onmouseover="this.style.background='{{ $gradientToHex }}15'" onmouseout="this.style.background='{{ $gradientToHex }}0a'">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <p class="text-sm text-gray-400 py-8 text-center">{{ __('academy.academic_section.no_teachers_title') }}</p>
      @endif
    </div>
    @endif
  </div>
</section>

