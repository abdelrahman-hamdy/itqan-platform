@props([
    'circle',
    'viewType' => 'student', // 'student' or 'teacher'
    'type' => 'quran' // 'quran' or 'academic'
])

@php
    $isTeacher = $viewType === 'teacher';
    $isAcademic = $type === 'academic';
    
    // For academic subscriptions, the $circle IS the subscription
    // For Quran circles, the subscription is nested under $circle->subscription
    if ($isAcademic) {
        $subscription = $circle; // $circle is actually the AcademicSubscription
        $totalSessions = $subscription->sessions_per_month ?? 0;
        $completedSessions = $subscription->sessions->where('status', 'completed')->count() ?? 0;
    } else {
        $subscription = $circle->subscription;
        $totalSessions = $circle->total_sessions ?? 0;
        $completedSessions = $circle->sessions_completed ?? 0;
    }
    
    $progressPercentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;
    $remainingSessions = max(0, $totalSessions - $completedSessions);
    $hasValidSubscription = $isAcademic ? ($subscription && $subscription->id) : ($subscription && !empty($subscription));
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

        @if($hasValidSubscription)
            <!-- Subscription Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-information-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">حالة الاشتراك</span>
                </div>
                <span class="text-sm font-bold 
                    @if(($isAcademic ? $subscription->status : $subscription->status) === 'active') text-green-600
                    @elseif(($isAcademic ? $subscription->status : $subscription->status) === 'paused') text-yellow-600
                    @elseif(($isAcademic ? $subscription->status : $subscription->status) === 'expired') text-blue-600
                    @elseif(($isAcademic ? $subscription->status : $subscription->status) === 'cancelled') text-red-600
                    @elseif(($isAcademic ? $subscription->status : $subscription->status) === 'suspended') text-orange-600
                    @else text-gray-600 @endif">
                    @php $status = $isAcademic ? $subscription->status : $subscription->status; @endphp
                    @if($status === 'active') نشط
                    @elseif($status === 'paused') متوقف
                    @elseif($status === 'expired') منتهي
                    @elseif($status === 'cancelled') ملغي
                    @elseif($status === 'suspended') موقف
                    @elseif($status === 'pending') في الانتظار
                    @else {{ $status }} @endif
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
                <h4 class="text-sm font-medium text-gray-900 mb-1">{{ $isAcademic ? 'لا يوجد اشتراك' : 'لا يوجد اشتراك' }}</h4>
                <p class="text-xs text-gray-600">
                    {{ $isAcademic ? 'لم يتم ربط اشتراك بهذا الدرس بعد' : 'لم يتم ربط اشتراك بهذه الحلقة بعد' }}
                </p>
            </div>
        @endif
    </div>
</div> 