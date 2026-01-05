@props([
    'subscription', // QuranSubscription, AcademicSubscription, or CourseSubscription model instance
    'viewType' => 'student', // 'student' or 'teacher'
])

@php
    use App\Services\QuranSubscriptionDetailsService;
    use App\Services\AcademicSubscriptionDetailsService;
    use App\Services\CourseSubscriptionDetailsService;

    $isTeacher = $viewType === 'teacher';

    // Determine which service to use based on subscription type
    $serviceClass = match (true) {
        $subscription instanceof \App\Models\QuranSubscription => QuranSubscriptionDetailsService::class,
        $subscription instanceof \App\Models\AcademicSubscription => AcademicSubscriptionDetailsService::class,
        $subscription instanceof \App\Models\CourseSubscription => CourseSubscriptionDetailsService::class,
        default => QuranSubscriptionDetailsService::class, // Fallback
    };

    $service = app($serviceClass);

    // Get subscription details from service
    $details = $subscription ? $service->getSubscriptionDetails($subscription) : null;

    // Get renewal message if applicable
    $renewalMessage = $subscription ? $service->getRenewalMessage($subscription) : null;

    // Formatted price
    $formattedPrice = $subscription ? $service->getFormattedPrice($subscription) : null;

    // Determine subscription type for dynamic colors
    $subscriptionType = null;
    if ($subscription instanceof \App\Models\QuranSubscription) {
        // Check if individual or group
        $subscriptionType = $subscription->individualCircle ? 'quran_individual' : 'quran_group';
    } elseif ($subscription instanceof \App\Models\AcademicSubscription) {
        $subscriptionType = 'academic_individual';
    }

    // Set colors based on subscription type
    $iconColor = match($subscriptionType) {
        'quran_individual' => 'text-yellow-500',
        'quran_group' => 'text-green-500',
        'academic_individual' => 'text-purple-500',
        default => 'text-purple-500',
    };

    $gradientFrom = match($subscriptionType) {
        'quran_individual' => 'from-yellow-50',
        'quran_group' => 'from-green-50',
        'academic_individual' => 'from-purple-50',
        default => 'from-blue-50',
    };

    $gradientTo = match($subscriptionType) {
        'quran_individual' => 'to-yellow-100',
        'quran_group' => 'to-green-100',
        'academic_individual' => 'to-purple-100',
        default => 'to-blue-100',
    };

    $borderColor = match($subscriptionType) {
        'quran_individual' => 'border-yellow-200',
        'quran_group' => 'border-green-200',
        'academic_individual' => 'border-purple-200',
        default => 'border-blue-200',
    };

    $textLightColor = match($subscriptionType) {
        'quran_individual' => 'text-yellow-700',
        'quran_group' => 'text-green-700',
        'academic_individual' => 'text-purple-700',
        default => 'text-blue-700',
    };

    $textDarkColor = match($subscriptionType) {
        'quran_individual' => 'text-yellow-900',
        'quran_group' => 'text-green-900',
        'academic_individual' => 'text-purple-900',
        default => 'text-blue-900',
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    @if($details)
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="ri-file-list-3-line {{ $iconColor }} text-lg" style="font-weight: 100;"></i>
            {{ __('components.circle.subscription_details.title') }}
        </h3>

        <!-- Subscription Status Badge -->
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm text-gray-600">{{ __('components.circle.subscription_details.status') }}</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $details['status_badge_class'] }}">
                {{ $service->getStatusTextArabic($details['status']) }}
            </span>
        </div>

        <!-- Billing Cycle -->
        <div class="mb-6 p-4 bg-gradient-to-r {{ $gradientFrom }} {{ $gradientTo }} rounded-lg border {{ $borderColor }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs {{ $textLightColor }} mb-1">{{ __('components.circle.subscription_details.subscription_type') }}</p>
                    <p class="text-lg font-bold {{ $textDarkColor }}">{{ $details['billing_cycle_ar'] }}</p>
                </div>
                <div class="text-end">
                    <p class="text-xs {{ $textLightColor }} mb-1">{{ __('components.circle.subscription_details.amount') }}</p>
                    <p class="text-lg font-bold {{ $textDarkColor }}">{{ $formattedPrice }}</p>
                </div>
            </div>
        </div>

        <!-- Sessions Progress -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">{{ __('components.circle.subscription_details.sessions_progress') }}</span>
                <span class="text-sm font-bold text-primary">{{ $details['sessions_percentage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-primary h-3 rounded-full transition-all duration-500"
                     style="width: {{ $details['sessions_percentage'] }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-600">
                <span>{{ $details['sessions_used'] }} {{ __('components.circle.subscription_details.used') }}</span>
                <span>{{ $details['sessions_remaining'] }} {{ __('components.circle.subscription_details.remaining') }}</span>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Sessions Used -->
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $details['sessions_used'] }}</div>
                <div class="text-xs text-green-700 font-medium">{{ __('components.circle.subscription_details.sessions_used') }}</div>
            </div>

            <!-- Sessions Remaining -->
            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">{{ $details['sessions_remaining'] }}</div>
                <div class="text-xs text-blue-700 font-medium">{{ __('components.circle.subscription_details.sessions_remaining') }}</div>
            </div>
        </div>

        <!-- Subscription Details -->
        <div class="space-y-3">
            <!-- Total Sessions -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-calendar-check-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                    <span class="text-sm text-gray-700">{{ __('components.circle.subscription_details.total_sessions') }}</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $details['total_sessions'] }} {{ __('components.circle.subscription_details.session_count') }}</span>
            </div>

            <!-- Payment Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-money-dollar-circle-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                    <span class="text-sm text-gray-700">{{ __('components.circle.subscription_details.payment_status') }}</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $details['payment_status_badge_class'] }}">
                    {{ $service->getPaymentStatusTextArabic($details['payment_status']) }}
                </span>
            </div>

            <!-- Start Date -->
            @if($details['starts_at'] ?? null)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.circle.subscription_details.start_date') }}</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $details['starts_at']->format('Y/m/d') }}</span>
                </div>
            @endif

            <!-- Next Payment Date -->
            @if(($details['next_payment_at'] ?? null) && ($details['status'] ?? '') === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.circle.subscription_details.next_renewal') }}</span>
                    </div>
                    <div class="text-end">
                        <span class="text-sm font-bold text-gray-900 block">{{ $details['next_payment_at']->format('Y/m/d') }}</span>
                        @if(($details['days_until_next_payment'] ?? null) !== null)
                            <span class="text-xs text-gray-600">
                                @if($details['days_until_next_payment'] > 0)
                                    {{ str_replace('{days}', $details['days_until_next_payment'], __('components.circle.subscription_details.days_after')) }}
                                @elseif($details['days_until_next_payment'] === 0)
                                    {{ __('components.circle.subscription_details.today') }}
                                @else
                                    {{ str_replace('{days}', abs($details['days_until_next_payment']), __('components.circle.subscription_details.overdue')) }}
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Auto-Renew Status -->
            @if(($details['status'] ?? '') === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-refresh-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.circle.subscription_details.auto_renew') }}</span>
                    </div>
                    <span class="text-sm font-bold {{ $details['auto_renew'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $details['auto_renew'] ? __('components.circle.subscription_details.enabled') : __('components.circle.subscription_details.disabled') }}
                    </span>
                </div>
            @endif

            <!-- Next Billing Date / End Date -->
            @if($subscription && ($subscription->next_billing_date ?? $subscription->expires_at ?? null))
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-close-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">
                            {{ $subscription->next_billing_date ? __('components.circle.subscription_details.next_billing_date') : __('components.circle.subscription_details.end_date') }}
                        </span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">
                        {{ ($subscription->next_billing_date ?? $subscription->expires_at)->format('Y/m/d') }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Renewal Warning/Message -->
        @if($renewalMessage)
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-information-line text-yellow-600 text-lg ms-2 rtl:ms-2 ltr:me-2 mt-0.5"></i>
                    <p class="text-sm text-yellow-800">{{ $renewalMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Trial Info (if applicable) -->
        @if($details['is_trial_active'] ?? false)
            <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-gift-line text-purple-600 text-lg ms-2 rtl:ms-2 ltr:me-2 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-purple-900 mb-1">{{ __('components.circle.subscription_details.trial_active') }}</p>
                        <p class="text-xs text-purple-700">{{ __('components.circle.subscription_details.trial_message') }}</p>
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
            <h4 class="text-base font-medium text-gray-900 mb-2">{{ __('components.circle.subscription_details.no_subscription') }}</h4>
            <p class="text-sm text-gray-600">
                {{ __('components.circle.subscription_details.no_subscription_message') }}
            </p>
        </div>
    @endif
</div>
