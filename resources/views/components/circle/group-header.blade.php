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
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $circle->name }}</h1>
            <p class="text-gray-600 mb-2">{{ $circle->description ?? 'حلقة قرآنية جماعية' }}</p>
            <div class="flex items-center space-x-3 space-x-reverse">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 
                       ($circle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                    <i class="ri-pulse-line ml-1"></i>
                    {{ $circle->status === 'active' ? 'نشط' : 
                       ($circle->status === 'pending' ? 'في الانتظار' : 
                       ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                </span>
                <span class="text-sm text-gray-500">
                    {{ $studentCount }} طالب مسجل
                </span>
            </div>
        </div>
        
        <!-- Action Buttons -->
        @if($viewType === 'teacher')
            <div class="flex items-center space-x-2 space-x-reverse">
                <!-- Schedule functionality removed - now handled in Filament dashboard -->
            </div>
        @endif
    </div>
</div>