@props([
    'circle',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $student = $circle->student;
    $studentName = $student->name ?? 'طالب';
@endphp

<!-- Student Info Card -->
@if($viewType === 'teacher' && $student)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <!-- Clickable Student Info -->
            <a href="{{ route('teacher.students.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}" 
               class="flex items-center space-x-4 space-x-reverse hover:bg-gray-50 p-3 rounded-lg transition-colors group">
                <x-student-avatar :student="$student" size="lg" />
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">{{ $studentName }}</h3>
                    <p class="text-sm text-gray-500">{{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                    <div class="flex items-center space-x-2 space-x-reverse mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 
                               ($circle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ $circle->status === 'active' ? 'نشط' : 
                               ($circle->status === 'pending' ? 'في الانتظار' : 
                               ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                        </span>
                        @if($student->email)
                            <span class="text-xs text-gray-400">{{ $student->email }}</span>
                        @endif
                    </div>
                </div>
            </a>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-2 space-x-reverse">
                @if($circle->canScheduleSession())
                    <button type="button" id="scheduleSessionBtn" 
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="ri-calendar-line ml-2"></i>
                        جدولة جلسة
                    </button>
                @endif
                
                <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="ri-line-chart-line ml-2"></i>
                    تقرير التقدم
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Progress Overview -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-blue-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-blue-600">إجمالي الجلسات</p>
                <p class="text-2xl font-bold text-blue-900">{{ $circle->total_sessions }}</p>
            </div>
            <i class="ri-book-line text-2xl text-blue-500"></i>
        </div>
    </div>
    
    <div class="bg-green-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-green-600">المكتملة</p>
                <p class="text-2xl font-bold text-green-900">{{ $circle->sessions_completed }}</p>
            </div>
            <i class="ri-checkbox-circle-line text-2xl text-green-500"></i>
        </div>
    </div>
    
    <div class="bg-orange-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">المجدولة</p>
                <p class="text-2xl font-bold text-orange-900">{{ $circle->sessions_scheduled }}</p>
            </div>
            <i class="ri-calendar-check-line text-2xl text-orange-500"></i>
        </div>
    </div>
    
    <div class="bg-purple-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-purple-600">المتبقية</p>
                <p class="text-2xl font-bold text-purple-900">{{ $circle->sessions_remaining }}</p>
            </div>
            <i class="ri-time-line text-2xl text-purple-500"></i>
        </div>
    </div>
</div>

<!-- Progress Bar -->
@if($circle->progress_percentage > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">نسبة الإنجاز</span>
            <span class="text-sm font-medium text-gray-900">{{ number_format($circle->progress_percentage, 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                 style="width: {{ $circle->progress_percentage }}%"></div>
        </div>
    </div>
@endif

<!-- Learning Progress -->
@if($circle->current_surah || $circle->verses_memorized)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <h4 class="font-semibold text-gray-900 mb-3">التقدم في الحفظ</h4>
        <div class="space-y-2">
            @if($circle->current_surah)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">السورة الحالية:</span>
                    <span class="font-medium text-gray-900">سورة رقم {{ $circle->current_surah }}</span>
                </div>
            @endif
            @if($circle->current_verse)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">الآية الحالية:</span>
                    <span class="font-medium text-gray-900">آية {{ $circle->current_verse }}</span>
                </div>
            @endif
            @if($circle->verses_memorized)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">إجمالي الآيات المحفوظة:</span>
                    <span class="font-medium text-green-600">{{ $circle->verses_memorized }} آية</span>
                </div>
            @endif
        </div>
    </div>
@endif
