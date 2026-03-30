@props(['teacher', 'academy', 'availablePackages' => collect()])

@php
  $isSubscribed = $teacher->is_subscribed ?? false;
  $subscription = $teacher->my_subscription ?? null;
  $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
  $studentsCount = $teacher->active_students_count ?? $teacher->total_students ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover flex flex-col overflow-hidden">
  {{-- Section 1: Colored Header — Avatar + Name + Rating --}}
  <div class="relative bg-amber-50 pb-6 pt-4 pe-4 sm:pe-6 ps-40 sm:ps-44">
    {{-- My Teacher Badge (top-end corner) --}}
    @if($isSubscribed)
    <span class="absolute top-3 end-3 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-500 text-white shadow-sm z-20">
      <i class="ri-check-line me-1"></i>
      {{ __('components.cards.quran_teacher.my_teacher') }}
    </span>
    @endif

    {{-- Avatar: vertically centered on bottom border, positioned at start side --}}
    <div class="absolute start-4 sm:start-6 -bottom-12 z-10">
      <div class="rounded-full border-4 border-white shadow-lg">
        <x-avatar
          :user="$teacher"
          size="lg"
          userType="quran_teacher"
          :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
          class="flex-shrink-0" />
      </div>
    </div>

    {{-- Name + Rating (inside header, beside avatar) --}}
    <h3 class="font-bold text-gray-900 text-lg leading-tight truncate">
      {{ $teacher->user->full_name ?? $teacher->user->name ?? __('components.cards.quran_teacher.default_name') }}
    </h3>
    <div class="flex items-center gap-1 mt-1">
      <x-reviews.star-rating
        :rating="$rating"
        :total-reviews="$teacher->total_reviews ?? null"
        size="sm"
      />
    </div>
  </div>

  {{-- Section 2: Main Info Area (pt-8 clears avatar overflow from header) --}}
  <div class="px-4 sm:px-6 pt-8 flex-1 flex flex-col">
    {{-- Key Details --}}
    <div class="space-y-2.5 mb-3">
      {{-- Qualification + Experience Row --}}
      <div class="flex flex-wrap items-center gap-2">
        @if($teacher->educational_qualification)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
          <i class="ri-graduation-cap-line me-1"></i>
          {{ $teacher->educational_qualification instanceof \App\Enums\EducationalQualification ? $teacher->educational_qualification->label() : \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}
        </span>
        @endif
        @if($teacher->teaching_experience_years)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
          <i class="ri-briefcase-line me-1 text-yellow-600"></i>
          {{ $teacher->teaching_experience_years }}
          {{ $teacher->teaching_experience_years == 1 ? __('components.cards.quran_teacher.year_experience') : __('components.cards.quran_teacher.years_experience') }}
        </span>
        @endif
      </div>

      {{-- Time Range --}}
      @if($teacher->available_time_start && $teacher->available_time_end)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-time-line me-1.5 text-yellow-600"></i>
        <span>{{ formatTimeArabic($teacher->available_time_start) }} - {{ formatTimeArabic($teacher->available_time_end) }}</span>
      </div>
      @endif

      {{-- Available Days --}}
      @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-calendar-line me-1.5 text-yellow-600"></i>
        @php
          $displayDays = array_slice($teacher->available_days, 0, 4);
          $dayNames = collect($displayDays)->map(function($day) {
            try {
              return \App\Enums\WeekDays::from($day)->label();
            } catch (\ValueError $e) {
              return $day;
            }
          })->join(' · ');
        @endphp
        <span>{{ $dayNames }}</span>
        @if(count($teacher->available_days) > 4)
          <span class="ms-1 text-xs text-yellow-600 font-medium">+{{ count($teacher->available_days) - 4 }}</span>
        @endif
      </div>
      @endif

      {{-- Students Count --}}
      @if($studentsCount > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line me-1.5 text-yellow-600"></i>
        <span>{{ $studentsCount }} {{ __('components.cards.quran_teacher.students_count') }}</span>
      </div>
      @endif
    </div>

    {{-- Bio --}}
    @if($teacher->bio || $teacher->bio_arabic)
    <p class="text-sm text-gray-500 mb-3 line-clamp-2 leading-relaxed">
      {{ $teacher->bio ?? $teacher->bio_arabic }}
    </p>
    @endif

    {{-- Certifications --}}
    @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
    <div class="mb-3">
      <div class="flex flex-wrap gap-1.5">
        @foreach(array_slice($teacher->certifications, 0, 2) as $cert)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700">
          <i class="ri-award-line me-1"></i>
          {{ Str::limit($cert, 28) }}
        </span>
        @endforeach
        @if(count($teacher->certifications) > 2)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
          +{{ count($teacher->certifications) - 2 }}
        </span>
        @endif
      </div>
    </div>
    @endif

    {{-- Languages --}}
    @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
    <div class="mb-3">
      <div class="flex flex-wrap gap-1.5">
        @foreach(array_slice($teacher->languages, 0, 3) as $lang)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
          <i class="ri-global-line me-1"></i>
          @php
            try { $langLabel = \App\Enums\TeachingLanguage::from($lang)->label(); }
            catch (\ValueError $e) { $langLabel = $lang; }
          @endphp
          {{ $langLabel }}
        </span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Subscribed State Panel --}}
    @if($isSubscribed && $subscription)
    <div class="bg-green-50 rounded-lg p-3 mb-3 border border-green-100">
      <div class="flex items-center justify-between text-sm mb-2">
        <span class="text-green-700 font-medium">
          <i class="ri-play-circle-line me-1"></i>
          {{ $subscription->sessions_attended ?? 0 }} {{ __('components.cards.quran_teacher.sessions_progress') }} {{ $subscription->total_sessions ?? 0 }} {{ __('components.cards.quran_teacher.session_unit') }}
        </span>
        <span class="text-xs font-bold text-green-800">{{ $subscription->progress_percentage ?? 0 }}%</span>
      </div>
      <div class="bg-green-200 rounded-full h-1.5">
        <div class="bg-green-600 h-1.5 rounded-full transition-all"
             style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
      </div>
    </div>
    @endif

    {{-- Spacer --}}
    <div class="flex-grow"></div>

    {{-- Section 4: Action Buttons --}}
    <div class="pb-4 sm:pb-6 pt-3 mt-auto">
      @if($isSubscribed && $subscription && $subscription->individualCircle)
        {{-- Subscribed: Circle button + Chat icon + Profile icon --}}
        <div class="flex items-center gap-2">
          <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id]) }}"
             class="flex-1 inline-flex items-center justify-center gap-1.5 bg-green-600 text-white h-11 px-4 rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors">
            <i class="ri-book-open-line"></i>
            {{ __('components.cards.quran_teacher.open_circle') }}
          </a>

          @if($teacher->user && $teacher->user->hasSupervisor())
            <x-chat.supervised-chat-button
                :teacher="$teacher->user"
                :student="auth()->user()"
                entityType="quran_individual"
                :entityId="$subscription->individualCircle->id"
                variant="icon-only"
                size="md"
                class="!w-11 !h-11 !p-0 !rounded-lg !bg-blue-50 !border !border-blue-200 !text-blue-600 hover:!bg-blue-100"
            />
          @endif

          <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
             class="w-11 h-11 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-yellow-600 transition-colors flex-shrink-0"
             title="{{ __('components.cards.quran_teacher.view_profile') }}">
            <i class="ri-user-line text-lg"></i>
          </a>
        </div>
      @elseif($isSubscribed && $subscription)
        {{-- Subscribed but circle not ready --}}
        <div class="flex items-center gap-2">
          <span class="flex-1 inline-flex items-center justify-center gap-1.5 bg-gray-100 text-gray-400 h-11 px-4 rounded-lg text-sm font-semibold cursor-not-allowed">
            <i class="ri-time-line"></i>
            {{ __('components.cards.quran_teacher.circle_preparing') }}
          </span>
          <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
             class="w-11 h-11 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-yellow-600 transition-colors flex-shrink-0"
             title="{{ __('components.cards.quran_teacher.view_profile') }}">
            <i class="ri-user-line text-lg"></i>
          </a>
        </div>
      @else
        {{-- Not subscribed: View Profile full-width --}}
        <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
           class="w-full inline-flex items-center justify-center gap-1.5 bg-yellow-600 text-white h-11 rounded-lg text-sm font-semibold hover:bg-yellow-700 transition-colors">
          <i class="ri-eye-line"></i>
          {{ __('components.cards.quran_teacher.view_profile') }}
        </a>
      @endif
    </div>
  </div>
</div>
