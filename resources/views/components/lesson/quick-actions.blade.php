@props([
    'lesson',
    'lessonType' => 'quran', // 'quran' or 'academic'
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $isAcademic = $lessonType === 'academic';
    $isTeacher = $viewType === 'teacher';
    
    if ($isAcademic) {
        $student = $lesson->student ?? null;
        $teacher = $lesson->academicTeacher ?? null;
        $teacherRoute = $teacher ? route('public.academic-teachers.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) : '#';
        $allLessonsRoute = route('student.academic-private-lessons', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']);
    } else {
        $student = $lesson->student;
        $teacher = $lesson->quranTeacher;
        $teacherRoute = $teacher ? route('public.quran-teachers.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->quranTeacherProfile->id ?? $teacher->id]) : '#';
        $allLessonsRoute = route('student.quran-circles', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']);
    }
    
    // Get next session for students
    $nextSession = null;
    if (!$isTeacher) {
        // For academic lessons, we'll need to implement this when sessions are created
        if (!$isAcademic) {
            $nextSession = $lesson->sessions()
                ->where('scheduled_at', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('scheduled_at')
                ->first();
        }
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
    
    <div class="space-y-3">
        @if($isTeacher)
            <!-- Teacher Actions -->
            @if($isAcademic)
                <!-- Academic teacher specific actions -->
                <button type="button" onclick="viewDetailedReport()" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-line-chart-line ml-2"></i>
                    عرض التقرير التفصيلي
                </button>
            @else
                <!-- Quran teacher specific actions -->
                <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $lesson->id]) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-line-chart-line ml-2"></i>
                    عرض التقرير التفصيلي
                </a>
            @endif

            @if($student)
                <a href="/chat/{{ $student->id }}?subdomain={{ request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy' }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                    <i class="ri-message-3-line ml-2"></i>
                    مراسلة الطالب
                </a>
            @endif

            <button type="button" onclick="updateLessonSettings()" 
                class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-settings-line ml-2"></i>
                @if($isAcademic)
                    إعدادات الدرس
                @else
                    إعدادات الحلقة
                @endif
            </button>
        @else
            <!-- Student Actions -->
            @if($nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30 && $nextSession->scheduled_at->diffInMinutes(now()) >= -5)
                <a href="{{ route('student.sessions.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $nextSession->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ml-2"></i>
                    انضمام للجلسة القادمة
                </a>
            @endif

            @if($teacher)
                <a href="/chat/{{ $teacher->id }}?subdomain={{ request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy' }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                    <i class="ri-message-3-line ml-2"></i>
                    مراسلة المعلم
                </a>
            @endif
            


            <a href="{{ $allLessonsRoute }}" 
               class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-group-line ml-2"></i>
                @if($isAcademic)
                    جميع الدروس
                @else
                    جميع الحلقات
                @endif
            </a>
        @endif
    </div>
</div>

@if($isTeacher)
<script>
    function updateLessonSettings() {
        // This will be implemented when we create the settings functionality
        alert('سيتم تنفيذ إعدادات {{ $isAcademic ? "الدرس" : "الحلقة" }} قريباً');
    }
    
    @if($isAcademic)
    function viewDetailedReport() {
        // This will be implemented when we create the academic report functionality
        alert('سيتم تنفيذ التقرير التفصيلي قريباً');
    }
    @endif
</script>
@endif
