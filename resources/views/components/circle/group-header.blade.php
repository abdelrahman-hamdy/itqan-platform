@props([
    'circle',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $studentCount = $circle->students ? $circle->students->count() : 0;
    $maxStudents = $circle->max_students ?? '∞';
@endphp

<!-- Simple Circle Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Circle Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $circle->name }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $circle->status_text }}
                </span>
            </div>
            <p class="text-gray-600 mb-2">{{ $circle->description ?? 'حلقة قرآنية جماعية' }}</p>
        </div>
        
        <!-- Action Buttons -->
        @if($viewType === 'teacher')
            <div class="flex items-center space-x-2 space-x-reverse">
                <!-- Schedule functionality removed - now handled in Filament dashboard -->
            </div>
        @endif
    </div>

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