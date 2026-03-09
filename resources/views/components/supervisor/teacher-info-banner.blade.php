@props([
    'teacher',
    'type' => 'quran',
])

@php
    $isQuran = $type === 'quran';
    $bgClass = $isQuran ? 'from-green-50 to-emerald-50 border-green-200' : 'from-violet-50 to-purple-50 border-violet-200';
    $badgeClass = $isQuran ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700';
    $icon = $isQuran ? 'ri-book-read-line' : 'ri-graduation-cap-line';
    $typeLabel = $isQuran ? __('supervisor.teachers.teacher_type_quran') : __('supervisor.teachers.teacher_type_academic');

    $teacherCode = $isQuran
        ? ($teacher->quranTeacherProfile?->teacher_code ?? '')
        : ($teacher->academicTeacherProfile?->teacher_code ?? '');
@endphp

<div class="bg-gradient-to-r {{ $bgClass }} border rounded-xl p-3 md:p-4 mb-4 md:mb-6">
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-1.5 text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 flex-shrink-0">
            <i class="ri-shield-user-line"></i>
            {{ __('supervisor.common.viewing_as_supervisor') }}
        </div>
        <div class="h-4 w-px bg-gray-300 flex-shrink-0"></div>
        <x-avatar :user="$teacher" size="xs" :user-type="$isQuran ? 'quran_teacher' : 'academic_teacher'" class="flex-shrink-0" />
        <div class="min-w-0 flex-1">
            <span class="text-sm font-medium text-gray-900 truncate block">{{ $teacher->name }}</span>
        </div>
        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $badgeClass }} flex-shrink-0">
            <i class="{{ $icon }}"></i>
            {{ $typeLabel }}
        </span>
        @if($teacherCode)
            <span class="text-xs font-mono text-gray-500 flex-shrink-0 hidden sm:inline">{{ $teacherCode }}</span>
        @endif
    </div>
</div>
