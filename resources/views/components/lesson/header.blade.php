@props([
    'lesson',
    'lessonType' => 'quran', // 'quran' or 'academic'
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    use App\Enums\SubscriptionStatus;

    $isAcademic = $lessonType === 'academic';
    $isTeacher = $viewType === 'teacher';
    
    if ($isAcademic) {
        // For academic lessons (subscription-based)
        $student = $lesson->student ?? null;
        $teacher = $lesson->academicTeacher ?? null;
        $lessonName = $lesson->subject_name ?? 'درس أكاديمي';
        $lessonDescription = $lesson->grade_level_name ?? 'مرحلة دراسية';
        $sessionProgress = $lesson->sessions_completed ?? 0;
        $totalSessions = $lesson->total_sessions_scheduled ?? 0;
    } else {
        // For Quran circles
        $student = $lesson->student;
        $teacher = $lesson->quranTeacher;
        $lessonName = $isTeacher 
            ? 'الحلقة الفردية - ' . ($student->name ?? 'طالب')
            : ($lesson->subscription->package->name ?? 'الحلقة الفردية');
        $lessonDescription = $isTeacher 
            ? 'حلقة فردية لتعليم القرآن الكريم مع الطالب ' . ($student->name ?? '')
            : 'حلقة فردية لتعليم القرآن الكريم';
        $sessionProgress = $lesson->subscription->sessions_used ?? 0;
        $totalSessions = $lesson->subscription->total_sessions ?? 0;
    }
@endphp

<!-- Enhanced Lesson Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Lesson Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">{{ $lessonName }}</h1>
                @php
                    $statusValue = is_object($lesson->status) ? $lesson->status->value : $lesson->status;
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $statusValue === SubscriptionStatus::ACTIVE->value || $lesson->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    @if($isAcademic)
                        @if($statusValue === SubscriptionStatus::ACTIVE->value) نشط
                        @elseif($statusValue === SubscriptionStatus::PAUSED->value) متوقف
                        @elseif($statusValue === SubscriptionStatus::CANCELLED->value) ملغي
                        @else نشط @endif
                    @else
                        {{ $lesson->status ? 'نشط' : 'غير نشط' }}
                    @endif
                </span>
            </div>
            
            <!-- Lesson Description -->
            <p class="text-gray-600 mb-4 leading-relaxed">
                {{ $lessonDescription }}
                @if($isAcademic && $teacher)
                    <br><span class="text-sm text-gray-500">مع الأستاذ {{ $teacher->full_name }}</span>
                @endif
            </p>
            
            <!-- Session Progress -->
            <div class="flex items-center">
                <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                    <i class="ri-calendar-check-line ml-1"></i>
                    {{ $sessionProgress }}/{{ $totalSessions }} جلسة
                </span>
                @if($isAcademic && $lesson->completion_rate !== null)
                    <span class="mr-3 inline-flex items-center px-3 py-1 bg-purple-50 text-purple-700 text-sm font-medium rounded-full">
                        <i class="ri-progress-line ml-1"></i>
                        {{ $lesson->completion_rate }}% مكتمل
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Student/Teacher Info Card (for teacher view) -->
    @if($isTeacher && $student)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="{{ route('teacher.students.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}" 
               class="flex items-center space-x-4 space-x-reverse p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                <x-avatar :user="$student" size="lg" />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                        {{ $student->name ?? 'طالب' }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        @if($isAcademic)
                            {{ $lesson->subject_name }} - {{ $lesson->grade_level_name }}
                        @else
                            {{ $lesson->subscription->package->name ?? 'اشتراك مخصص' }}
                        @endif
                    </p>
                    <div class="flex items-center space-x-3 space-x-reverse mt-2">
                        @if($student->email)
                            <span class="text-xs text-gray-400">{{ $student->email }}</span>
                        @endif
                        @if(!$isAcademic && $lesson->subscription && $lesson->subscription->expires_at)
                            <span class="text-xs text-gray-400">ينتهي: {{ $lesson->subscription->expires_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                <i class="ri-external-link-line text-gray-400 group-hover:text-primary-600 transition-colors"></i>
            </a>
        </div>
    @endif

    <!-- Admin Notes (Only for Teachers, Admins, and Super Admins) -->
    @if(($lesson->admin_notes ?? $lesson->notes) && ($isTeacher || (auth()->user() && (auth()->user()->hasRole(['admin', 'super_admin']) || auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher()))))
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-orange-800 flex items-center">
                    <i class="ri-information-line text-orange-600 ml-2"></i>
                    ملاحظات الإدارة
                </h3>
                <span class="text-xs text-orange-400 italic">مرئية للإدارة والمعلمين والمشرفين فقط</span>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $lesson->admin_notes ?? $lesson->notes }}</p>
            </div>
        </div>
    @endif
</div>
