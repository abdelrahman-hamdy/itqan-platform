@php
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors       = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex   = $hexColors['to'];

    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex950 = $brandColor->getHexValue(950);

    $showCircles  = $academy->quran_show_circles ?? true;
    $showTeachers = $academy->quran_show_teachers ?? true;

    $circleItems  = $quranCircles->take(6);
    $teacherItems = $quranTeachers->take(6);
@endphp

<section id="quran" class="py-20 sm:py-24 lg:py-28 scroll-mt-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Section header --}}
    <div class="max-w-2xl mb-14 sm:mb-16">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-[3px] rounded-full" style="background: {{ $gradientFromHex }};"></div>
        <span class="text-xs font-bold uppercase text-gray-400">{{ __('academy.nav.sections.quran') }}</span>
      </div>
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 leading-tight">{{ $heading ?? __('academy.quran_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base text-gray-400 mt-3 leading-relaxed">{{ $subheading }}</p>
      @endif
    </div>

    {{-- ═══ Individual Teachers ═══ --}}
    @if($showTeachers)
    <div class="mb-14 lg:mb-18">
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('academy.quran_section.individual_title') }}</h3>
      <p class="text-sm text-gray-400 mb-6">{{ __('academy.quran_section.individual_subtitle') }}</p>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($teacherItems as $teacher)
        @php
          $rating      = $teacher->average_rating ?? $teacher->rating ?? 0;
          $tName       = $teacher->user->full_name ?? $teacher->user->name ?? '';
          $qual        = $teacher->educational_qualification;
          $qualLabel   = $qual instanceof \App\Enums\EducationalQualification
                           ? $qual->label()
                           : ($qual ? \App\Enums\EducationalQualification::getLabel($qual) : null);
        @endphp
        <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
           class="group flex items-center gap-3 p-3 bg-gray-50/80 hover:bg-white transition-all duration-200"
           style="border-inline-start: 4px solid {{ $gradientToHex }};">
          <x-avatar :user="$teacher" size="sm" userType="quran_teacher"
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
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($quranTeachers->count() > 0)
      <div class="mt-5"><a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" class="text-sm font-bold transition-all hover:gap-3 inline-flex items-center gap-2" style="color: {{ $gradientToHex }};">{{ __('academy.actions.view_more') }} <i class="ri-arrow-left-line ltr:rotate-180"></i></a></div>
      @endif
      @else
      <p class="text-sm text-gray-400 py-8 text-center">{{ __('academy.quran_section.no_teachers_title') }}</p>
      @endif
    </div>
    @endif

    {{-- ═══ Group Circles ═══ --}}
    @if($showCircles)
    <div>
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('academy.quran_section.group_title') }}</h3>
      <p class="text-sm text-gray-400 mb-6">{{ __('academy.quran_section.group_subtitle') }}</p>

      @if($circleItems->count() > 0)
      <div class="grid md:grid-cols-2 gap-4">
        @foreach($circleItems as $circle)
        @php
          $isEnrolled  = in_array($circle->id, $enrolledCircleIds ?? []);
          $isFull      = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL
                         || ($circle->enrolled_students >= $circle->max_students && $circle->max_students > 0);
          $isOpen      = !$isEnrolled && $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN && !$isFull;
          $fillPct     = $circle->max_students > 0 ? round($circle->students_count / $circle->max_students * 100) : 0;
          $statusColor = $isEnrolled ? '#22C55E' : ($isOpen ? $gradientFromHex : ($isFull ? '#F59E0B' : '#9CA3AF'));
        @endphp
        <div class="group rounded-sm overflow-hidden transition-shadow duration-300 hover:shadow-lg" style="box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
          {{-- Dark header bar --}}
          <div class="px-5 py-3 flex items-center justify-between gap-3" style="background: {{ $brandHex950 }};">
            <h4 class="text-sm font-bold text-white truncate">{{ $circle->name }}</h4>
            <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-0.5 rounded-sm" style="background: {{ $statusColor }}25; color: {{ $statusColor }};">
              @if($isEnrolled) {{ __('academy.cards.enrolled') }}
              @elseif($isOpen) {{ __('academy.cards.open') }}
              @elseif($isFull) {{ __('academy.cards.full') }}
              @endif
            </span>
          </div>

          {{-- White body --}}
          <div class="bg-white p-5">
            @if($circle->quranTeacher)
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
              <div class="w-6 h-6 rounded-full flex items-center justify-center" style="background: {{ $gradientFromHex }}12;">
                <i class="ri-user-star-line text-xs" style="color: {{ $gradientFromHex }};"></i>
              </div>
              <span class="font-medium truncate">{{ $circle->quranTeacher->full_name }}</span>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-3 mb-4">
              <div>
                <span class="block text-[10px] uppercase text-gray-400 mb-0.5">{{ __('components.circle.labels.students_count') }}</span>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-black text-gray-900">{{ $circle->students_count }}<span class="text-gray-300 font-normal">/{{ $circle->max_students }}</span></span>
                  <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ $fillPct }}%; background:{{ $gradientFromHex }};"></div>
                  </div>
                </div>
              </div>
              @if($circle->schedule_days_text)
              <div>
                <span class="block text-[10px] uppercase text-gray-400 mb-0.5">{{ __('components.circle.labels.schedule') }}</span>
                <span class="text-sm font-semibold text-gray-700 truncate block">{{ $circle->schedule_days_text }}</span>
              </div>
              @endif
            </div>

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
              @if($circle->monthly_fee)
              <div>
                <span class="text-xl font-black" style="color: {{ $gradientFromHex }};">{{ number_format($circle->monthly_fee, 0) }}</span>
                <span class="text-xs text-gray-400 ms-0.5">{{ getCurrencySymbol() }}/{{ __('academy.cards.per_month') }}</span>
              </div>
              @endif
              <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain, 'circleId' => $circle->id]) }}"
                 class="inline-flex items-center gap-1.5 px-4 py-2 rounded-sm text-xs font-bold text-white ms-auto transition-opacity hover:opacity-80"
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
      <div class="mt-5"><a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}" class="text-sm font-bold transition-all hover:gap-3 inline-flex items-center gap-2" style="color: {{ $gradientFromHex }};">{{ __('academy.actions.view_more') }} <i class="ri-arrow-left-line ltr:rotate-180"></i></a></div>
      @endif
      @else
      <p class="text-sm text-gray-400 py-8 text-center">{{ __('academy.quran_section.no_circles_title') }}</p>
      @endif
    </div>
    @endif
  </div>
</section>
