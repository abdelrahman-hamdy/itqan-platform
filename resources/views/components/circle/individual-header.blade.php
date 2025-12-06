@props([
    'circle',
    'viewType' => 'student', // 'student' or 'teacher'
    'context' => 'quran' // 'quran' or 'academic'
])

@php
    $student = $circle->student;
    $teacher = $context === 'academic' ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
    $isTeacher = $viewType === 'teacher';
    $isAcademic = $context === 'academic';
@endphp

<!-- Enhanced Individual Circle Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Circle Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    @if($isAcademic)
                        @if($isTeacher)
                            {{ $circle->subject->name ?? $circle->subject_name ?? 'مادة دراسية' }}
                        @else
                            {{ $circle->subject->name ?? $circle->subject_name ?? 'الدرس الخاص' }}
                        @endif
                    @else
                        @if($isTeacher)
                            الحلقة الفردية للطالب {{ $student->name ?? 'غير محدد' }}
                        @else
                            {{ $circle->subscription->package->name ?? 'الحلقة الفردية' }}
                        @endif
                    @endif
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $circle->status ? 'نشط' : 'غير نشط' }}
                </span>
            </div>

            <!-- Circle Description -->
            <p class="text-gray-600 mb-4 leading-relaxed">
                @if($isAcademic)
                    درس خاص في {{ $circle->subject->name ?? $circle->subject_name ?? 'المادة الأكاديمية' }}
                @else
                    @if($isTeacher)
                        حلقة فردية لتعليم القرآن الكريم
                    @else
                        حلقة فردية لتعليم القرآن الكريم
                    @endif
                @endif
            </p>
            
            <!-- Session Progress Only -->
            @if($circle->subscription)
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                        <i class="ri-calendar-check-line ml-1"></i>
                        {{ $circle->subscription->sessions_used ?? 0 }}/{{ $circle->subscription->total_sessions ?? 0 }} جلسة
                    </span>
                </div>
            @endif
        </div>
    </div>

    <!-- Admin Notes (Only for Teachers, Admins, and Super Admins) -->
    @if($circle->admin_notes && ($isTeacher || (auth()->user() && (auth()->user()->hasRole(['admin', 'super_admin']) || auth()->user()->isQuranTeacher()))))
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