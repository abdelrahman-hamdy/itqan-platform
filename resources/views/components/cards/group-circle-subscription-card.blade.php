@props([
    'subscription',
    'circle' => null,
    'compact' => false
])

@php
    use App\Enums\SubscriptionStatus;

    // Status is automatically cast to SubscriptionStatus enum by the model
    $statusEnum = $subscription->status ?? SubscriptionStatus::PENDING;

    $teacher = $subscription->quranTeacher;
    $teacherName = $teacher?->full_name ?? $subscription->quranTeacherUser?->name ?? 'معلم غير محدد';

    // Get circle info if available
    $circleName = $circle?->name_ar ?? $circle?->name ?? 'حلقة قرآنية';
    $circleDescription = $circle?->description_ar ?? $circle?->description ?? null;
    $studentsCount = $circle?->students?->count() ?? $circle?->enrolled_students ?? 0;
    $maxStudents = $circle?->max_students ?? 10;

    // Calculate progress
    $sessionsUsed = $subscription->sessions_used ?? 0;
    $totalSessions = $subscription->total_sessions ?? 0;
    $sessionsRemaining = $subscription->sessions_remaining ?? 0;
    $progressPercentage = $totalSessions > 0 ? round(($sessionsUsed / $totalSessions) * 100, 1) : 0;

    // Navigation
    $canAccess = $circle && $circle->id;
    $href = $canAccess
        ? route('quran-circles.show', [
            'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
            'circleId' => $circle->id
        ])
        : '#';
@endphp

<div class="group-circle-card {{ $compact ? 'p-4' : 'p-6' }} bg-white rounded-lg border border-gray-200 hover:border-emerald-300 transition-all duration-200 {{ $canAccess ? 'hover:shadow-md cursor-pointer' : '' }}">
    @if($canAccess)
        <a href="{{ $href }}" class="block h-full">
    @endif

    <!-- Header with Circle Name and Status -->
    <div class="flex items-start justify-between {{ $compact ? 'mb-3' : 'mb-4' }}">
        <div class="flex items-center space-x-3 space-x-reverse flex-1">
            <!-- Circle Icon -->
            <div class="{{ $compact ? 'w-10 h-10' : 'w-12 h-12' }} rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                <i class="ri-group-line text-white {{ $compact ? 'text-lg' : 'text-xl' }}"></i>
            </div>

            <!-- Circle Info -->
            <div class="flex-1 min-w-0">
                <h4 class="{{ $compact ? 'text-sm' : 'text-base' }} font-bold text-gray-900 truncate">
                    {{ $circleName }}
                </h4>
                <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600 truncate">
                    <i class="ri-user-star-line ml-1 text-emerald-500"></i>
                    {{ $teacherName }}
                </p>
                @if(!$compact && $studentsCount > 0)
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="ri-group-2-line ml-1"></i>
                        {{ $studentsCount }} / {{ $maxStudents }} طالب
                    </p>
                @endif
            </div>
        </div>

        <!-- Status Badge -->
        <div class="flex flex-col items-end space-y-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusEnum->badgeClasses() }}">
                {{ $statusEnum->label() }}
            </span>

            @if($canAccess)
                <i class="ri-arrow-left-s-line text-gray-400 {{ $compact ? 'text-sm' : '' }}"></i>
            @endif
        </div>
    </div>

    <!-- Circle Description -->
    @if(!$compact && $circleDescription)
        <p class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ $circleDescription }}
        </p>
    @endif

    <!-- Subscription Details -->
    <div class="{{ $compact ? 'space-y-2' : 'space-y-3' }}">
        <!-- Session Stats -->
        <div class="flex items-center justify-between {{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600">
            <span>
                <i class="ri-calendar-check-line ml-1 text-emerald-500"></i>
                {{ $sessionsUsed }}/{{ $totalSessions }} جلسة مستخدمة
            </span>
            @if($sessionsRemaining > 0)
                <span class="font-medium text-emerald-600">
                    {{ $sessionsRemaining }} متبقية
                </span>
            @endif
        </div>

        <!-- Progress Bar -->
        @if($totalSessions > 0)
            <div class="w-full bg-gray-200 rounded-full {{ $compact ? 'h-1.5' : 'h-2' }}">
                <div class="bg-gradient-to-l from-emerald-500 to-teal-500 {{ $compact ? 'h-1.5' : 'h-2' }} rounded-full transition-all duration-300"
                     style="width: {{ min(100, max(0, $progressPercentage)) }}%"></div>
            </div>
        @endif

        <!-- Schedule Info -->
        @if(!$compact && $circle)
            <div class="flex flex-wrap gap-2 pt-2">
                @if($circle->schedule_days && is_array($circle->schedule_days))
                    @php
                        $dayLabels = [
                            'sunday' => 'الأحد',
                            'monday' => 'الاثنين',
                            'tuesday' => 'الثلاثاء',
                            'wednesday' => 'الأربعاء',
                            'thursday' => 'الخميس',
                            'friday' => 'الجمعة',
                            'saturday' => 'السبت',
                        ];
                    @endphp
                    @foreach($circle->schedule_days as $day)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                            {{ $dayLabels[$day] ?? $day }}
                        </span>
                    @endforeach
                @endif

                @if($circle->start_time)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-emerald-50 text-emerald-700">
                        <i class="ri-time-line ml-1"></i>
                        {{ \Carbon\Carbon::parse($circle->start_time)->format('h:i A') }}
                    </span>
                @endif
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

                @if($subscription->next_billing_date ?? $subscription->next_payment_at)
                    <span class="text-amber-600">
                        <i class="ri-calendar-line ml-1"></i>
                        التجديد: {{ ($subscription->next_billing_date ?? $subscription->next_payment_at)?->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    @if($canAccess)
        </a>
    @endif
</div>
