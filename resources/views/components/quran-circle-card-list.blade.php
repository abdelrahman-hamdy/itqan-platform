@props(['circle', 'academy', 'enrolledCircleIds' => [], 'isAuthenticated' => false])

@php
  $isEnrolled = $isAuthenticated && in_array($circle->id, $enrolledCircleIds);
  $isAvailable = !$isEnrolled && $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN && $circle->enrolled_students < $circle->max_students;
  $isFull = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL || $circle->enrolled_students >= $circle->max_students;
@endphp

<div class="bg-white rounded-xl shadow-sm border {{ $isEnrolled ? 'border-blue-300 ring-2 ring-blue-100' : 'border-gray-200' }} p-6 card-hover relative">
  {{-- Enrolled Badge --}}
  @if($isEnrolled)
  <div class="absolute -top-2 -start-2 z-10">
    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-600 text-white shadow-md">
      <i class="ri-check-line"></i>
      {{ __('components.circle.status.enrolled') }}
    </span>
  </div>
  @endif

  <!-- Card Header -->
  <div class="flex items-start justify-between mb-4">
    <div class="w-14 h-14 bg-gradient-to-br rtl:bg-gradient-to-bl {{ $isEnrolled ? 'from-blue-100 to-blue-200' : 'from-green-100 to-green-200' }} rounded-xl flex items-center justify-center shadow-sm">
      <i class="ri-group-line {{ $isEnrolled ? 'text-blue-600' : 'text-green-600' }} text-2xl"></i>
    </div>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm
      {{ $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN ? 'bg-green-100 text-green-700' :
         ($circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL || $isFull ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
      @if($circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN)
        {{ __('components.circle.status.open_enrollment') }}
      @elseif($circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL || $isFull)
        {{ __('components.circle.status.full') }}
      @else
        {{ __('components.circle.status.closed') }}
      @endif
    </span>
  </div>

  <!-- Circle Info -->
  <div class="mb-4">
    <h3 class="font-bold text-gray-900 mb-2 text-lg leading-tight">{{ $circle->name }}</h3>
    <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $circle->description }}</p>
  </div>

  <!-- Details Grid -->
  <div class="space-y-3 mb-6 bg-gray-50 rounded-lg p-4">
    <!-- Teacher -->
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-user-star-line text-green-600"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.teacher') }}</p>
        <p class="font-semibold text-gray-900 truncate">{{ $circle->quranTeacher?->full_name ?? __('components.circle.labels.teacher_default') }}</p>
      </div>
    </div>

    <!-- Students Count -->
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-group-line text-green-600"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.students_count') }}</p>
        <div class="flex items-center gap-3">
          <p class="font-semibold text-gray-900">{{ $circle->students_count }}/{{ $circle->max_students }}</p>
          <div class="flex-1 bg-gray-200 rounded-full h-1.5">
            <div class="bg-green-600 h-1.5 rounded-full transition-all"
                 style="width: {{ $circle->max_students > 0 ? ($circle->students_count / $circle->max_students * 100) : 0 }}%"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule -->
    @if($circle->schedule_days_text)
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-calendar-line text-green-600"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.schedule') }}</p>
        <p class="font-semibold text-gray-900 truncate">{{ $circle->schedule_days_text }}</p>
      </div>
    </div>
    @endif

    <!-- Memorization Level -->
    @if($circle->memorization_level)
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-bar-chart-line text-green-600"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.memorization_level') }}</p>
        <p class="font-semibold text-gray-900">{{ $circle->memorization_level_text }}</p>
      </div>
    </div>
    @endif

    <!-- Monthly Fee -->
    @if($circle->monthly_fee)
    <div class="flex items-center gap-3 text-sm">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
        <i class="ri-money-dollar-circle-line text-green-600"></i>
      </div>
      <div class="flex-1">
        <p class="text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.monthly_fee') }}</p>
        <p class="font-bold text-green-600">{{ number_format($circle->monthly_fee, 2) }} {{ getCurrencySymbol() }}</p>
      </div>
    </div>
    @endif
  </div>

  <!-- Action Button -->
  @if($isEnrolled)
    <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circleId' => $circle->id]) }}"
       class="min-h-[44px] flex items-center justify-center w-full bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors text-center">
      <i class="ri-book-open-line me-1"></i>
      {{ __('components.circle.actions.go_to_circle') }}
    </a>
  @else
    <a href="{{ route('quran-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circleId' => $circle->id]) }}"
       class="min-h-[44px] flex items-center justify-center w-full bg-green-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors text-center">
      <i class="ri-information-line me-1"></i>
      {{ __('components.circle.actions.view_details') }}
    </a>
  @endif
</div>
