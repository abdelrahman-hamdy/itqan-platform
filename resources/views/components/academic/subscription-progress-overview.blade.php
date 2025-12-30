@props([
    'subscription',
    'viewType' => 'student'
])

@php
    $isTeacher = $viewType === 'teacher';
    $completionRate = $subscription->completion_rate ?? 0;
    $progressColor = $completionRate >= 75 ? 'green' : ($completionRate >= 50 ? 'blue' : ($completionRate >= 25 ? 'yellow' : 'red'));
@endphp

<!-- Academic Subscription Progress Overview -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ri-pie-chart-line text-{{ $progressColor }}-600 ms-2"></i>
            نظرة عامة على التقدم
        </h3>
    </div>

    <!-- Progress Circle -->
    <div class="flex items-center justify-center mb-6">
        <div class="relative w-32 h-32">
            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                <!-- Background circle -->
                <path
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-width="2"/>
                <!-- Progress circle -->
                <path
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-dasharray="{{ $completionRate }}, 100"
                    class="text-{{ $progressColor }}-500"/>
            </svg>
            <!-- Percentage text -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-2xl font-bold text-{{ $progressColor }}-600">{{ number_format($completionRate, 1) }}%</div>
                    <div class="text-xs text-gray-500">مكتمل</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Stats -->
    <div class="space-y-3">
        <!-- Sessions Progress -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-calendar-check-line text-blue-600 ms-2"></i>
                <span class="text-sm font-medium text-gray-700">الجلسات المكتملة</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">
                {{ $subscription->sessions_completed ?? 0 }}/{{ $subscription->total_sessions ?? 0 }}
            </span>
        </div>

        <!-- Weekly Sessions -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-repeat-line text-green-600 ms-2"></i>
                <span class="text-sm font-medium text-gray-700">الجلسات الأسبوعية</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">{{ $subscription->sessions_per_week ?? 0 }}</span>
        </div>

        @if($subscription->monthly_fee)
        <!-- Monthly Fee -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-money-dollar-circle-line text-purple-600 ms-2"></i>
                <span class="text-sm font-medium text-gray-700">الرسوم الشهرية</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">{{ number_format($subscription->monthly_fee, 2) }} ريال</span>
        </div>
        @endif

        @if($subscription->start_date && $subscription->end_date)
        <!-- Duration -->
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-time-line text-orange-600 ms-2"></i>
                <span class="text-sm font-medium text-gray-700">مدة الاشتراك</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">
                {{ $subscription->start_date->diffInDays($subscription->end_date) }} يوم
            </span>
        </div>
        @endif
    </div>

    <!-- Performance Indicators (for teacher view) -->
    @if($isTeacher)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">مؤشرات الأداء</h4>
        <div class="space-y-2">
            <!-- Attendance Rate -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">معدل الحضور</span>
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ms-2">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $subscription->attendance_rate ?? 85 }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">{{ $subscription->attendance_rate ?? 85 }}%</span>
                </div>
            </div>

            <!-- Homework Completion -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">إنجاز الواجبات</span>
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ms-2">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $subscription->homework_completion_rate ?? 90 }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">{{ $subscription->homework_completion_rate ?? 90 }}%</span>
                </div>
            </div>

            <!-- Average Grade -->
            @if($subscription->average_grade)
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600">متوسط الدرجات</span>
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ms-2">
                        <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ ($subscription->average_grade / 100) * 100 }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">{{ number_format($subscription->average_grade, 1) }}/100</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Next Session Info -->
    @if($subscription->next_session_date)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <i class="ri-calendar-event-line text-blue-600 ms-2"></i>
                <div>
                    <div class="text-sm font-medium text-blue-800">الجلسة القادمة</div>
                    <div class="text-xs text-blue-600">{{ $subscription->next_session_date->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
