@php
use App\Enums\SessionStatus;

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.subscriptions.title')">
    <div class="space-y-4 md:space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('parent.subscriptions.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] inline-flex items-center text-blue-600 hover:text-blue-700 font-bold text-sm md:text-base">
                <i class="ri-arrow-right-line ms-1.5 md:ms-2"></i>
                {{ __('parent.subscriptions.back_to_subscriptions') }}
            </a>
        </div>

        <!-- Subscription Header -->
        <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 md:gap-4">
                <div class="flex items-start gap-3 md:gap-4">
                    @if($type === 'quran')
                        <div class="bg-green-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                            <i class="ri-book-read-line text-2xl md:text-3xl text-green-600"></i>
                        </div>
                    @elseif($type === 'academic')
                        <div class="bg-blue-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                            <i class="ri-book-2-line text-2xl md:text-3xl text-blue-600"></i>
                        </div>
                    @else
                        <div class="bg-purple-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                            <i class="ri-video-line text-2xl md:text-3xl text-purple-600"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl md:text-3xl font-bold text-gray-900">
                            @if($type === 'quran')
                                {{ $subscription->package->name ?? __('parent.subscriptions.quran_subscription') }}
                            @elseif($type === 'academic')
                                {{ $subscription->subject_name ?? __('parent.subscriptions.academic_subscription') }}
                            @else
                                {{ $subscription->recordedCourse?->title ?? $subscription->interactiveCourse?->title ?? __('parent.subscriptions.course_subscription') }}
                            @endif
                        </h1>
                        <p class="text-sm md:text-base text-gray-600 mt-0.5 md:mt-1">
                            @if($type === 'quran')
                                {{ $subscription->subscription_type === 'individual' ? __('parent.subscriptions.individual') : __('parent.subscriptions.group_circle') }}
                            @elseif($type === 'academic')
                                {{ $subscription->grade_level_name ?? __('parent.subscriptions.level') }}
                            @else
                                {{ __('parent.subscriptions.educational_course') }}
                            @endif
                        </p>
                    </div>
                </div>
                <span class="self-start px-3 md:px-4 py-1.5 md:py-2 text-xs md:text-sm font-bold rounded-full flex-shrink-0
                    {{ $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'bg-green-100 text-green-800' : '' }}
                    {{ $subscription->status === \App\Enums\SubscriptionStatus::EXPIRED->value ? 'bg-red-100 text-red-800' : '' }}
                    {{ $subscription->status === \App\Enums\SubscriptionStatus::PENDING->value ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE->value ? __('parent.subscriptions.status.active') : ($subscription->status === \App\Enums\SubscriptionStatus::EXPIRED->value ? __('parent.subscriptions.status.expired') : __('parent.subscriptions.status.pending')) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4 md:space-y-6">
                <!-- Subscription Details -->
                <div class="bg-white rounded-lg md:rounded-xl shadow">
                    <div class="p-4 md:p-6 border-b border-gray-200">
                        <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.subscriptions.subscription_details') }}</h2>
                    </div>
                    <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                        <!-- Student -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-blue-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-user-smile-line text-lg md:text-xl text-blue-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.subscriptions.student') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $subscription->student->name ?? '-' }}</p>
                            </div>
                        </div>

                        @if($type === 'quran' || $type === 'academic')
                            <!-- Teacher -->
                            <div class="flex items-center gap-2.5 md:gap-3">
                                <div class="bg-green-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                    <i class="ri-user-line text-lg md:text-xl text-green-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('parent.subscriptions.teacher') }}</p>
                                    <p class="font-bold text-sm md:text-base text-gray-900 truncate">
                                        @if($type === 'quran')
                                            {{ $subscription->quranTeacher->user->name ?? '-' }}
                                        @else
                                            {{ $subscription->academicTeacher->user->name ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Dates -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-purple-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-calendar-line text-lg md:text-xl text-purple-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.subscriptions.start_date') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900">
                                    @if($type === 'course')
                                        {{ $subscription->enrolled_at?->format('Y/m/d') ?? '-' }}
                                    @else
                                        {{ $subscription->start_date?->format('Y/m/d') ?? '-' }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if(($type !== 'course' && $subscription->end_date) || ($type === 'course' && $subscription->expires_at))
                            <div class="flex items-center gap-2.5 md:gap-3">
                                <div class="bg-yellow-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                    <i class="ri-calendar-check-line text-lg md:text-xl text-yellow-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('parent.subscriptions.end_date') }}</p>
                                    <p class="font-bold text-sm md:text-base text-gray-900">
                                        @if($type === 'course')
                                            {{ $subscription->expires_at?->format('Y/m/d') ?? '-' }}
                                        @else
                                            {{ $subscription->end_date?->format('Y/m/d') ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Progress & Stats -->
                <div class="bg-white rounded-lg md:rounded-xl shadow">
                    <div class="p-4 md:p-6 border-b border-gray-200">
                        <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.subscriptions.stats_and_progress') }}</h2>
                    </div>
                    <div class="p-4 md:p-6">
                        @if($type === 'quran')
                            <div class="grid grid-cols-2 gap-3 md:gap-6">
                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                                    <i class="ri-calendar-line text-2xl md:text-4xl text-green-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.total_sessions') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">{{ $subscription->total_sessions }}</p>
                                </div>

                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                                    <i class="ri-timer-line text-2xl md:text-4xl text-blue-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.remaining_sessions') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">{{ $subscription->sessions_remaining }}</p>
                                </div>

                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg">
                                    <i class="ri-check-line text-2xl md:text-4xl text-purple-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.completed_sessions') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">{{ $subscription->total_sessions - $subscription->sessions_remaining }}</p>
                                </div>

                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg">
                                    <i class="ri-percent-line text-2xl md:text-4xl text-yellow-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.completion_percentage') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">
                                        {{ $subscription->total_sessions > 0 ? round((($subscription->total_sessions - $subscription->sessions_remaining) / $subscription->total_sessions) * 100) : 0 }}%
                                    </p>
                                </div>
                            </div>
                        @elseif($type === 'academic')
                            <div class="grid grid-cols-2 gap-3 md:gap-6">
                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                                    <i class="ri-calendar-check-line text-2xl md:text-4xl text-blue-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.completed_lessons') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">{{ $subscription->total_sessions_completed ?? 0 }}</p>
                                </div>

                                <div class="text-center p-3 md:p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                                    <i class="ri-time-line text-2xl md:text-4xl text-green-600 mb-1.5 md:mb-3"></i>
                                    <p class="text-[10px] md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.subscriptions.total_hours') }}</p>
                                    <p class="text-xl md:text-4xl font-bold text-gray-900">{{ $subscription->total_hours ?? 0 }}</p>
                                </div>
                            </div>
                        @else
                            <div class="space-y-3 md:space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5 md:mb-2">
                                        <span class="text-sm md:text-base text-gray-700 font-bold">{{ __('parent.subscriptions.progress_percentage') }}</span>
                                        <span class="text-lg md:text-2xl font-bold text-purple-600">{{ $subscription->progress_percentage ?? 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3 md:h-4">
                                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 md:h-4 rounded-full" style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Sessions/Activities -->
                @if($recentSessions && $recentSessions->isNotEmpty())
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.subscriptions.recent_sessions') }}</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($recentSessions->take(5) as $session)
                                <div class="p-3 md:p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 md:gap-3 min-w-0">
                                            <i class="ri-calendar-event-line text-lg md:text-xl text-blue-600 flex-shrink-0"></i>
                                            <div class="min-w-0">
                                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">
                                                    {{ formatDateArabic($session->scheduled_at) }}
                                                </p>
                                                <p class="text-xs md:text-sm text-gray-600">{{ formatTimeArabic($session->scheduled_at) }}</p>
                                            </div>
                                        </div>
                                        <span class="px-2 md:px-3 py-0.5 md:py-1 text-[10px] md:text-xs font-bold rounded-full flex-shrink-0
                                            {{ $session->status === SessionStatus::COMPLETED->value ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $session->status === SessionStatus::SCHEDULED->value ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $session->status === SessionStatus::CANCELLED->value ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ $session->status === SessionStatus::COMPLETED->value ? __('parent.subscriptions.session_status.completed') : ($session->status === SessionStatus::SCHEDULED->value ? __('parent.subscriptions.session_status.scheduled') : __('parent.subscriptions.session_status.cancelled')) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-4 md:space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('parent.subscriptions.quick_actions') }}</h3>
                    <div class="space-y-2">
                        @if($type === 'quran' || $type === 'academic')
                            <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                <div class="flex items-center gap-2">
                                    <i class="ri-calendar-event-line text-blue-600"></i>
                                    <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.subscriptions.upcoming_sessions') }}</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>

                            <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                <div class="flex items-center gap-2">
                                    <i class="ri-history-line text-gray-600"></i>
                                    <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.subscriptions.session_history') }}</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>
                        @endif

                        <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-money-dollar-circle-line text-green-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.subscriptions.payment_history') }}</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>

                <!-- Subscription Status -->
                <div class="bg-gradient-to-br {{ $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'from-green-500 to-green-600' : ($subscription->status === \App\Enums\SubscriptionStatus::EXPIRED->value ? 'from-red-500 to-red-600' : 'from-yellow-500 to-yellow-600') }} rounded-lg md:rounded-xl shadow-lg p-4 md:p-6 text-white">
                    <h3 class="text-sm md:text-lg font-bold mb-3 md:mb-4">{{ __('parent.subscriptions.subscription_status') }}</h3>
                    <div class="text-center">
                        <i class="ri-{{ $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE->value ? 'checkbox-circle' : ($subscription->status === \App\Enums\SubscriptionStatus::EXPIRED->value ? 'close-circle' : 'time') }}-line text-4xl md:text-6xl mb-2 md:mb-3 opacity-80"></i>
                        <p class="text-xl md:text-2xl font-bold">
                            {{ $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE->value ? __('parent.subscriptions.status.active') : ($subscription->status === \App\Enums\SubscriptionStatus::EXPIRED->value ? __('parent.subscriptions.status.expired') : __('parent.subscriptions.status.pending')) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
