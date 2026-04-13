@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex500 = $brandColor->getHexValue(500);

    $showCourses = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;

    $courseItems = $interactiveCourses->take(6);
    $teacherItems = $academicTeachers->take(6);
@endphp

<section id="academic" class="py-16 sm:py-20 lg:py-24 scroll-mt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12 sm:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 mb-3">{{ $heading ?? __('academy.academic_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">{{ $subheading }}</p>
      @endif
    </div>

    {{-- Interactive Courses — Magazine-style full-width cards --}}
    @if($showCourses)
    <div class="mb-12 lg:mb-16">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-1.5 h-8 rounded-full" style="background: {{ $gradientFromHex }};"></div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.academic_section.interactive_courses_title') }}</h3>
      </div>

      @if($courseItems->count() > 0)
      <div class="space-y-5">
        @foreach($courseItems as $course)
        <div class="group overflow-hidden rounded-xl transition-all duration-300 hover:shadow-xl flex flex-col md:flex-row" style="box-shadow: 0 2px 16px rgba(0,0,0,0.06);">
          {{-- Solid colored block with angled edge --}}
          <div class="relative md:w-[300px] lg:w-[340px] flex-shrink-0 overflow-hidden" style="background: {{ $gradientFromHex }};">
            {{-- Angled edge (desktop only) --}}
            <div class="hidden md:block absolute inset-y-0 end-0 w-12 bg-white" style="clip-path: polygon(100% 0, 100% 100%, 0 100%); z-index: 2;"></div>

            <div class="relative z-[1] flex flex-col items-center justify-center h-48 md:h-full md:min-h-[240px] p-6 text-center">
              <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-4">
                <i class="ri-slideshow-3-line text-3xl text-white"></i>
              </div>
              @if($course->subject)
              <p class="text-sm font-bold text-white/90 mb-2">{{ $course->subject->name }}</p>
              @endif
              <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold
                {{ $course->is_published ? 'bg-white/20 text-white' : 'bg-black/20 text-white/60' }}">
                <span class="w-1.5 h-1.5 rounded-full me-1.5 {{ $course->is_published ? 'bg-green-300' : 'bg-gray-400' }}"></span>
                {{ $course->is_published ? __('components.cards.interactive_course.status_available') : __('components.cards.interactive_course.status_unavailable') }}
              </span>
            </div>
          </div>

          {{-- Content side --}}
          <div class="flex-1 bg-white p-5 sm:p-7 flex flex-col">
            {{-- Title + Rating --}}
            <div class="flex items-start justify-between gap-3 mb-3">
              <h4 class="text-lg sm:text-xl font-black text-gray-900 line-clamp-2 leading-tight">{{ $course->title }}</h4>
              @if(($course->avg_rating ?? 0) > 0)
              <span class="flex-shrink-0 text-sm font-black px-2 py-1 rounded-lg" style="background: {{ $gradientFromHex }}12; color: {{ $gradientFromHex }};">
                <i class="ri-star-fill text-amber-400 me-0.5"></i>{{ number_format($course->avg_rating, 1) }}
              </span>
              @endif
            </div>

            @if($course->description)
            <p class="text-sm text-gray-500 line-clamp-2 mb-5 leading-relaxed">{{ $course->description }}</p>
            @endif

            {{-- Inline meta with dot separators --}}
            <div class="text-sm text-gray-600 mb-5 leading-relaxed">
              @if($course->assignedTeacher)
              <span class="font-semibold text-gray-800">{{ $course->assignedTeacher->full_name }}</span>
              @endif
              @if($course->gradeLevel)
              <span class="mx-2 text-gray-300">&bull;</span>
              <span>{{ $course->gradeLevel->getDisplayName() }}</span>
              @endif
              <span class="mx-2 text-gray-300">&bull;</span>
              <span>{{ __('academy.cards.sessions', ['count' => $course->total_sessions ?? 0]) }}</span>
              <span class="mx-2 text-gray-300">&bull;</span>
              <span>{{ __('academy.cards.weeks', ['count' => $course->duration_weeks ?? 0]) }}</span>
            </div>

            {{-- Schedule --}}
            @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
            <div class="flex items-center gap-2 flex-wrap mb-5">
              @foreach(array_slice($course->schedule, 0, 4) as $item)
              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-gray-50 text-gray-600">
                {{ is_array($item) ? ($item['day'] ?? '') : ($item->day ?? '') }}@if(is_array($item) ? ($item['time'] ?? null) : ($item->time ?? null)): {{ is_array($item) ? $item['time'] : $item->time }}@endif
              </span>
              @endforeach
            </div>
            @endif

            <div class="flex-grow"></div>

            {{-- Price + Ghost CTA --}}
            <div class="flex items-center justify-between gap-4 pt-5 border-t border-gray-100">
              @if($course->student_price > 0)
              <div>
                @if($course->hasDiscount())
                <span class="text-2xl font-black" style="color: {{ $gradientFromHex }};">{{ number_format($course->sale_price) }}</span>
                <span class="text-sm text-gray-400 line-through ms-1">{{ number_format($course->student_price) }}</span>
                <span class="text-xs text-gray-400 ms-0.5">{{ getCurrencySymbol() }}</span>
                @else
                <span class="text-2xl font-black" style="color: {{ $gradientFromHex }};">{{ number_format($course->student_price) }}</span>
                <span class="text-xs text-gray-400 ms-1">{{ getCurrencySymbol() }}</span>
                @endif
              </div>
              @else
              <span class="text-xl font-black text-green-600">{{ __('academy.cards.free') }}</span>
              @endif

              <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
                 class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 hover:text-white"
                 style="color: {{ $gradientFromHex }}; border: 2px solid {{ $gradientFromHex }};"
                 onmouseover="this.style.background='{{ $gradientFromHex }}'; this.style.color='white'"
                 onmouseout="this.style.background='transparent'; this.style.color='{{ $gradientFromHex }}'">
                {{ __('academy.cards.view_details') }}
                <i class="ri-arrow-left-s-line text-base ltr:rotate-180"></i>
              </a>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-10">
        <p class="text-sm text-gray-500">{{ __('academy.academic_section.no_courses_title') }}</p>
      </div>
      @endif
    </div>
    @endif

    {{-- Academic Teachers — Thick accent bar cards --}}
    @if($showTeachers)
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="w-1.5 h-8 rounded-full" style="background: {{ $gradientToHex }};"></div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.academic_section.academic_teachers_title') }}</h3>
      </div>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($teacherItems as $teacher)
        @php
          $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
          $teacherName = $teacher->full_name ?? '';
          $qualification = $teacher->educational_qualification;
          $qualificationLabel = $qualification instanceof \App\Enums\EducationalQualification
              ? $qualification->label()
              : ($qualification ? \App\Enums\EducationalQualification::getLabel($qualification) : null);
          $teacherSubjects = $teacher->relationLoaded('subjects')
              ? $teacher->subjects
              : (($teacher->subject_ids && is_array($teacher->subject_ids))
                  ? \App\Models\AcademicSubject::whereIn('id', $teacher->subject_ids)->limit(3)->get()
                  : collect());
        @endphp
        <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
           class="group flex items-center gap-3.5 bg-white p-3.5 transition-all duration-200 hover:translate-x-1 ltr:hover:-translate-x-1"
           style="border-inline-start: 6px solid {{ $gradientToHex }};">
          <div class="flex-shrink-0">
            <x-avatar :user="$teacher" size="sm" userType="academic_teacher"
                       :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold text-gray-900 truncate">{{ $teacherName }}</h4>
              @if($rating > 0)
              <span class="flex-shrink-0 text-xs font-black px-1.5 py-0.5 rounded" style="background: {{ $gradientToHex }}15; color: {{ $gradientToHex }};">{{ number_format($rating, 1) }}</span>
              @endif
            </div>
            <div class="flex items-center flex-wrap gap-1.5 mt-1.5">
              @if($qualificationLabel)
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $qualificationLabel }}</span>
              @endif
              @if($teacher->teaching_experience_years)
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                {{ __('academy.cards.experience_years', ['years' => $teacher->teaching_experience_years]) }}
              </span>
              @endif
              @if($teacherSubjects->isNotEmpty())
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                {{ $teacherSubjects->first()->name }}
                @if($teacherSubjects->count() > 1)+{{ $teacherSubjects->count() - 1 }}@endif
              </span>
              @endif
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($academicTeachers->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-10">
        <p class="text-sm text-gray-500">{{ __('academy.academic_section.no_teachers_title') }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
