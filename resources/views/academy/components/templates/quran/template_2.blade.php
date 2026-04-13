@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $showCircles = $academy->quran_show_circles ?? true;
    $showTeachers = $academy->quran_show_teachers ?? true;

    $circleItems = $quranCircles->take(6);
    $teacherItems = $quranTeachers->take(6);
@endphp

<!-- Quran Section - Template 2: Dual Sub-sections Design -->
<section id="quran" class="py-16 sm:py-20 lg:py-24 scroll-mt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-10 sm:mb-14">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">{{ $heading ?? __('academy.quran_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    {{-- Sub-section A: Individual Quran Teachers --}}
    @if($showTeachers)
    <div class="rounded-2xl p-6 sm:p-8 lg:p-10 mb-8 lg:mb-12" style="background: linear-gradient(135deg, {{ $gradientToHex }}0d, {{ $gradientToHex }}08, white);">
      <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $gradientToHex }}20;">
            <i class="ri-user-star-line text-base" style="color: {{ $gradientToHex }};"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.quran_section.individual_title') }}</h3>
        </div>
        <p class="text-sm text-gray-500 ms-11">{{ __('academy.quran_section.individual_subtitle') }}</p>
      </div>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
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
           class="group flex items-start gap-3 bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm transition-all duration-200 hover:shadow-md hover:border-gray-200">
          {{-- Avatar --}}
          <div class="flex-shrink-0">
            <x-avatar :user="$teacher" size="sm" userType="quran_teacher"
                       :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
          </div>

          {{-- Info --}}
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2 mb-1">
              <h4 class="text-sm font-bold text-gray-900 truncate">{{ $teacherName }}</h4>
              @if($rating > 0)
              <div class="flex items-center gap-0.5 flex-shrink-0">
                <i class="ri-star-fill text-xs text-amber-400"></i>
                <span class="text-xs font-medium text-gray-600">{{ number_format($rating, 1) }}</span>
              </div>
              @endif
            </div>

            @if($qualificationLabel)
            <span class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded-md mb-1.5"
                  style="background-color: {{ $gradientToHex }}15; color: {{ $gradientToHex }};">{{ $qualificationLabel }}</span>
            @endif

            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-500">
              @if($teacher->teaching_experience_years)
              <span class="flex items-center gap-1">
                <i class="ri-briefcase-line"></i>
                {{ __('academy.cards.experience_years', ['years' => $teacher->teaching_experience_years]) }}
              </span>
              @endif
              @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
              <span class="flex items-center gap-1">
                <i class="ri-calendar-line"></i>
                @php
                  $displayDays = array_slice($teacher->available_days, 0, 2);
                  $dayLabels = array_map(fn($d) => \App\Enums\WeekDays::from($d)->label(), $displayDays);
                @endphp
                {{ implode('، ', $dayLabels) }}
                @if(count($teacher->available_days) > 2)
                  <span class="font-medium" style="color: {{ $gradientToHex }};">+{{ count($teacher->available_days) - 2 }}</span>
                @endif
              </span>
              @endif
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($quranTeachers->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-8">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-user-star-line text-xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">{{ __('academy.quran_section.no_teachers_title') }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ __('academy.quran_section.no_teachers_message') }}</p>
      </div>
      @endif
    </div>
    @endif

    {{-- Sub-section B: Group Quran Circles --}}
    @if($showCircles)
    <div class="rounded-2xl p-6 sm:p-8 lg:p-10" style="background: linear-gradient(135deg, {{ $gradientFromHex }}0d, {{ $gradientFromHex }}08, white);">
      <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $gradientFromHex }}20;">
            <i class="ri-group-line text-base" style="color: {{ $gradientFromHex }};"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.quran_section.group_title') }}</h3>
        </div>
        <p class="text-sm text-gray-500 ms-11">{{ __('academy.quran_section.group_subtitle') }}</p>
      </div>

      @if($circleItems->count() > 0)
      <div class="grid md:grid-cols-2 gap-4 sm:gap-5">
        @foreach($circleItems as $circle)
        @php
          $isEnrolled = in_array($circle->id, $enrolledCircleIds ?? []);
          $isFull = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL
                    || ($circle->enrolled_students >= $circle->max_students && $circle->max_students > 0);
          $isOpen = !$isEnrolled && $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN && !$isFull;
          $fillPercent = $circle->max_students > 0 ? round($circle->students_count / $circle->max_students * 100) : 0;
        @endphp
        <div class="group bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:border-gray-200 flex">
          {{-- Accent Bar --}}
          <div class="w-1 flex-shrink-0" style="background: linear-gradient(to bottom, {{ $gradientFromHex }}, {{ $gradientFromHex }}80);"></div>

          {{-- Content --}}
          <div class="flex-1 p-4 sm:p-5">
            {{-- Header: Name + Status --}}
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="min-w-0 flex-1">
                <h4 class="text-sm sm:text-base font-bold text-gray-900 truncate">{{ $circle->name }}</h4>
                @if($circle->description)
                <p class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ $circle->description }}</p>
                @endif
              </div>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0
                {{ $isEnrolled ? 'bg-green-100 text-green-700' : ($isOpen ? 'bg-blue-100 text-blue-700' : ($isFull ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) }}">
                @if($isEnrolled)
                  {{ __('academy.cards.enrolled') }}
                @elseif($isOpen)
                  {{ __('academy.cards.open') }}
                @elseif($isFull)
                  {{ __('academy.cards.full') }}
                @endif
              </span>
            </div>

            {{-- Teacher --}}
            @if($circle->quranTeacher)
            <div class="flex items-center gap-2 mb-3 text-xs text-gray-600">
              <i class="ri-user-star-line" style="color: {{ $gradientFromHex }};"></i>
              <span class="truncate">{{ $circle->quranTeacher->full_name }}</span>
            </div>
            @endif

            {{-- Info Grid --}}
            <div class="flex items-center flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500 mb-3">
              {{-- Students --}}
              <div class="flex items-center gap-1.5">
                <i class="ri-group-line"></i>
                <span>{{ $circle->students_count }}/{{ $circle->max_students }}</span>
                <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all" style="width: {{ $fillPercent }}%; background-color: {{ $gradientFromHex }};"></div>
                </div>
              </div>

              {{-- Schedule --}}
              @if($circle->schedule_days_text)
              <div class="flex items-center gap-1">
                <i class="ri-calendar-line"></i>
                <span class="truncate max-w-[120px]">{{ $circle->schedule_days_text }}</span>
              </div>
              @endif

              {{-- Level --}}
              @if($circle->memorization_level)
              <div class="flex items-center gap-1">
                <i class="ri-bar-chart-line"></i>
                <span>{{ $circle->memorization_level_text }}</span>
              </div>
              @endif
            </div>

            {{-- Footer: Price + CTA --}}
            <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-50">
              @if($circle->monthly_fee)
              <div>
                <span class="text-sm font-bold" style="color: {{ $gradientFromHex }};">{{ number_format($circle->monthly_fee, 2) }} {{ getCurrencySymbol() }}</span>
                <span class="text-[10px] text-gray-400">/ {{ __('academy.cards.per_month') }}</span>
              </div>
              @else
              <div></div>
              @endif

              <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain, 'circleId' => $circle->id]) }}"
                 class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all duration-200 hover:opacity-90"
                 style="background-color: {{ $gradientFromHex }};">
                {{ __('academy.cards.view_details') }}
                <i class="ri-arrow-left-s-line text-sm ltr:rotate-180"></i>
              </a>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      @if($quranCircles->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-8">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-group-line text-xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">{{ __('academy.quran_section.no_circles_title') }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ __('academy.quran_section.no_circles_message') }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>
