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
        إجراءات سريعة
    </h3>

    <div class="space-y-3">
        @if($isTeacher)
            {{-- TEACHER ACTIONS --}}

            {{-- Progress Reports Link --}}
            @if($isInteractiveCourse)
                <a href="{{ route('teacher.interactive-courses.report', ['subdomain' => $subdomain, 'course' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير التفصيلي
                </a>
            @elseif($isGroup)
                <a href="{{ route('teacher.group-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير الكامل
                </a>
            @elseif($isIndividual)
                @if($isAcademic)
                    <a href="{{ route('teacher.academic-subscriptions.report', ['subdomain' => $subdomain, 'subscription' => $circle->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="ri-file-chart-line ml-2"></i>
                        عرض التقرير التفصيلي
                    </a>
                @else
                    <a href="{{ route('teacher.individual-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="ri-file-chart-line ml-2"></i>
                        عرض التقرير الكامل
                    </a>
                @endif
            @endif

            {{-- Message Student (Individual/Trial only) --}}
            @if(($isIndividual || $isTrial) && $student)
                @php
                    $studentUser = ($student instanceof \App\Models\User) ? $student : ($student->user ?? null);
                    $conv = $studentUser ? auth()->user()->getOrCreatePrivateConversation($studentUser) : null;
                @endphp
                @if($conv)
                    <a href="{{ route('chat', ['subdomain' => $subdomain, 'conversation' => $conv->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                        <i class="ri-message-3-line ml-2"></i>
                        مراسلة الطالب
                    </a>
                @endif
            @endif

        @else
            {{-- STUDENT ACTIONS --}}

            {{-- Join Next Session (Individual/Trial only - if within 30 minutes) --}}
            @if(($isIndividual || $isTrial) && $nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30 && $nextSession->scheduled_at->diffInMinutes(now()) >= -5)
                <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $nextSession->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ml-2"></i>
                    انضمام للجلسة القادمة
                </a>
            @endif

            {{-- Room Link (Group circles) --}}
            @if($isGroup && $circle->room_link)
                <a href="{{ $circle->room_link }}" target="_blank"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ml-2"></i>
                    دخول الجلسة
                </a>
            @endif

            {{-- Enroll/Leave Actions (Group circles only, excluding interactive courses) --}}
            @if($isGroup && !$isInteractiveCourse)
                @if($canEnroll)
                    <button onclick="showEnrollModal({{ $circle->id }})"
                            class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="ri-user-add-line ml-2"></i>
                        انضم للحلقة
                    </button>
                @endif
            @endif

            {{-- Message Teacher --}}
            @if($teacher && (!$isGroup || $isEnrolled))
                @php
                    $teacherUser = ($teacher instanceof \App\Models\User) ? $teacher : ($teacher->user ?? null);
                    $conv = $teacherUser ? auth()->user()->getOrCreatePrivateConversation($teacherUser) : null;
                @endphp
                <a href="{{ $conv ? route('chat', ['subdomain' => $subdomain, 'conversation' => $conv->id]) : '#' }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200"
                   @if(!$conv) onclick="alert('حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.'); return false;" @endif>
                    <i class="ri-message-3-line ml-2"></i>
                    مراسلة المعلم
                </a>
            @endif

            {{-- View Full Report (Enrolled/Subscribed students only) --}}
            @if($isInteractiveCourse && $isEnrolled)
                <a href="{{ route('student.interactive-courses.report', ['subdomain' => $subdomain, 'course' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض تقريري
                </a>
            @elseif($isGroup && $isEnrolled)
                <a href="{{ route('student.group-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير الكامل
                </a>
            @elseif($isIndividual && !$isAcademic)
                <a href="{{ route('student.individual-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير الكامل
                </a>
            @elseif($isIndividual && $isAcademic)
                <a href="{{ route('student.academic-subscriptions.report', ['subdomain' => $subdomain, 'subscription' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض تقريري
                </a>
            @endif
        @endif
    </div>
</div>
