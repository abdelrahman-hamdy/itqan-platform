@props(['teacher', 'academy', 'availablePackages' => collect()])

@php
  $isSubscribed = $teacher->is_subscribed ?? false;
  $subscription = $teacher->my_subscription ?? null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover flex flex-col">
  <!-- Card Header -->
  <div class="flex items-center gap-3 mb-4">
    <!-- Teacher Avatar -->
    <x-avatar
      :user="$teacher"
      size="lg"
      userType="quran_teacher"
      :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
      class="flex-shrink-0" />

    <!-- Name and Info -->
    <div class="flex-1 min-w-0">
      <!-- Name Row -->
      <div class="flex items-center justify-between gap-2 mb-2">
        <h3 class="font-bold text-gray-900 text-lg leading-tight">
          {{ $teacher->user->full_name ?? $teacher->user->name ?? __('components.cards.quran_teacher.default_name') }}
        </h3>
        <!-- Status Badge -->
        @if($isSubscribed)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 flex-shrink-0">
          <i class="ri-check-line me-1"></i>
          {{ __('components.cards.quran_teacher.my_teacher') }}
        </span>
        @endif
      </div>

      <!-- Info Row -->
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-sm text-gray-600">
          @if($teacher->educational_qualification)
          <div class="flex items-center gap-1">
            <i class="ri-graduation-cap-line text-yellow-600"></i>
            <span class="truncate">{{ \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}</span>
          </div>
          @endif
          @if($teacher->educational_qualification && $teacher->teaching_experience_years)
          <span class="text-gray-300">•</span>
          @endif
          @if($teacher->teaching_experience_years)
          <div class="flex items-center gap-1">
            <i class="ri-time-line text-yellow-600"></i>
            <span>{{ $teacher->teaching_experience_years }} {{ __('components.cards.quran_teacher.years_experience') }}</span>
          </div>
          @endif
        </div>
        <!-- Rating and Students -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <div class="flex items-center">
            <i class="ri-star-fill text-yellow-400 text-base"></i>
            <span class="text-sm font-semibold text-gray-700 me-1">
              {{ number_format($teacher->average_rating ?? $teacher->rating ?? 4.8, 1) }}
            </span>
          </div>
          @if($teacher->active_students_count)
          <span class="w-1 h-1 rounded-full bg-gray-300"></span>
          <span class="text-xs text-gray-600">{{ $teacher->active_students_count }} {{ __('components.cards.quran_teacher.students_count') }}</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Bio -->
  @if($teacher->bio || $teacher->bio_arabic)
  <p class="text-sm text-gray-600 mb-4 line-clamp-2 leading-relaxed">
    {{ $teacher->bio ?? $teacher->bio_arabic }}
  </p>
  @endif

  <!-- Details Grid -->
  <div class="space-y-3 mb-6 bg-gray-50 rounded-lg p-4">
    @if($isSubscribed && $subscription)
      <!-- Sessions Info -->
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

      <!-- Progress -->
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
    @else
      <!-- Teaching Times -->
      @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
      <div class="flex items-center gap-3 text-sm">
        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
          <i class="ri-calendar-line text-yellow-600"></i>
        </div>
        <div class="flex-1">
          <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.quran_teacher.teaching_times') }}</p>
          <p class="font-semibold text-gray-900">
            @php
              $displayDays = array_slice($teacher->available_days, 0, 3);
              $dayNames = collect($displayDays)->map(function($day) {
                try {
                  return \App\Enums\WeekDays::from($day)->label();
                } catch (\ValueError $e) {
                  return $day;
                }
              })->join(' • ');
            @endphp
            {{ $dayNames }}
            @if(count($teacher->available_days) > 3)
            <span class="text-xs text-gray-500">+{{ count($teacher->available_days) - 3 }}</span>
            @endif
            @if($teacher->available_time_start && $teacher->available_time_end)
            <span class="text-xs text-gray-600 me-1">• {{ formatTimeArabic($teacher->available_time_start) }} - {{ formatTimeArabic($teacher->available_time_end) }}</span>
            @endif
          </p>
        </div>
      </div>
      @endif

      <!-- Lowest Price -->
      @if($availablePackages->count() > 0)
      <div class="flex items-center gap-3 text-sm">
        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
          <i class="ri-money-dollar-circle-line text-yellow-600"></i>
        </div>
        <div class="flex-1">
          <p class="text-xs text-gray-500 mb-0.5">{{ __('components.cards.quran_teacher.prices_label') }}</p>
          <p class="font-bold text-yellow-600">{{ __('components.cards.quran_teacher.starts_from') }} {{ number_format($availablePackages->min('monthly_price'), 0) }} {{ __('components.cards.quran_teacher.per_month') }}</p>
        </div>
      </div>
      @endif
    @endif
  </div>

  <!-- Certifications -->
  @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
  <div class="mb-6">
    <p class="text-xs font-medium text-gray-500 mb-2">{{ __('components.cards.quran_teacher.certifications_label') }}</p>
    <div class="flex flex-wrap gap-1.5">
      @foreach(array_slice($teacher->certifications, 0, 3) as $cert)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800">
        <i class="ri-award-line me-1"></i>
        {{ Str::limit($cert, 25) }}
      </span>
      @endforeach
      @if(count($teacher->certifications) > 3)
      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
        +{{ count($teacher->certifications) - 3 }}
      </span>
      @endif
    </div>
  </div>
  @endif

  <!-- Spacer to push buttons to bottom -->
  <div class="flex-grow"></div>

  <!-- Action Buttons -->
  <div class="flex items-center gap-2 mt-auto">
    <!-- View Profile Button (Yellow - Always shown) -->
    <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
       class="inline-flex items-center bg-yellow-600 text-white px-5 py-3.5 rounded-lg text-sm font-semibold hover:bg-yellow-700 transition-colors">
      <i class="ri-eye-line me-1"></i>
      {{ __('components.cards.quran_teacher.view_profile') }}
    </a>

    <!-- Additional Buttons for Subscribed Teachers -->
    @if($isSubscribed && $subscription && $subscription->individualCircle)
      <!-- Open Circle Button (Subtle Yellow) -->
      <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id]) }}"
         class="inline-flex items-center px-5 py-3.5 bg-yellow-50 border-2 border-yellow-200 rounded-lg text-sm font-semibold text-yellow-700 hover:bg-yellow-100 transition-colors">
        <i class="ri-book-open-line me-1"></i>
        {{ __('components.cards.quran_teacher.open_circle') }}
      </a>

      <!-- Chat Button (Supervised) -->
      @if($teacher->user && $teacher->user->hasSupervisor() && $subscription->individualCircle)
        <x-chat.supervised-chat-button
            :teacher="$teacher->user"
            :student="auth()->user()"
            entityType="quran_individual"
            :entityId="$subscription->individualCircle->id"
            variant="icon-only"
            size="lg"
            class="px-5"
            style="height: 52px;"
        />
      @endif
    @endif
  </div>
</div>
