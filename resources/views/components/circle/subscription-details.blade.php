@props([
    'subscription', // QuranSubscription model instance
    'viewType' => 'student', // 'student' or 'teacher'
])

@php
    use App\Services\QuranSubscriptionDetailsService;

    $isTeacher = $viewType === 'teacher';
    $service = app(QuranSubscriptionDetailsService::class);

    // Get subscription details from service
    $details = $subscription ? $service->getSubscriptionDetails($subscription) : null;

    // Get renewal message if applicable
    $renewalMessage = $subscription ? $service->getRenewalMessage($subscription) : null;

    // Formatted price
    $formattedPrice = $subscription ? $service->getFormattedPrice($subscription) : null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    @if($details)
        <h3 class="font-bold text-gray-900 mb-4">تفاصيل الاشتراك</h3>

        <!-- Subscription Status Badge -->
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm text-gray-600">حالة الاشتراك</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $details['status_badge_class'] }}">
                {{ $service->getStatusTextArabic($details['subscription_status']) }}
            </span>
        </div>

        <!-- Billing Cycle -->
        <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-700 mb-1">نوع الاشتراك</p>
                    <p class="text-lg font-bold text-blue-900">{{ $details['billing_cycle_ar'] }}</p>
                </div>
                <div class="text-left">
                    <p class="text-xs text-blue-700 mb-1">المبلغ</p>
                    <p class="text-lg font-bold text-blue-900">{{ $formattedPrice }}</p>
                </div>
            </div>
        </div>

        <!-- Sessions Progress -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">تقدم الجلسات</span>
                <span class="text-sm font-bold text-primary">{{ $details['sessions_percentage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-primary h-3 rounded-full transition-all duration-500"
                     style="width: {{ $details['sessions_percentage'] }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-600">
                <span>{{ $details['sessions_used'] }} مستخدمة</span>
                <span>{{ $details['sessions_remaining'] }} متبقية</span>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Sessions Used -->
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $details['sessions_used'] }}</div>
                <div class="text-xs text-green-700 font-medium">جلسة مستخدمة</div>
            </div>

            <!-- Sessions Remaining -->
            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">{{ $details['sessions_remaining'] }}</div>
                <div class="text-xs text-blue-700 font-medium">جلسة متبقية</div>
            </div>
        </div>

        <!-- Subscription Details -->
        <div class="space-y-3">
            <!-- Total Sessions -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-calendar-check-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">إجمالي الجلسات</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $details['total_sessions'] }} جلسة</span>
            </div>

            <!-- Payment Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-money-dollar-circle-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">حالة الدفع</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $details['payment_status_badge_class'] }}">
                    {{ $service->getPaymentStatusTextArabic($details['payment_status']) }}
                </span>
            </div>

            <!-- Start Date -->
            @if($details['starts_at'])
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">تاريخ البداية</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $details['starts_at']->format('Y/m/d') }}</span>
                </div>
            @endif

            <!-- Next Payment Date -->
            @if($details['next_payment_at'] && $details['subscription_status'] === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">التجديد القادم</span>
                    </div>
                    <div class="text-left">
                        <span class="text-sm font-bold text-gray-900 block">{{ $details['next_payment_at']->format('Y/m/d') }}</span>
                        @if($details['days_until_next_payment'] !== null)
                            <span class="text-xs text-gray-600">
                                @if($details['days_until_next_payment'] > 0)
                                    بعد {{ $details['days_until_next_payment'] }} يوم
                                @elseif($details['days_until_next_payment'] === 0)
                                    اليوم
                                @else
                                    متأخر {{ abs($details['days_until_next_payment']) }} يوم
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Auto-Renew Status -->
            @if($details['subscription_status'] === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-refresh-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">التجديد التلقائي</span>
                    </div>
                    <span class="text-sm font-bold {{ $details['auto_renew'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $details['auto_renew'] ? 'مفعّل' : 'معطّل' }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Renewal Warning/Message -->
        @if($renewalMessage)
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-information-line text-yellow-600 text-lg ml-2 mt-0.5"></i>
                    <p class="text-sm text-yellow-800">{{ $renewalMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Trial Info (if applicable) -->
        @if($details['is_trial_active'])
            <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-gift-line text-purple-600 text-lg ml-2 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-purple-900 mb-1">فترة تجريبية نشطة</p>
                        <p class="text-xs text-purple-700">أنت حالياً في الفترة التجريبية المجانية</p>
                    </div>
                </div>
            </div>
        @endif

    @else
        <!-- No Subscription Message -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-information-line text-3xl text-gray-400"></i>
            </div>
            <h4 class="text-base font-medium text-gray-900 mb-2">لا يوجد اشتراك نشط</h4>
            <p class="text-sm text-gray-600">
                لم يتم ربط اشتراك بهذه الحلقة بعد
            </p>
        </div>
    @endif
</div>
