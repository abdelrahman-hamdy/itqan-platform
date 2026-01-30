@props([
    'circle',
    'type' => 'group', // 'group', 'individual', or 'trial'
    'context' => 'quran', // 'quran' or 'academic'
    'isEnrolled' => false,
    'showActions' => true
])

@php
    $isGroup = $type === 'group';
    $isIndividual = $type === 'individual';
    $isTrial = $type === 'trial';
    $isAcademic = $context === 'academic';

    // Calculate availability for group circles
    if ($isGroup) {
        $isAvailable = !$isEnrolled && $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN && $circle->enrolled_students < $circle->max_students;
        $isFull = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::FULL || $circle->enrolled_students >= $circle->max_students;
    }

    // Get teacher based on context
    $teacher = null;
    if ($isGroup) {
        $teacher = $circle->quranTeacher;
    } elseif ($isIndividual || $isTrial) {
        $teacher = $isAcademic ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
    }

    // Determine status text and color
    $statusText = '';
    $statusClass = '';

    if ($isGroup) {
        if ($isEnrolled) {
            $statusText = __('components.circle.status.active');
            $statusClass = 'bg-secondary-100 text-secondary-600';
        } elseif ($isAvailable) {
            $statusText = __('components.circle.status.open_enrollment');
            $statusClass = 'bg-primary-100 text-primary-600';
        } elseif ($isFull) {
            $statusText = __('components.circle.status.full');
            $statusClass = 'bg-red-100 text-red-800';
        } else {
            $statusText = __('components.circle.status.closed');
            $statusClass = 'bg-gray-100 text-gray-800';
        }
    } else {
        // Individual/Trial circles
        $statusText = $circle->status ? __('components.circle.status.active') : __('components.circle.status.inactive');
        $statusClass = $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    }

    // Get circle title
    $circleTitle = '';
    if ($isGroup) {
        $circleTitle = $circle->name ?? '';
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $circleTitle = $circle->subject->name ?? $circle->subject_name ?? __('components.circle.titles.private_lesson');
        } else {
            $circleTitle = $circle->subscription->package->name ?? __('components.circle.titles.individual_circle');
        }
    } elseif ($isTrial) {
        $circleTitle = __('components.circle.titles.trial_session');
    }

    // Get description
    $circleDescription = '';
    if ($isGroup) {
        $circleDescription = $circle->description ?? '';
    } elseif ($isIndividual) {
        $circleDescription = $isAcademic
            ? __('components.circle.descriptions.private_lesson_in', ['subject' => $circle->subject->name ?? __('components.circle.labels.academic_subject')])
            : __('components.circle.descriptions.individual_quran');
    } elseif ($isTrial) {
        $circleDescription = __('components.circle.descriptions.trial_session');
    }

    // Subdomain helper
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 card-hover">
    <!-- Card Header -->
    <div class="flex items-start justify-between gap-3 mb-3 md:mb-4">
        <div class="w-11 h-11 md:w-14 md:h-14 {{ $isEnrolled ? 'bg-gradient-to-br rtl:bg-gradient-to-bl from-secondary-100 to-secondary-200' : 'bg-gradient-to-br rtl:bg-gradient-to-bl from-primary-100 to-primary-200' }} rounded-lg md:rounded-xl flex items-center justify-center shadow-sm flex-shrink-0">
            <i class="{{ $isEnrolled ? 'ri-bookmark-fill text-secondary-600' : 'ri-book-mark-line text-primary-600' }} text-lg md:text-2xl"></i>
        </div>
        <span class="inline-flex items-center px-2.5 md:px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold shadow-sm {{ $statusClass }}">
            {{ $statusText }}
        </span>
    </div>

    <!-- Circle Info -->
    <div class="mb-3 md:mb-4">
        <h3 class="font-bold text-gray-900 mb-1.5 md:mb-2 text-base md:text-lg leading-tight">{{ $circleTitle }}</h3>
        <p class="text-xs md:text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $circleDescription }}</p>
    </div>

    <!-- Details Grid -->
    <div class="space-y-2.5 md:space-y-3 mb-4 md:mb-6 bg-gray-50 rounded-lg p-3 md:p-4">
        <!-- Teacher -->
        @if($teacher)
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-user-star-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.teacher') }}</p>
                <p class="font-semibold text-gray-900 truncate text-xs md:text-sm">{{ $teacher->full_name ?? $teacher->name ?? __('components.circle.labels.teacher_default') }}</p>
            </div>
        </div>
        @endif

        <!-- Students Count (Group circles only) -->
        @if($isGroup && isset($circle->students_count))
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-group-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.students_count') }}</p>
                <div class="flex items-center gap-2 md:gap-3">
                    <p class="font-semibold text-gray-900 text-xs md:text-sm">{{ $circle->students_count }}/{{ $circle->max_students }}</p>
                    <div class="flex-1 bg-gray-200 rounded-full h-1 md:h-1.5">
                        <div class="bg-primary h-1 md:h-1.5 rounded-full transition-all"
                             style="width: {{ $circle->max_students > 0 ? ($circle->students_count / $circle->max_students * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Session Progress (Individual/Trial) -->
        @if(($isIndividual || $isTrial) && isset($circle->subscription))
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-calendar-check-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.progress') }}</p>
                <p class="font-semibold text-gray-900 text-xs md:text-sm">{{ $circle->subscription->sessions_used ?? 0 }}/{{ $circle->subscription->total_sessions ?? 0 }} {{ __('components.circle.labels.session_unit') }}</p>
            </div>
        </div>
        @endif

        <!-- Schedule (Group circles) -->
        @if($isGroup && !empty($circle->schedule_days_text))
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-calendar-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.schedule') }}</p>
                <p class="font-semibold text-gray-900 truncate text-xs md:text-sm">{{ $circle->schedule_days_text }}</p>
            </div>
        </div>
        @endif

        <!-- Memorization Level (Group Quran circles) -->
        @if($isGroup && !$isAcademic && !empty($circle->memorization_level))
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-bar-chart-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.memorization_level') }}</p>
                <p class="font-semibold text-gray-900 text-xs md:text-sm">{{ $circle->memorization_level_text ?? $circle->memorization_level }}</p>
            </div>
        </div>
        @endif

        <!-- Monthly Fee (Group circles) -->
        @if($isGroup && !empty($circle->monthly_fee))
        <div class="flex items-center gap-2.5 md:gap-3 text-xs md:text-sm">
            <div class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                <i class="ri-money-dollar-circle-line text-primary text-sm md:text-base"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] md:text-xs text-gray-500 mb-0.5">{{ __('components.circle.labels.monthly_fee') }}</p>
                <p class="font-bold text-primary text-xs md:text-sm">{{ number_format($circle->monthly_fee, 2) }} {{ getCurrencySymbol() }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Action Button -->
    @if($showActions)
        @if($isGroup)
            @if($isEnrolled)
                <a href="{{ route('student.circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id]) }}"
                   class="min-h-[44px] flex items-center justify-center w-full bg-secondary text-white px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-semibold hover:bg-secondary-600 transition-colors text-center">
                    <i class="ri-eye-line me-1"></i>
                    {{ __('components.circle.actions.view_circle') }}
                </a>
            @else
                <a href="{{ route('student.circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id]) }}"
                   class="min-h-[44px] flex items-center justify-center w-full bg-primary text-white px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                    <i class="ri-information-line me-1"></i>
                    {{ __('components.circle.actions.view_details') }}
                </a>
            @endif
        @elseif($isIndividual)
            <a href="{{ route('student.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
               class="min-h-[44px] flex items-center justify-center w-full bg-primary text-white px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                <i class="ri-eye-line me-1"></i>
                {{ __('components.circle.actions.view_circle') }}
            </a>
        @elseif($isTrial)
            <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $circle->id]) }}"
               class="min-h-[44px] flex items-center justify-center w-full bg-primary text-white px-4 py-2.5 md:py-3 rounded-lg text-xs md:text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                <i class="ri-eye-line me-1"></i>
                {{ __('components.circle.actions.view_session') }}
            </a>
        @endif
    @endif
</div>
