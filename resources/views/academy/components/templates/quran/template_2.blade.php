@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCircles = $academy->quran_show_circles ?? true;
    $showTeachers = $academy->quran_show_teachers ?? true;

    $circleItems = $quranCircles->take(6);
    $teacherItems = $quranTeachers->take(6);
@endphp

<section id="quran" class="py-16 sm:py-20 lg:py-24 scroll-mt-20" style="background: {{ $gradientFromHex }}06;">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12 sm:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 mb-3">{{ $heading ?? __('academy.quran_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">{{ $subheading }}</p>
      @endif
    </div>

    {{-- Individual Quran Teachers --}}
    @if($showTeachers)
    <div class="mb-12 lg:mb-16">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-1.5 h-8 rounded-full" style="background: {{ $gradientToHex }};"></div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.quran_section.individual_title') }}</h3>
      </div>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($teacherItems as $teacher)
        @php
          $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
          $teacherName = $teacher->user->full_name ?? $teacher->user->name ?? '';
          $qualification = $teacher->educational_qualification;
          $qualificationLabel = $qualification instanceof \App\Enums\EducationalQualification
              ? $qualification->label()
              : ($qualification ? \App\Enums\EducationalQualification::getLabel($qualification) : null);
        @endphp
        <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
           class="group flex items-center gap-3.5 bg-white p-3.5 transition-all duration-200 hover:translate-x-1 ltr:hover:-translate-x-1"
           style="border-inline-start: 6px solid {{ $gradientToHex }};">
          <div class="flex-shrink-0">
            <x-avatar :user="$teacher" size="sm" userType="quran_teacher"
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
              @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                @php
                  $displayDays = array_slice($teacher->available_days, 0, 2);
                  $dayLabels = array_map(fn($d) => \App\Enums\WeekDays::from($d)->label(), $displayDays);
                @endphp
                {{ implode('، ', $dayLabels) }}
                @if(count($teacher->available_days) > 2)+{{ count($teacher->available_days) - 2 }}@endif
              </span>
              @endif
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($quranTeachers->count() > 0)
      <div class="mt-6 text-center">
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-10">
        <p class="text-sm text-gray-500">{{ __('academy.quran_section.no_teachers_title') }}</p>
      </div>
      @endif
    </div>
    @endif

    {{-- Group Quran Circles --}}
    @if($showCircles)
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="w-1.5 h-8 rounded-full" style="background: {{ $gradientFromHex }};"></div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.quran_section.group_title') }}</h3>
      </div>

      @if($circleItems->count() > 0)
      <div class="grid md:grid-cols-2 gap-4">
        @foreach($circleItems as $circle)
        @php
          $isEnrolled = in_array($circle->id, $enrolledCircleIds ?? []);
          $isFull = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL
                    || ($circle->enrolled_students >= $circle->max_students && $circle->max_students > 0);
          $isOpen = !$isEnrolled && $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN && !$isFull;
          $fillPercent = $circle->max_students > 0 ? round($circle->students_count / $circle->max_students * 100) : 0;
        @endphp
        <div class="group overflow-hidden rounded-xl transition-all duration-200 hover:shadow-lg" style="box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
          {{-- Dark header --}}
          <div class="px-5 py-3.5 flex items-center justify-between gap-3" style="background: {{ $gradientFromHex }}e6;">
            <h4 class="text-sm sm:text-base font-bold text-white truncate">{{ $circle->name }}</h4>
            <span class="flex-shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full
              {{ $isEnrolled ? 'bg-white/25 text-white' : ($isOpen ? 'bg-white/25 text-white' : 'bg-black/20 text-white/70') }}">
              @if($isEnrolled) {{ __('academy.cards.enrolled') }}
              @elseif($isOpen) {{ __('academy.cards.open') }}
              @elseif($isFull) {{ __('academy.cards.full') }}
              @endif
            </span>
          </div>

          {{-- White body --}}
          <div class="bg-white p-5">
            @if($circle->quranTeacher)
            <div class="flex items-center gap-2 mb-3 text-sm text-gray-700">
              <i class="ri-user-star-line" style="color: {{ $gradientFromHex }};"></i>
              <span class="font-medium truncate">{{ $circle->quranTeacher->full_name }}</span>
            </div>
            @endif

            <div class="flex items-center flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500 mb-4">
              <div class="flex items-center gap-1.5">
                <i class="ri-group-line"></i>
                <span class="font-semibold text-gray-700">{{ $circle->students_count }}/{{ $circle->max_students }}</span>
                <div class="w-14 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full" style="width: {{ $fillPercent }}%; background: {{ $gradientFromHex }};"></div>
                </div>
              </div>
              @if($circle->schedule_days_text)
              <span class="flex items-center gap-1"><i class="ri-calendar-line"></i> {{ $circle->schedule_days_text }}</span>
              @endif
              @if($circle->memorization_level)
              <span class="flex items-center gap-1"><i class="ri-bar-chart-line"></i> {{ $circle->memorization_level_text }}</span>
              @endif
            </div>

            <div class="flex items-center justify-between gap-3">
              @if($circle->monthly_fee)
              <span class="text-lg font-black" style="color: {{ $gradientFromHex }};">{{ number_format($circle->monthly_fee, 2) }} <span class="text-xs font-medium text-gray-400">{{ getCurrencySymbol() }}/{{ __('academy.cards.per_month') }}</span></span>
              @endif
              <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain, 'circleId' => $circle->id]) }}"
                 class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white ms-auto transition-all hover:opacity-90"
                 style="background: {{ $gradientFromHex }};">
                {{ __('academy.cards.view_details') }}
                <i class="ri-arrow-left-s-line ltr:rotate-180"></i>
              </a>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      @if($quranCircles->count() > 0)
      <div class="mt-6 text-center">
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-10">
        <p class="text-sm text-gray-500">{{ __('academy.quran_section.no_circles_title') }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
