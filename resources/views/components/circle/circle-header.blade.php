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
        $circleTitle = $circle->name_ar ?? $circle->name_en ?? $circle->name;
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $circleTitle = $isTeacher
                ? 'الدرس الخاص - ' . ($student->name ?? 'طالب')
                : ($circle->subject->name ?? $circle->subject_name ?? 'الدرس الخاص');
        } else {
            $circleTitle = $isTeacher
                ? 'الحلقة الفردية - ' . ($student->name ?? 'طالب')
                : ($circle->subscription->package->name ?? 'الحلقة الفردية');
        }
    } elseif ($isTrial) {
        $circleTitle = $isTeacher
            ? 'جلسة تجريبية - ' . ($student->name ?? 'طالب')
            : 'الجلسة التجريبية';
    }

    // Get description based on type
    $circleDescription = '';
    if ($isGroup) {
        $circleDescription = $circle->description_ar ?? $circle->description_en ?? $circle->description ?? '';
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $circleDescription = $isTeacher
                ? 'درس خاص في ' . ($circle->subject->name ?? $circle->subject_name ?? 'المادة') . ' مع الطالب ' . ($student->name ?? '')
                : 'درس خاص في ' . ($circle->subject->name ?? $circle->subject_name ?? 'المادة الأكاديمية');
        } else {
            $circleDescription = $isTeacher
                ? 'حلقة فردية لتعليم القرآن الكريم مع الطالب ' . ($student->name ?? '')
                : 'حلقة فردية لتعليم القرآن الكريم';
        }
    } elseif ($isTrial) {
        $circleDescription = 'جلسة تجريبية لتقييم مستوى الطالب وتحديد الخطة التعليمية المناسبة';
    }

    // Get status text and color
    $statusText = $circle->status_text ?? ($circle->status ? 'نشط' : 'غير نشط');
    $statusClass = $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
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
                        <i class="ri-calendar-check-line ml-1"></i>
                        {{ $circle->subscription->sessions_used ?? 0 }}/{{ $circle->subscription->total_sessions ?? 0 }} جلسة
                    </span>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        @if($isTeacher)
            <div class="flex items-center space-x-2 space-x-reverse">
                <!-- Schedule functionality removed - now handled in Filament dashboard -->
            </div>
        @endif
    </div>

    <!-- Student Info Card (Individual/Trial - Teacher view only) -->
    @if(($isIndividual || $isTrial) && $isTeacher && isset($student))
        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="{{ route('teacher.students.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}"
               class="flex items-center space-x-4 space-x-reverse p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                <x-student-avatar :student="$student" size="lg" />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                        {{ $student->name ?? 'طالب' }}
                    </h3>
                    <p class="text-sm text-gray-500">{{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                    <div class="flex items-center space-x-3 space-x-reverse mt-2">
                        @if($student->email)
                            <span class="text-xs text-gray-400">{{ $student->email }}</span>
                        @endif
                        @if(isset($circle->subscription) && $circle->subscription->expires_at)
                            <span class="text-xs text-gray-400">ينتهي: {{ $circle->subscription->expires_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
                <i class="ri-external-link-line text-gray-400 group-hover:text-primary-600 transition-colors"></i>
            </a>
        </div>
    @endif

    <!-- Learning Objectives Display -->
    @if($circle->learning_objectives && count($circle->learning_objectives) > 0)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <x-circle.objectives-display 
                :objectives="$circle->learning_objectives" 
                variant="compact" 
                title="أهداف الحلقة" />
        </div>
    @endif

    <!-- Admin Notes (Only for Teachers, Admins, and Super Admins) -->
    @if($circle->admin_notes && ($viewType === 'teacher' || (auth()->user() && (auth()->user()->hasRole(['admin', 'super_admin']) || auth()->user()->isQuranTeacher()))))
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-orange-800 flex items-center">
                    <i class="ri-information-line text-orange-600 ml-2"></i>
                    ملاحظات الإدارة
                </h3>
                <span class="text-xs text-orange-400 italic">مرئية للإدارة والمعلمين والمشرفين فقط</span>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $circle->admin_notes }}</p>
            </div>
        </div>
    @endif
</div>