@props(['teacher', 'academy', 'availablePackages' => collect()])

@php
  $isSubscribed = $teacher->is_subscribed ?? false;
  $subscription = $teacher->my_subscription ?? null;
  $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
  $studentsCount = $teacher->active_students_count ?? $teacher->total_students ?? 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 card-hover flex flex-col overflow-hidden">
  {{-- Section 1: Header — Avatar + Name + Qualification + Experience --}}
  <div class="flex items-start gap-3 mb-3">
    <x-avatar
      :user="$teacher"
      size="lg"
      userType="quran_teacher"
      :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
      class="flex-shrink-0" />

    <div class="flex-1 min-w-0">
      {{-- Name + Subscription Badge --}}
      <div class="flex items-center justify-between gap-2 mb-1.5">
        <h3 class="font-bold text-gray-900 text-lg leading-tight truncate">
          {{ $teacher->user->full_name ?? $teacher->user->name ?? __('components.cards.quran_teacher.default_name') }}
        </h3>
        @if($isSubscribed)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 flex-shrink-0">
          <i class="ri-check-line me-1"></i>
          {{ __('components.cards.quran_teacher.my_teacher') }}
        </span>
        @endif
      </div>

      {{-- Qualification Badge + Experience --}}
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @if($teacher->educational_qualification)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
          <i class="ri-graduation-cap-line me-1"></i>
          {{ $teacher->educational_qualification instanceof \App\Enums\EducationalQualification ? $teacher->educational_qualification->label() : \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}
        </span>
        @endif
        @if($teacher->teaching_experience_years)
        <span class="inline-flex items-center text-sm text-gray-600">
          <i class="ri-time-line text-yellow-600 me-1"></i>
          {{ $teacher->teaching_experience_years }}
          {{ $teacher->teaching_experience_years == 1 ? __('components.cards.quran_teacher.year_experience') : __('components.cards.quran_teacher.years_experience') }}
        </span>
        @endif
      </div>
    </div>
  </div>

  {{-- Section 2: Info Tags — Time Range + Trial Badge + Available Days --}}
  <div class="flex flex-wrap items-center gap-2 mb-3">
    @if($teacher->available_time_start && $teacher->available_time_end)
    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
      <i class="ri-time-line me-1 text-yellow-600"></i>
      {{ formatTimeArabic($teacher->available_time_start) }} - {{ formatTimeArabic($teacher->available_time_end) }}
    </span>
    @endif

    @if($teacher->offers_trial_sessions)
    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-green-50 text-green-700 border border-green-200">
      <i class="ri-play-circle-line me-1"></i>
      {{ __('components.cards.quran_teacher.offers_trial') }}
    </span>
    @endif

    @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-yellow-50 text-yellow-700">
      <i class="ri-calendar-line me-1"></i>
      @php
        $displayDays = array_slice($teacher->available_days, 0, 3);
        $dayNames = collect($displayDays)->map(function($day) {
          try {
            return \App\Enums\WeekDays::from($day)->label();
          } catch (\ValueError $e) {
            return $day;
          }
        })->join(' · ');
      @endphp
      {{ $dayNames }}
      @if(count($teacher->available_days) > 3)
        <span class="ms-0.5 text-yellow-500">+{{ count($teacher->available_days) - 3 }}</span>
      @endif
    </span>
    @endif
  </div>

  {{-- Section 3: Bio (conditional) --}}
  @if($teacher->bio || $teacher->bio_arabic)
  <p class="text-sm text-gray-600 mb-3 line-clamp-2 leading-relaxed">
    {{ $teacher->bio ?? $teacher->bio_arabic }}
  </p>
  @endif

  {{-- Section 4: Certifications (conditional) --}}
  @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
  <div class="mb-3">
    <div class="flex flex-wrap gap-1.5">
      @foreach(array_slice($teacher->certifications, 0, 3) as $cert)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
        <i class="ri-award-line me-1"></i>
        {{ Str::limit($cert, 30) }}
      </span>
      @endforeach
      @if(count($teacher->certifications) > 3)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
        +{{ count($teacher->certifications) - 3 }}
      </span>
      @endif
    </div>
  </div>
  @endif

  {{-- Section 5: Languages (conditional) --}}
  @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
  <div class="mb-3">
    <div class="flex flex-wrap gap-1.5">
      @foreach(array_slice($teacher->languages, 0, 3) as $lang)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
        <i class="ri-global-line me-1"></i>
        @php
          try {
            $langLabel = \App\Enums\TeachingLanguage::from($lang)->label();
          } catch (\ValueError $e) {
            $langLabel = $lang;
          }
        @endphp
        {{ $langLabel }}
      </span>
      @endforeach
      @if(count($teacher->languages) > 3)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
        +{{ count($teacher->languages) - 3 }}
      </span>
      @endif
    </div>
  </div>
  @endif

  {{-- Section 6: Rating & Social Proof (only when data exists) --}}
  @if($rating > 0 || $studentsCount > 0)
  <div class="flex flex-wrap items-center gap-3 text-sm mb-3">
    @if($rating > 0)
    <x-reviews.star-rating
      :rating="$rating"
      :total-reviews="$teacher->total_reviews ?? null"
      size="sm"
    />
    @endif
    @if($studentsCount > 0)
    <span class="inline-flex items-center text-gray-600">
      <i class="ri-group-line me-1 text-yellow-600"></i>
      {{ $studentsCount }} {{ __('components.cards.quran_teacher.students_count') }}
    </span>
    @endif
  </div>
  @endif

  {{-- Section 7: Subscribed State Panel --}}
  @if($isSubscribed && $subscription)
  <div class="bg-gray-50 rounded-lg p-4 mb-4 space-y-3">
    {{-- Sessions Info --}}
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-video-line text-green-600"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.quran_teacher.sessions_label') }}</p>
        <p class="font-semibold text-gray-900">
          {{ $subscription->sessions_attended ?? 0 }} {{ __('components.cards.quran_teacher.sessions_progress') }} {{ $subscription->total_sessions ?? 0 }} {{ __('components.cards.quran_teacher.session_unit') }}
        </p>
      </div>
    </div>

    {{-- Progress Bar --}}
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-calendar-check-line text-green-600"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.quran_teacher.progress_label') }}</p>
        <div class="flex items-center gap-2">
          <div class="flex-1 bg-gray-200 rounded-full h-1.5">
            <div class="bg-green-600 h-1.5 rounded-full transition-all"
                 style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
          </div>
          <span class="text-xs font-semibold text-gray-900">{{ $subscription->progress_percentage ?? 0 }}%</span>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Spacer to push buttons to bottom --}}
  <div class="flex-grow"></div>

  {{-- Section 8: Action Buttons --}}
  <div class="flex flex-wrap items-center gap-2 mt-auto pt-3 border-t border-gray-100">
    {{-- View Profile Button --}}
    <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
       class="inline-flex items-center bg-yellow-600 text-white px-4 sm:px-5 py-3 sm:py-3.5 rounded-lg text-sm font-semibold hover:bg-yellow-700 transition-colors">
      <i class="ri-eye-line me-1"></i>
      {{ __('components.cards.quran_teacher.view_profile') }}
    </a>

    {{-- Subscribed Teacher Buttons --}}
    @if($isSubscribed && $subscription)
      @if($subscription->individualCircle)
        <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id]) }}"
           class="inline-flex items-center px-4 sm:px-5 py-3 sm:py-3.5 bg-green-50 border border-green-200 rounded-lg text-sm font-semibold text-green-700 hover:bg-green-100 transition-colors">
          <i class="ri-book-open-line me-1"></i>
          {{ __('components.cards.quran_teacher.open_circle') }}
        </a>

        @if($teacher->user && $teacher->user->hasSupervisor())
          <x-chat.supervised-chat-button
              :teacher="$teacher->user"
              :student="auth()->user()"
              entityType="quran_individual"
              :entityId="$subscription->individualCircle->id"
              variant="icon-only"
              size="lg"
              class="px-4 sm:px-5"
              style="height: 48px;"
          />
        @endif
      @else
        <span class="inline-flex items-center px-4 sm:px-5 py-3 sm:py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-semibold text-gray-400 cursor-not-allowed">
          <i class="ri-time-line me-1"></i>
          {{ __('components.cards.quran_teacher.circle_preparing') }}
        </span>
      @endif
    @endif
  </div>
</div>
