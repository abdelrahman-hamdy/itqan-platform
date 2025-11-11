@props([
    'lesson',
    'lessonType' => 'quran', // 'quran' or 'academic'
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $isAcademic = $lessonType === 'academic';
    
    if ($isAcademic) {
        // Use package data for calculations
        $package = $lesson->academicPackage ?? null;
        $totalSessions = $package ? $package->sessions_per_month : 0;
        $completedSessions = $lesson->sessions_completed ?? 0;
        $progressPercentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;
    } else {
        $completedSessions = $lesson->subscription->sessions_used ?? 0;
        $totalSessions = $lesson->subscription->total_sessions ?? 0;
        $progressPercentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;
    }
    
    $remainingSessions = max(0, $totalSessions - $completedSessions);
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4">نظرة عامة على التقدم</h3>
    
    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">التقدم الإجمالي</span>
            <span class="text-sm font-bold text-primary">{{ $progressPercentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-primary h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <!-- Completed Sessions -->
        <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="text-2xl font-bold text-green-600">{{ $completedSessions }}</div>
            <div class="text-xs text-green-700 font-medium">جلسة مكتملة</div>
        </div>
        
        <!-- Remaining Sessions -->
        <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="text-2xl font-bold text-blue-600">{{ $remainingSessions }}</div>
            <div class="text-xs text-blue-700 font-medium">جلسة متبقية</div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="space-y-3">
        <!-- Total Sessions -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-calendar-check-line text-gray-600 ml-2"></i>
                <span class="text-sm text-gray-700">إجمالي الجلسات</span>
            </div>
            <span class="text-sm font-bold text-gray-900">{{ $totalSessions }} جلسة</span>
        </div>



        @if($isAcademic)
            <!-- Subscription Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-information-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">حالة الاشتراك</span>
                </div>
                <span class="text-sm font-bold 
                    @if($lesson->status === 'active') text-green-600
                    @elseif($lesson->status === 'paused') text-yellow-600
                    @elseif($lesson->status === 'completed') text-blue-600
                    @elseif($lesson->status === 'cancelled') text-red-600
                    @else text-gray-600 @endif">
                    @if($lesson->status === 'active') نشط
                    @elseif($lesson->status === 'paused') متوقف
                    @elseif($lesson->status === 'completed') مكتمل
                    @elseif($lesson->status === 'cancelled') ملغي
                    @else {{ $lesson->status }} @endif
                </span>
            </div>

            <!-- Subscription Start Date -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-calendar-2-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">تاريخ البداية</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $lesson->created_at->format('Y/m/d') }}</span>
            </div>
        @endif
    </div>


</div>
