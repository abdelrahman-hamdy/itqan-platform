@props([
    'subscription',
    'compact' => false
])

@php
    use App\Enums\SubscriptionStatus;

    // Handle both enum and legacy string status
    $statusEnum = $subscription->status instanceof SubscriptionStatus
        ? $subscription->status
        : SubscriptionStatus::tryFrom($subscription->status) ?? SubscriptionStatus::PENDING;

    $teacher = $subscription->quranTeacher;
    $teacherName = $teacher?->full_name ?? $subscription->quranTeacherUser?->name ?? 'معلم غير محدد';
    $teacherAvatar = $teacher?->user?->avatar ?? $subscription->quranTeacherUser?->avatar ?? null;

    // Individual circle data
    $circle = $subscription->individualCircle;
    $hasCircle = $circle && $circle->id;

    // Calculate progress
    $sessionsUsed = $subscription->sessions_used ?? 0;
    $totalSessions = $subscription->total_sessions ?? 0;
    $sessionsRemaining = $subscription->sessions_remaining ?? 0;
    $progressPercentage = $totalSessions > 0 ? round(($sessionsUsed / $totalSessions) * 100, 1) : 0;

    // Package info
    $packageName = $subscription->package_name_ar ?? $subscription->package?->name_ar ?? $subscription->package?->name ?? 'اشتراك فردي';

    // Navigation
    $href = $hasCircle
        ? route('individual-circles.show', [
            'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
            'circle' => $circle->id
        ])
        : '#';
@endphp

<div class="individual-subscription-card {{ $compact ? 'p-4' : 'p-6' }} bg-white rounded-lg border border-gray-200 hover:border-blue-300 transition-all duration-200 {{ $hasCircle ? 'hover:shadow-md cursor-pointer' : 'opacity-90' }}">
    @if($hasCircle)
        <a href="{{ $href }}" class="block h-full">
    @endif

    <!-- Header with Teacher Info and Status -->
    <div class="flex items-start justify-between {{ $compact ? 'mb-3' : 'mb-4' }}">
        <div class="flex items-center space-x-3 space-x-reverse flex-1">
            <!-- Teacher Avatar -->
            @if($teacherAvatar)
                <img src="{{ asset('storage/' . $teacherAvatar) }}" alt="{{ $teacherName }}"
                     class="{{ $compact ? 'w-10 h-10' : 'w-12 h-12' }} rounded-full object-cover border-2 border-blue-100">
            @else
                <div class="{{ $compact ? 'w-10 h-10' : 'w-12 h-12' }} rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                    <span class="{{ $compact ? 'text-sm' : 'text-lg' }} font-bold text-white">
                        {{ mb_substr($teacherName, 0, 1) }}
                    </span>
                </div>
            @endif

            <!-- Teacher & Package Info -->
            <div class="flex-1 min-w-0">
                <h4 class="{{ $compact ? 'text-sm' : 'text-base' }} font-bold text-gray-900 truncate">
                    {{ $teacherName }}
                </h4>
                <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600 truncate">
                    <i class="ri-bookmark-line ml-1 text-blue-500"></i>
                    {{ $packageName }}
                </p>
                @if(!$compact)
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="ri-user-line ml-1"></i>
                        جلسات فردية (1 على 1)
                    </p>
                @endif
            </div>
        </div>

        <!-- Status Badge -->
        <div class="flex flex-col items-end space-y-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusEnum->badgeClasses() }}">
                {{ $statusEnum->label() }}
            </span>

            @if($hasCircle)
                <i class="ri-arrow-left-s-line text-gray-400 {{ $compact ? 'text-sm' : '' }}"></i>
            @endif
        </div>
    </div>

    <!-- Subscription Details -->
    <div class="{{ $compact ? 'space-y-2' : 'space-y-3' }}">
        <!-- Session Stats -->
        <div class="flex items-center justify-between {{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600">
            <span>
                <i class="ri-calendar-check-line ml-1 text-blue-500"></i>
                {{ $sessionsUsed }}/{{ $totalSessions }} جلسة
            </span>
            @if($progressPercentage > 0)
                <span class="font-medium">{{ number_format($progressPercentage, 1) }}%</span>
            @endif
        </div>

        <!-- Progress Bar -->
        @if($totalSessions > 0)
            <div class="w-full bg-gray-200 rounded-full {{ $compact ? 'h-1.5' : 'h-2' }}">
                <div class="bg-gradient-to-l from-blue-500 to-indigo-500 {{ $compact ? 'h-1.5' : 'h-2' }} rounded-full transition-all duration-300"
                     style="width: {{ min(100, max(0, $progressPercentage)) }}%"></div>
            </div>
        @endif

        <!-- Sessions Remaining -->
        @if(!$compact && $sessionsRemaining > 0)
            <div class="flex items-center justify-between text-xs text-gray-500">
                @if($circle?->last_session_at)
                    <span>
                        <i class="ri-time-line ml-1"></i>
                        آخر جلسة {{ $circle->last_session_at->diffForHumans() }}
                    </span>
                @else
                    <span></span>
                @endif

                <span class="text-blue-600 font-medium">
                    {{ $sessionsRemaining }} جلسة متبقية
                </span>
            </div>
        @endif

        <!-- Billing Info -->
        @if(!$compact)
            <div class="flex items-center justify-between text-xs text-gray-500 pt-2 border-t border-gray-100">
                @if($subscription->billing_cycle)
                    @php
                        $billingLabel = $subscription->billing_cycle instanceof \App\Enums\BillingCycle
                            ? $subscription->billing_cycle->label()
                            : match($subscription->billing_cycle) {
                                'monthly' => 'شهري',
                                'quarterly' => 'ربع سنوي',
                                'yearly' => 'سنوي',
                                default => $subscription->billing_cycle,
                            };
                    @endphp
                    <span>
                        <i class="ri-repeat-line ml-1"></i>
                        اشتراك {{ $billingLabel }}
                    </span>
                @endif

                @if($subscription->final_price)
                    <span class="font-medium text-gray-700">
                        {{ number_format($subscription->final_price) }} {{ $subscription->currency ?? 'SAR' }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    <!-- Warning for pending circle creation -->
    @if(!$hasCircle && $statusEnum === SubscriptionStatus::ACTIVE)
        <div class="mt-3 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800">
            <i class="ri-loader-4-line animate-spin ml-1"></i>
            جاري إعداد الحلقة الفردية...
        </div>
    @endif

    @if($hasCircle)
        </a>
    @endif
</div>
