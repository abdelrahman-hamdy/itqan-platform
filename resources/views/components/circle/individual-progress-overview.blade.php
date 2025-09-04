@props([
    'circle',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $isTeacher = $viewType === 'teacher';
    $subscription = $circle->subscription;
    
    // Use subscription data for calculations (like private lesson page)
    if ($subscription) {
        $totalSessions = $subscription->total_sessions ?? 0;
        $completedSessions = $subscription->sessions_used ?? 0;
        $progressPercentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;
    } else {
        $totalSessions = 0;
        $completedSessions = 0;
        $progressPercentage = 0;
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

        @if($subscription)
            <!-- Subscription Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-information-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">حالة الاشتراك</span>
                </div>
                <span class="text-sm font-bold 
                    @if($subscription->subscription_status === 'active') text-green-600
                    @elseif($subscription->subscription_status === 'paused') text-yellow-600
                    @elseif($subscription->subscription_status === 'expired') text-blue-600
                    @elseif($subscription->subscription_status === 'cancelled') text-red-600
                    @elseif($subscription->subscription_status === 'suspended') text-orange-600
                    @else text-gray-600 @endif">
                    @if($subscription->subscription_status === 'active') نشط
                    @elseif($subscription->subscription_status === 'paused') متوقف
                    @elseif($subscription->subscription_status === 'expired') منتهي
                    @elseif($subscription->subscription_status === 'cancelled') ملغي
                    @elseif($subscription->subscription_status === 'suspended') موقف
                    @elseif($subscription->subscription_status === 'pending') في الانتظار
                    @else {{ $subscription->subscription_status }} @endif
                </span>
            </div>

            <!-- Subscription Start Date -->
            @if($subscription->created_at)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">تاريخ البداية</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $subscription->created_at->format('Y/m/d') }}</span>
                </div>
            @endif

            <!-- Expiry Date -->
            @if($subscription->expires_at)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">تاريخ الانتهاء</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $subscription->expires_at->format('Y/m/d') }}</span>
                </div>
            @endif
        @else
            <!-- No Subscription Message -->
            <div class="text-center py-6">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="ri-information-line text-2xl text-gray-400"></i>
                </div>
                <h4 class="text-sm font-medium text-gray-900 mb-1">لا يوجد اشتراك</h4>
                <p class="text-xs text-gray-600">لم يتم ربط اشتراك بهذه الحلقة بعد</p>
            </div>
        @endif
    </div>
</div> 