@props([
    'circle',
    'type' => 'group', // 'group', 'individual', or 'trial'
    'viewType' => 'student', // 'student' or 'teacher'
    'context' => 'quran', // 'quran' or 'academic'
    'isEnrolled' => false, // For group circles
    'canEnroll' => false // For group circles
])

@php
    $isTeacher = $viewType === 'teacher';
    $isGroup = $type === 'group';
    $isIndividual = $type === 'individual';
    $isTrial = $type === 'trial';
    $isAcademic = $context === 'academic';

    // Detect if this is an interactive course (not a Quran circle)
    $isInteractiveCourse = $circle instanceof \App\Models\InteractiveCourse;

    // Get student and teacher based on circle type
    $student = ($isIndividual || $isTrial) ? ($circle->student ?? null) : null;
    $teacher = null;

    if ($isGroup) {
        if ($isInteractiveCourse) {
            // Interactive course - get User from assignedTeacher (AcademicTeacherProfile)
            $academicTeacher = $circle->assignedTeacher ?? null;
            $teacher = $academicTeacher?->user ?? null;
        } else {
            // Quran group circle - quranTeacher is already a User model
            $teacher = $circle->quranTeacher ?? null;
        }
    } elseif ($isIndividual || $isTrial) {
        if ($isAcademic) {
            // Academic individual - teacher is AcademicTeacherProfile, get User
            $academicTeacher = $circle->teacher ?? null;
            $teacher = $academicTeacher?->user ?? null;
        } else {
            // Quran individual - quranTeacher is already a User model
            $teacher = $circle->quranTeacher ?? null;
        }
    }

    // Get next session for individual/trial students
    $nextSession = null;
    if (!$isTeacher && ($isIndividual || $isTrial) && !$isAcademic) {
        $nextSession = $circle->sessions()
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->first();
    }

    // Subdomain helper
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="ri-flashlight-line text-blue-500 text-lg" style="font-weight: 100;"></i>
        {{ __('components.circle.quick_actions.title') }}
    </h3>

    <div class="space-y-3">
        @if($isTeacher)
            {{-- TEACHER ACTIONS --}}

            {{-- Progress Reports Link --}}
            @if($isInteractiveCourse)
                <a href="{{ route('teacher.interactive-courses.report', ['subdomain' => $subdomain, 'course' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_detailed_report') }}
                </a>
            @elseif($isGroup)
                <a href="{{ route('teacher.group-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_full_report') }}
                </a>
            @elseif($isIndividual)
                @if($isAcademic)
                    <a href="{{ route('teacher.academic-subscriptions.report', ['subdomain' => $subdomain, 'subscription' => $circle->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ __('components.circle.quick_actions.view_detailed_report') }}
                    </a>
                @else
                    <a href="{{ route('teacher.individual-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ __('components.circle.quick_actions.view_full_report') }}
                    </a>
                @endif
            @endif

            {{-- Message Student (Individual/Trial only) --}}
            @if(($isIndividual || $isTrial) && $student)
                @php
                    $studentUser = ($student instanceof \App\Models\User) ? $student : ($student->user ?? null);
                @endphp
                @if($studentUser)
                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $studentUser->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                        <i class="ri-message-3-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ __('components.circle.quick_actions.message_student') }}
                    </a>
                @endif
            @endif

        @else
            {{-- STUDENT ACTIONS --}}

            {{-- Join Next Session (Individual/Trial only - if within 30 minutes) --}}
            @if(($isIndividual || $isTrial) && $nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30 && $nextSession->scheduled_at->diffInMinutes(now()) >= -5)
                <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $nextSession->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.join_next_session') }}
                </a>
            @endif

            {{-- Room Link (Group circles) --}}
            @if($isGroup && $circle->room_link)
                <a href="{{ $circle->room_link }}" target="_blank"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.join_session') }}
                </a>
            @endif

            {{-- Enroll/Leave Actions (Group circles only, excluding interactive courses) --}}
            @if($isGroup && !$isInteractiveCourse)
                @if($canEnroll)
                    <button onclick="showEnrollModal({{ $circle->id }})"
                            class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="ri-user-add-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ __('components.circle.quick_actions.join_circle') }}
                    </button>
                @endif
            @endif

            {{-- Message Teacher --}}
            @if($teacher && (!$isGroup || $isEnrolled))
                @php
                    $teacherUser = ($teacher instanceof \App\Models\User) ? $teacher : ($teacher->user ?? null);
                @endphp
                @if($teacherUser)
                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $teacherUser->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                        <i class="ri-message-3-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ __('components.circle.quick_actions.message_teacher') }}
                    </a>
                @endif
            @endif

            {{-- View Full Report (Enrolled/Subscribed students only) --}}
            @if($isInteractiveCourse && $isEnrolled)
                <a href="{{ route('student.interactive-courses.report', ['subdomain' => $subdomain, 'course' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_my_report') }}
                </a>
            @elseif($isGroup && $isEnrolled)
                <a href="{{ route('student.group-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_full_report') }}
                </a>
            @elseif($isIndividual && !$isAcademic)
                <a href="{{ route('student.individual-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_full_report') }}
                </a>
            @elseif($isIndividual && $isAcademic)
                <a href="{{ route('student.academic-subscriptions.report', ['subdomain' => $subdomain, 'subscription' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ms-2 rtl:ms-2 ltr:me-2"></i>
                    {{ __('components.circle.quick_actions.view_my_report') }}
                </a>
            @endif
        @endif
    </div>
</div>
