@props([
    'progress', // Progress statistics array
    'title' => 'التقدم الأكاديمي'
])

@php
    // Session progress
    $sessionsCompleted = $progress['sessions_completed'] ?? 0;
    $totalSessions = $progress['total_sessions'] ?? 0;
    $progressPercentage = $totalSessions > 0 ? round(($sessionsCompleted / $totalSessions) * 100, 1) : 0;

    // Homework stats
    $homeworkCompletionRate = $progress['homework_completion_rate'] ?? 0;
    $homeworkSubmitted = $progress['homework_submitted'] ?? 0;
    $homeworkAssigned = $progress['homework_assigned'] ?? 0;

    // Performance trend
    $averageGrade = $progress['average_grade'] ?? 0;
    $gradeImprovement = $progress['grade_improvement'] ?? 0;

    // Topics covered
    $topicsCovered = $progress['topics_covered'] ?? 0;

    // Color for grade improvement
    $getImprovementColor = function($improvement) {
        if ($improvement > 0) return 'text-green-600';
        if ($improvement < 0) return 'text-red-600';
        return 'text-gray-600';
    };

    $getImprovementIcon = function($improvement) {
        if ($improvement > 0) return 'ri-arrow-up-line';
        if ($improvement < 0) return 'ri-arrow-down-line';
        return 'ri-subtract-line';
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    <!-- Sessions Progress -->
    <div class="mb-6">
        <div class="flex items-center justify-center mb-4">
            <div class="text-center">
                <div class="text-5xl font-bold text-primary mb-2">{{ $sessionsCompleted }}</div>
                <div class="text-sm text-gray-600">جلسة مكتملة</div>
                @if($totalSessions > 0)
                    <div class="text-xs text-gray-500 mt-1">من أصل {{ $totalSessions }} جلسة</div>
                @endif
            </div>
        </div>

        <!-- Sessions Progress Bar -->
        @if($totalSessions > 0)
            <div class="mb-4">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-medium text-gray-700">نسبة إتمام الاشتراك</span>
                    <span class="text-xs font-bold text-primary">{{ $progressPercentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-primary to-green-500 h-2 rounded-full transition-all duration-500"
                         style="width: {{ $progressPercentage }}%"></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500">
                    <span>{{ $sessionsCompleted }} من {{ $totalSessions }} جلسة</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Academic Stats Grid -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <!-- Average Grade -->
        <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-center justify-center mb-1">
                <div class="text-2xl font-bold text-blue-600">{{ number_format($averageGrade, 1) }}</div>
                <span class="text-xs text-blue-600 mr-1">/10</span>
            </div>
            <div class="text-xs text-blue-700 font-medium">المعدل العام</div>
            @if($gradeImprovement != 0)
                <div class="flex items-center justify-center mt-1">
                    <i class="{{ $getImprovementIcon($gradeImprovement) }} {{ $getImprovementColor($gradeImprovement) }} text-xs"></i>
                    <span class="text-xs {{ $getImprovementColor($gradeImprovement) }} mr-1">
                        {{ abs($gradeImprovement) > 0 ? number_format(abs($gradeImprovement), 1) : '0' }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Homework Completion -->
        <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="text-2xl font-bold text-green-600">{{ $homeworkCompletionRate }}%</div>
            <div class="text-xs text-green-700 font-medium">إنجاز الواجبات</div>
            @if($homeworkAssigned > 0)
                <div class="text-xs text-green-600 mt-1">
                    {{ $homeworkSubmitted }}/{{ $homeworkAssigned }}
                </div>
            @endif
        </div>
    </div>

    <!-- Topics Covered (if available) -->
    @if($topicsCovered > 0)
        <div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="ri-book-open-line text-purple-600 ml-2"></i>
                    <span class="text-sm text-purple-800">المواضيع المغطاة</span>
                </div>
                <span class="text-sm font-bold text-purple-700">
                    {{ $topicsCovered }} موضوع
                </span>
            </div>
        </div>
    @endif

    <!-- Performance Trend Info -->
    @if(isset($progress['recent_trend']) && $progress['recent_trend'])
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center text-xs text-gray-600">
                <i class="ri-information-line ml-1"></i>
                <span>{{ $progress['recent_trend'] }}</span>
            </div>
        </div>
    @endif
</div>
