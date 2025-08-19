@props([
    'circle'
])

@php
    $sessions = $circle->sessions ?? collect();
    $completedSessions = $sessions->where('status', 'completed');
    $totalSessions = $sessions->count();
    $progressPercentage = $totalSessions > 0 ? ($completedSessions->count() / $totalSessions) * 100 : 0;
    
    // Calculate average performance metrics
    $avgRecitationQuality = $completedSessions->where('recitation_quality', '>', 0)->avg('recitation_quality') ?? 0;
    $avgTajweedAccuracy = $completedSessions->where('tajweed_accuracy', '>', 0)->avg('tajweed_accuracy') ?? 0;
    $consistencyScore = $totalSessions > 0 ? min(10, ($progressPercentage / 10)) : 0;
    
    $studentCount = $circle->students ? $circle->students->count() : 0;
    $maxStudents = $circle->max_students ?? 0;
    $enrollmentRate = $maxStudents > 0 ? ($studentCount / $maxStudents) * 100 : 0;
@endphp

<!-- Group Learning Progress Overview -->
<div class="bg-gradient-to-br from-blue-50 via-white to-purple-50 rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-900">تقدم الحلقة الجماعية</h3>
            <p class="text-gray-600">نظرة عامة على أداء المجموعة والتقدم</p>
        </div>
        <div class="text-right">
            <span class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                {{ number_format($consistencyScore, 1) }}/10
            </span>
            <p class="text-sm text-gray-500">درجة انتظام الحلقة</p>
        </div>
    </div>
    
    <!-- Progress Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Progress -->
        <div class="bg-white rounded-lg p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">إجمالي التقدم</span>
                <i class="ri-progress-line text-blue-600"></i>
            </div>
            <div class="text-2xl font-bold text-blue-900 mb-2">{{ number_format($progressPercentage, 1) }}%</div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $completedSessions->count() }}/{{ $totalSessions }} جلسة</div>
        </div>

        <!-- Average Recitation Quality -->
        <div class="bg-white rounded-lg p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">جودة التلاوة المتوسطة</span>
                <i class="ri-music-line text-purple-600"></i>
            </div>
            <div class="text-2xl font-bold text-purple-900 mb-2">{{ number_format($avgRecitationQuality, 1) }}/10</div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ $avgRecitationQuality * 10 }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">متوسط المجموعة</div>
        </div>

        <!-- Average Tajweed Accuracy -->
        <div class="bg-white rounded-lg p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">دقة التجويد المتوسطة</span>
                <i class="ri-book-open-line text-indigo-600"></i>
            </div>
            <div class="text-2xl font-bold text-indigo-900 mb-2">{{ number_format($avgTajweedAccuracy, 1) }}/10</div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ $avgTajweedAccuracy * 10 }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">متوسط المجموعة</div>
        </div>

        <!-- Enrollment Rate -->
        <div class="bg-white rounded-lg p-4 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">معدل التسجيل</span>
                <i class="ri-group-line text-green-600"></i>
            </div>
            <div class="text-2xl font-bold text-green-900 mb-2">{{ number_format($enrollmentRate, 1) }}%</div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ $enrollmentRate }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $studentCount }}/{{ $maxStudents ?: '∞' }} طالب</div>
        </div>
    </div>
    
    <!-- Detailed Progress Bars -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Consistency and Schedule Adherence -->
        <div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">انتظام الحلقة</span>
                <span class="font-medium">{{ number_format($consistencyScore, 1) }}/10</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-primary-500 to-purple-500 h-3 rounded-full transition-all duration-1000 shadow-lg" 
                     style="width: {{ $consistencyScore * 10 }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">مبني على انتظام الجلسات والحضور</div>
        </div>
        
        <!-- Overall Group Performance -->
        <div>
            @php
                $overallPerformance = ($avgRecitationQuality + $avgTajweedAccuracy + ($progressPercentage / 10)) / 3;
            @endphp
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">الأداء الإجمالي للمجموعة</span>
                <span class="font-medium">{{ number_format($overallPerformance, 1) }}/10</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-3 rounded-full transition-all duration-1000 shadow-lg" 
                     style="width: {{ $overallPerformance * 10 }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">متوسط التلاوة والتجويد والتقدم</div>
        </div>
    </div>

    <!-- Quick Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-200">
        <div class="text-center">
            <div class="text-lg font-bold text-gray-900">{{ $totalSessions }}</div>
            <div class="text-xs text-gray-600">إجمالي الجلسات</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-green-600">{{ $completedSessions->count() }}</div>
            <div class="text-xs text-gray-600">جلسة مكتملة</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-blue-600">{{ $sessions->where('status', 'scheduled')->count() }}</div>
            <div class="text-xs text-gray-600">جلسة مجدولة</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-primary-600">{{ $studentCount }}</div>
            <div class="text-xs text-gray-600">طالب نشط</div>
        </div>
    </div>

    <!-- Performance Grade -->
    @if($completedSessions->count() > 0)
        <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">التقييم الإجمالي للحلقة الجماعية</p>
                @php
                    $gradeText = $overallPerformance >= 8.5 ? 'ممتاز' : ($overallPerformance >= 7 ? 'جيد جداً' : ($overallPerformance >= 6 ? 'جيد' : 'يحتاج تحسين'));
                    $gradeColor = $overallPerformance >= 8.5 ? 'text-green-600' : ($overallPerformance >= 7 ? 'text-blue-600' : ($overallPerformance >= 6 ? 'text-yellow-600' : 'text-red-600'));
                    $gradeIcon = $overallPerformance >= 8.5 ? 'ri-trophy-line' : ($overallPerformance >= 7 ? 'ri-medal-line' : ($overallPerformance >= 6 ? 'ri-thumb-up-line' : 'ri-arrow-up-line'));
                @endphp
                <div class="flex items-center justify-center space-x-2 space-x-reverse">
                    <i class="{{ $gradeIcon }} text-2xl {{ $gradeColor }}"></i>
                    <p class="text-2xl font-bold {{ $gradeColor }}">{{ $gradeText }}</p>
                </div>
                <p class="text-sm text-gray-500">{{ number_format($overallPerformance, 1) }}/10</p>
            </div>
        </div>
    @endif
</div>
