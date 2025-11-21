@props([
    'circle',
    'viewType' => 'student', // 'student' or 'teacher'
    'type' => 'quran' // 'quran' or 'academic'
])

@php
    $isTeacher = $viewType === 'teacher';
    $isAcademic = $type === 'academic';
    $student = $circle->student;
    $teacher = $isAcademic ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
    
    // Get next session for students
    $nextSession = null;
    if (!$isTeacher && !$isAcademic) {
        $nextSession = $circle->sessions()
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->first();
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
    
    <div class="space-y-3">
        @if($isTeacher)
            <!-- Teacher Actions -->
            @if($isAcademic)
                <a href="#" onclick="alert('سيتم تنفيذ التقرير التفصيلي قريباً')"
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-line-chart-line ml-2"></i>
                    عرض التقرير التفصيلي
                </a>
            @else
                <a href="{{ route('teacher.individual-circles.report', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير الكامل
                </a>
            @endif

            @if($student)
                @php
                    $studentUser = ($student instanceof \App\Models\User) ? $student : ($student->user ?? null);
                    $conv = $studentUser ? auth()->user()->getOrCreatePrivateConversation($studentUser) : null;
                @endphp
                @if($conv)
                    <a href="{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'conversation' => $conv->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                        <i class="ri-message-3-line ml-2"></i>
                        مراسلة الطالب
                    </a>
                @endif
            @endif

            @if(!$isAcademic)
                <button type="button" onclick="updateCircleSettings()" 
                    class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-settings-line ml-2"></i>
                    إعدادات الحلقة
                </button>
            @endif
        @else
            <!-- Student Actions -->
            @if(!$isAcademic)
                <a href="{{ route('student.individual-circles.report', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <i class="ri-file-chart-line ml-2"></i>
                    عرض التقرير الكامل
                </a>
            @endif

            @if($nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30 && $nextSession->scheduled_at->diffInMinutes(now()) >= -5)
                <a href="{{ route('student.sessions.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $nextSession->id]) }}"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ml-2"></i>
                    انضمام للجلسة القادمة
                </a>
            @endif

            @if($teacher)
                @php
                    // For academic teachers, $teacher is AcademicTeacherProfile, so we need the user relationship
                    // For Quran teachers, $teacher is already a User object
                    $teacherUser = $isAcademic ? ($teacher->user ?? null) : ($teacher instanceof \App\Models\User ? $teacher : ($teacher->user ?? null));
                    $conv = $teacherUser ? auth()->user()->getOrCreatePrivateConversation($teacherUser) : null;
                @endphp
                @if($conv)
                    <a href="{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'conversation' => $conv->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200">
                        <i class="ri-message-3-line ml-2"></i>
                        مراسلة المعلم
                    </a>
                @endif
            @endif
            
            @if($teacher && !$isAcademic)
                <a href="{{ route('public.quran-teachers.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->quranTeacherProfile->id ?? $teacher->id]) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-user-line ml-2"></i>
                    ملف المعلم
                </a>
            @endif

            <button type="button" onclick="requestReschedule()" 
                class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-calendar-event-line ml-2"></i>
                طلب إعادة جدولة
            </button>

            @if($isAcademic)
                <a href="{{ route('student.academic-teachers', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-group-line ml-2"></i>
                    جميع المعلمين الأكاديميين
                </a>
            @else
                <a href="{{ route('student.quran-circles', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-group-line ml-2"></i>
                    جميع الحلقات
                </a>
            @endif
        @endif
    </div>
</div>

@if($isTeacher)
<script>
    function updateCircleSettings() {
        // This will be implemented when we create the settings functionality
        alert('سيتم تنفيذ إعدادات الحلقة قريباً');
    }
    
    function updateLessonSettings() {
        // This will be implemented when we create the settings functionality
        alert('سيتم تنفيذ إعدادات الدرس قريباً');
    }
</script>
@endif 