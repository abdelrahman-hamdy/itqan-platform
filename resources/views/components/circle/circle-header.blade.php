@props([
    'circle',
    'type' => 'group', // 'group', 'individual', or 'trial'
    'viewType' => 'student', // 'student' or 'teacher'
    'context' => 'quran' // 'quran' or 'academic' (for individual/trial circles)
])

@php
    // Common variables
    $isTeacher = $viewType === 'teacher';
    $isGroup = $type === 'group';
    $isIndividual = $type === 'individual';
    $isTrial = $type === 'trial';
    $isAcademic = $context === 'academic';

    // Group-specific variables
    if ($isGroup) {
        $studentCount = $circle->students ? $circle->students->count() : 0;
        $maxStudents = $circle->max_students ?? '∞';
    }

    // Individual/Trial-specific variables
    if ($isIndividual || $isTrial) {
        $student = $circle->student ?? null;
        $teacher = $isAcademic ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
    }

    // Get circle title based on type
    $circleTitle = '';
    if ($isGroup) {
        $circleTitle = $circle->name ?? '';
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $circleTitle = $isTeacher
                ? __('components.circle.header.private_lesson_prefix') . ($student->name ?? __('components.circle.header.student'))
                : ($circle->subject->name ?? $circle->subject_name ?? __('components.circle.header.private_lesson'));
        } else {
            $circleTitle = $isTeacher
                ? __('components.circle.header.individual_circle_prefix') . ($student->name ?? __('components.circle.header.student'))
                : ($circle->name ?? __('components.circle.header.individual_circle'));
        }
    } elseif ($isTrial) {
        $circleTitle = $isTeacher
            ? __('components.circle.header.trial_session_prefix') . ($student->name ?? __('components.circle.header.student'))
            : __('components.circle.header.trial_session');
    }

    // Get description based on type
    $circleDescription = '';
    if ($isGroup) {
        $circleDescription = $circle->description ?? '';
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $subjectName = $circle->subject->name ?? $circle->subject_name ?? __('components.circle.header.academic_subject');
            $circleDescription = $isTeacher
                ? __('components.circle.header.private_lesson_description') . ' ' . $subjectName . ' ' . __('components.circle.header.with_student') . ' ' . ($student->name ?? '')
                : __('components.circle.descriptions.private_lesson_in', ['subject' => $subjectName]);
        } else {
            $circleDescription = $isTeacher
                ? __('components.circle.header.individual_quran_description') . ' ' . ($student->name ?? '')
                : __('components.circle.header.individual_quran');
        }
    } elseif ($isTrial) {
        $circleDescription = __('components.circle.header.trial_description');
    }

    // Get status text and color based on circle type
    if (($isIndividual || $isTrial) && isset($circle->subscription)) {
        // For individual/trial circles: use the subscription status (most meaningful for students)
        $subStatus = $circle->subscription->status;
        if ($subStatus instanceof \App\Enums\SessionSubscriptionStatus) {
            $statusText = $subStatus->label();
            $statusClass = $subStatus->badgeClasses();
        } else {
            $isActive = $subStatus === 'active';
            $statusText = $isActive ? __('components.circle.header.active') : __('components.circle.header.inactive');
            $statusClass = $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
        }
    } else {
        // For group circles (QuranCircle has `status` boolean) or individual without subscription (`is_active` boolean)
        $isActive = $circle->is_active ?? $circle->status ?? false;
        $statusText = $circle->status_text ?? ($isActive ? __('components.circle.header.active') : __('components.circle.header.inactive'));
        $statusClass = $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    }
@endphp

<!-- Unified Circle Header (Group/Individual/Trial) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Circle Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">{{ $circleTitle }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                    {{ $statusText }}
                </span>
            </div>

            <!-- Circle Description -->
            @if($circleDescription)
                <p class="text-gray-600 mb-4 leading-relaxed">{{ $circleDescription }}</p>
            @endif

            <!-- Subscription Session Progress (Individual/Trial only) -->
            @if(($isIndividual || $isTrial) && isset($circle->subscription))
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                        <i class="ri-calendar-check-line ms-1 rtl:ms-1 ltr:me-1"></i>
                        {{ $circle->subscription->sessions_used ?? 0 }}/{{ $circle->subscription->total_sessions ?? 0 }} {{ __('components.circle.header.sessions_progress') }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        @if($isTeacher)
            <div class="flex items-center gap-2">
                <!-- Schedule functionality removed - now handled in Filament dashboard -->
            </div>
        @endif
    </div>

    <!-- Student Info Card (Individual/Trial - Teacher view only) -->
    @if(($isIndividual || $isTrial) && $isTeacher && isset($student))
        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="{{ route('teacher.students.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}"
               class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                <x-avatar
                    :user="$student"
                    size="lg"
                    userType="student"
                    :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                        {{ $student->name ?? __('components.circle.header.student') }}
                    </h3>
                    <p class="text-sm text-gray-500">{{ $circle->subscription->package->name ?? __('components.circle.header.custom_subscription') }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        @if($student->email)
                            <span class="text-xs text-gray-400">{{ $student->email }}</span>
                        @endif
                        @if(isset($circle->subscription) && $circle->subscription->ends_at)
                            <span class="text-xs text-gray-400">{{ __('components.circle.header.expires') }} {{ $circle->subscription->ends_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                <i class="ri-external-link-line text-gray-400 group-hover:text-primary-600 transition-colors rtl:rotate-180"></i>
            </a>
        </div>
    @endif

    <!-- Learning Objectives Display -->
    @if($circle->learning_objectives && count($circle->learning_objectives) > 0)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <x-circle.objectives-display
                :objectives="$circle->learning_objectives"
                variant="compact"
                :title="__('components.circle.header.objectives_title')" />
        </div>
    @endif

    <!-- Admin Notes (Only for Teachers, Admins, and Super Admins) -->
    @if($circle->admin_notes && ($viewType === 'teacher' || (auth()->user() && (auth()->user()->hasRole(['admin', 'super_admin']) || auth()->user()->isQuranTeacher()))))
        <x-common.admin-notes :notes="$circle->admin_notes" />
    @endif
</div>