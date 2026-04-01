<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $studentName = $type === 'quran' ? ($subscription->student?->name ?? '-') : ($subscription->student?->name ?? '-');
    $teacherName = $type === 'quran' ? ($subscription->quranTeacherUser?->name ?? '-') : ($subscription->teacher?->user?->name ?? '-');

    $sessionsUsed = $subscription->sessions_used ?? 0;
    $sessionsTotal = $subscription->total_sessions ?? 0;
    $sessionsRemaining = $subscription->sessions_remaining ?? ($sessionsTotal - $sessionsUsed);
    $progressPct = $sessionsTotal > 0 ? min(100, round(($sessionsUsed / $sessionsTotal) * 100)) : 0;
@endphp

<div class="max-w-5xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName],
        ]"
        view-type="supervisor"
    />

    @php
        // Type label matching index page
        $subType = $type === 'quran' ? ($subscription->subscription_type ?? 'individual') : 'academic';
        $typeLabel = match($subType) {
            'individual' => __('supervisor.subscriptions.type_quran_individual'),
            'group' => __('supervisor.subscriptions.type_quran_group'),
            default => __('supervisor.subscriptions.type_academic'),
        };
        $typeColor = $type === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700';
        $typeIcon = match($subType) {
            'individual' => 'ri-user-line',
            'group' => 'ri-group-line',
            default => 'ri-graduation-cap-line',
        };
    @endphp

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('supervisor.subscriptions.show_title') }}</h1>
            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $typeColor }}">
                <i class="{{ $typeIcon }}"></i>
                {{ $typeLabel }}
            </span>
            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $subscription->status->badgeClasses() }}">
                {{ $subscription->status->label() }}
            </span>
            @if($subscription->is_sessions_exhausted)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">
                    <i class="ri-check-double-line"></i>
                    {{ __('supervisor.subscriptions.sessions_exhausted_badge') }}
                </span>
            @endif
        </div>
        <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
           class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium whitespace-nowrap">
            <i class="ri-arrow-right-line"></i>
            {{ __('supervisor.subscriptions.page_title') }}
        </a>
    </div>

    <!-- Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.col_student') }}</p>
                <p class="text-sm font-semibold text-gray-900">{{ $studentName }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.col_teacher') }}</p>
                <p class="text-sm font-semibold text-gray-900">{{ $teacherName }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.col_sessions') }}</p>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $sessionsUsed }} / {{ $sessionsTotal }}
                    <span class="text-xs text-gray-500 font-normal">({{ __('supervisor.subscriptions.remaining') }}: {{ $sessionsRemaining }})</span>
                </p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                    <div class="h-1.5 rounded-full transition-all {{ $progressPct >= 80 ? 'bg-red-500' : ($progressPct >= 50 ? 'bg-amber-500' : 'bg-blue-500') }}"
                         style="width: {{ $progressPct }}%"></div>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.col_dates') }}</p>
                <p class="text-sm font-semibold text-gray-900">{{ $subscription->starts_at?->format('Y-m-d') ?? '-' }}</p>
                <p class="text-xs text-gray-500">{{ __('supervisor.subscriptions.to') }} {{ $subscription->ends_at?->format('Y-m-d') ?? '-' }}</p>
            </div>
            @if($subscription->isInGracePeriod())
                @php
                    $graceMeta = $subscription->metadata ?? [];
                    $graceEnd = \Carbon\Carbon::parse($graceMeta['grace_period_ends_at']);
                    $extensionDays = (int) ($subscription->ends_at ?? now())->diffInDays($graceEnd, false);
                @endphp
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.grace_period_until', ['date' => $graceEnd->format('Y-m-d')]) }}</p>
                    <p class="text-sm font-semibold text-orange-600">
                        <i class="ri-timer-line"></i>
                        {{ __('supervisor.subscriptions.extended_for_days', ['days' => max(0, $extensionDays)]) }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    @if($isAdmin)
        <div x-data="{ expanded: false }" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <button @click="expanded = !expanded" type="button"
                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <i class="ri-apps-line"></i>
                {{ __('supervisor.subscriptions.quick_actions') }}
                <i class="ri-arrow-down-s-line transition-transform" :class="{ 'rotate-180': expanded }"></i>
            </button>
            <div x-show="expanded" x-collapse>
                <div class="flex flex-wrap gap-2 mt-3">
                    @if($subscription->status === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                        <form id="show-pause-form" method="POST" action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_pause')),
                                message: @js(__('supervisor.subscriptions.confirm_pause')),
                                confirmText: @js(__('supervisor.subscriptions.action_pause')),
                                isDangerous: false,
                                icon: 'ri-pause-circle-line',
                                onConfirm: () => document.getElementById('show-pause-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600 transition-colors cursor-pointer">
                            <i class="ri-pause-circle-line"></i>{{ __('supervisor.subscriptions.action_pause') }}
                        </button>
                    @endif
                    @if($subscription->status === \App\Enums\SessionSubscriptionStatus::PAUSED)
                        <form id="show-resume-form" method="POST" action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_resume')),
                                message: @js(__('supervisor.subscriptions.confirm_resume')),
                                confirmText: @js(__('supervisor.subscriptions.action_resume')),
                                isDangerous: false,
                                icon: 'ri-play-circle-line',
                                onConfirm: () => document.getElementById('show-resume-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors cursor-pointer">
                            <i class="ri-play-circle-line"></i>{{ __('supervisor.subscriptions.action_resume') }}
                        </button>
                    @endif
                    @if(in_array($subscription->status, [\App\Enums\SessionSubscriptionStatus::CANCELLED, \App\Enums\SessionSubscriptionStatus::EXPIRED]))
                        <form id="show-activate-form" method="POST" action="{{ route('manage.subscriptions.activate', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_activate')),
                                message: @js(__('supervisor.subscriptions.confirm_activate')),
                                confirmText: @js(__('supervisor.subscriptions.action_activate')),
                                isDangerous: false,
                                icon: 'ri-checkbox-circle-line',
                                onConfirm: () => document.getElementById('show-activate-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition-colors cursor-pointer">
                            <i class="ri-checkbox-circle-line"></i>{{ __('supervisor.subscriptions.action_activate') }}
                        </button>
                    @endif
                    @if($subscription->status->canCancel())
                        <form id="show-cancel-form" method="POST" action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_cancel')),
                                message: @js(__('supervisor.subscriptions.confirm_cancel')),
                                confirmText: @js(__('supervisor.subscriptions.action_cancel')),
                                isDangerous: true,
                                icon: 'ri-close-circle-line',
                                onConfirm: () => document.getElementById('show-cancel-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors cursor-pointer">
                            <i class="ri-close-circle-line"></i>{{ __('supervisor.subscriptions.action_cancel') }}
                        </button>
                    @endif

                    {{-- Extend button --}}
                    <button type="button"
                        onclick="document.getElementById('show-extend-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition-colors cursor-pointer">
                        <i class="ri-calendar-check-line"></i>{{ __('supervisor.subscriptions.action_extend') }}
                    </button>

                    {{-- Confirm Payment button (when payment pending/failed) --}}
                    @if(in_array($subscription->payment_status, [\App\Enums\SubscriptionPaymentStatus::PENDING, \App\Enums\SubscriptionPaymentStatus::FAILED]))
                        <button type="button"
                            onclick="document.getElementById('show-confirm-payment-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700 transition-colors cursor-pointer">
                            <i class="ri-check-double-line"></i>{{ __('supervisor.subscriptions.action_confirm_payment') }}
                        </button>
                    @endif

                    {{-- Renew button (when subscription can be renewed) --}}
                    @if($subscription->canRenew() || $subscription->is_sessions_exhausted)
                        <button type="button"
                            onclick="document.getElementById('show-renew-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors cursor-pointer">
                            <i class="ri-refresh-line"></i>{{ __('supervisor.subscriptions.action_renew') }}
                        </button>
                    @endif

                    {{-- Resubscribe button (when cancelled or expired) --}}
                    @if(in_array($subscription->status, [\App\Enums\SessionSubscriptionStatus::CANCELLED, \App\Enums\SessionSubscriptionStatus::EXPIRED]))
                        <button type="button"
                            onclick="document.getElementById('show-resubscribe-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700 transition-colors cursor-pointer">
                            <i class="ri-arrow-go-back-line"></i>{{ __('supervisor.subscriptions.action_resubscribe') }}
                        </button>
                    @endif

                    {{-- Cancel Extension button (only when in grace period) --}}
                    @if($subscription->isInGracePeriod())
                        <form id="show-cancel-extension-form" method="POST"
                              action="{{ route('manage.subscriptions.cancel-extension', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_cancel_extension')),
                                message: @js(__('supervisor.subscriptions.confirm_cancel_extension')),
                                confirmText: @js(__('supervisor.subscriptions.action_cancel_extension')),
                                isDangerous: true,
                                icon: 'ri-calendar-close-line',
                                onConfirm: () => document.getElementById('show-cancel-extension-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700 transition-colors cursor-pointer">
                            <i class="ri-calendar-close-line"></i>{{ __('supervisor.subscriptions.action_cancel_extension') }}
                        </button>
                    @endif

                    {{-- Cancel Pending (only when PENDING) --}}
                    @if($subscription->isPending())
                        <form id="show-cancel-pending-form" method="POST" action="{{ route('manage.subscriptions.cancel-pending', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.subscriptions.action_cancel_pending')),
                                message: @js(__('supervisor.subscriptions.confirm_cancel_pending')),
                                confirmText: @js(__('supervisor.subscriptions.action_cancel_pending')),
                                isDangerous: true,
                                icon: 'ri-close-circle-line',
                                onConfirm: () => document.getElementById('show-cancel-pending-form').submit()
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors cursor-pointer">
                            <i class="ri-close-circle-line"></i>{{ __('supervisor.subscriptions.action_cancel_pending') }}
                        </button>
                    @endif

                    {{-- Create Circle (Quran individual without circle) --}}
                    @if($type === 'quran' && ($subscription->subscription_type ?? '') === 'individual' && !$subscription->education_unit_id)
                        <button type="button"
                            onclick="document.getElementById('show-create-circle-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition-colors cursor-pointer">
                            <i class="ri-add-circle-line"></i>{{ __('supervisor.subscriptions.action_create_circle') }}
                        </button>
                    @endif

                    {{-- Delete Subscription (admin only, dangerous) --}}
                    <form id="show-delete-form" method="POST" action="{{ route('manage.subscriptions.destroy', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button"
                        onclick="window.confirmAction({
                            title: @js(__('supervisor.subscriptions.action_delete')),
                            message: @js(__('supervisor.subscriptions.confirm_delete')),
                            confirmText: @js(__('supervisor.subscriptions.action_delete')),
                            isDangerous: true,
                            icon: 'ri-delete-bin-line',
                            onConfirm: () => document.getElementById('show-delete-form').submit()
                        })"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-700 text-white rounded-lg text-sm hover:bg-red-800 transition-colors cursor-pointer">
                        <i class="ri-delete-bin-line"></i>{{ __('supervisor.subscriptions.action_delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Session History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">{{ __('supervisor.subscriptions.session_history') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.session_date') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.session_status') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.subscriptions.session_duration') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $session->scheduled_at?->format('Y-m-d H:i') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : $session->status;
                                    $statusClasses = match($statusValue) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'scheduled' => 'bg-blue-100 text-blue-700',
                                        'live', 'ongoing' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full {{ $statusClasses }}">
                                    {{ $statusValue }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                {{ $session->actual_duration ?? $session->duration_minutes ?? '-' }} {{ __('supervisor.subscriptions.minutes') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-8 text-gray-500">
                                {{ __('supervisor.subscriptions.no_sessions') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Extend Modal --}}
@if($isAdmin ?? false)
    <div id="show-extend-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                <div class="p-6 pb-4 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-green-100 mb-4">
                        <i class="ri-calendar-check-line text-3xl md:text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.extend_title') }}</h3>
                    <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.extend_message', ['name' => $studentName]) }}</p>
                    <form method="POST" action="{{ route('manage.subscriptions.extend', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-extend-form">
                        @csrf
                        <label for="show_extend_days" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.extend_days') }} ({{ __('supervisor.subscriptions.extend_max_days', ['max' => 30]) }})</label>
                        <input type="number" name="extend_days" id="show_extend_days" min="1" max="30" value="3" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @if($subscription->ends_at)
                            <p class="text-xs text-gray-500 mt-1">{{ __('supervisor.subscriptions.current_end_date') }}: {{ $subscription->ends_at->format('Y-m-d') }}</p>
                        @endif
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button type="button" onclick="document.getElementById('show-extend-modal').classList.add('hidden')"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" onclick="document.getElementById('show-extend-form').submit()"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md">
                        {{ __('supervisor.subscriptions.action_extend') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Confirm Payment Modal --}}
@if($isAdmin ?? false)
    <div id="show-confirm-payment-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                <div class="p-6 pb-4 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-emerald-100 mb-4">
                        <i class="ri-check-double-line text-3xl md:text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.confirm_payment_title') }}</h3>
                    <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.confirm_payment_message', ['name' => $studentName]) }}</p>
                    <form method="POST" action="{{ route('manage.subscriptions.confirm-payment', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-confirm-payment-form">
                        @csrf
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.payment_reference_label') }}</label>
                        <input type="text" name="payment_reference" id="payment_reference" maxlength="255"
                               placeholder="{{ __('supervisor.subscriptions.payment_reference_placeholder') }}"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button type="button" onclick="document.getElementById('show-confirm-payment-modal').classList.add('hidden')"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" onclick="document.getElementById('show-confirm-payment-form').submit()"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-md">
                        {{ __('supervisor.subscriptions.action_confirm_payment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Renew Modal --}}
@if($isAdmin ?? false)
    <div id="show-renew-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                <div class="p-6 pb-4 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-indigo-100 mb-4">
                        <i class="ri-refresh-line text-3xl md:text-2xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.renew_title') }}</h3>
                    <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.renew_message', ['name' => $studentName]) }}</p>
                    @if($sessionsRemaining > 0)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-700">
                            <i class="ri-information-line"></i>
                            {{ __('supervisor.subscriptions.sessions_carryover_message', ['count' => $sessionsRemaining]) }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('manage.subscriptions.renew', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-renew-form">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.billing_cycle_label') }}</label>
                            <div class="flex gap-3">
                                @foreach(['monthly' => __('enums.billing_cycle.monthly'), 'quarterly' => __('enums.billing_cycle.quarterly'), 'yearly' => __('enums.billing_cycle.yearly')] as $value => $label)
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="billing_cycle" value="{{ $value }}" {{ $subscription->billing_cycle->value === $value ? 'checked' : '' }} class="peer sr-only">
                                        <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 transition-colors">
                                            {{ $label }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.activation_mode_label') }}</label>
                            <div class="flex gap-3">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="activate_mode" value="immediate" checked class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 transition-colors">
                                        {{ __('supervisor.subscriptions.mode_immediate') }}
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="activate_mode" value="pending" class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 transition-colors">
                                        {{ __('supervisor.subscriptions.mode_pending') }}
                                    </div>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button type="button" onclick="document.getElementById('show-renew-modal').classList.add('hidden')"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" onclick="document.getElementById('show-renew-form').submit()"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                        {{ __('supervisor.subscriptions.action_renew') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Resubscribe Modal --}}
@if($isAdmin ?? false)
    <div id="show-resubscribe-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                <div class="p-6 pb-4 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-teal-100 mb-4">
                        <i class="ri-arrow-go-back-line text-3xl md:text-2xl text-teal-600"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.resubscribe_title') }}</h3>
                    <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.resubscribe_message', ['name' => $studentName]) }}</p>
                    <form method="POST" action="{{ route('manage.subscriptions.resubscribe', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-resubscribe-form">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.billing_cycle_label') }}</label>
                            <div class="flex gap-3">
                                @foreach(['monthly' => __('enums.billing_cycle.monthly'), 'quarterly' => __('enums.billing_cycle.quarterly'), 'yearly' => __('enums.billing_cycle.yearly')] as $value => $label)
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="billing_cycle" value="{{ $value }}" {{ $subscription->billing_cycle->value === $value ? 'checked' : '' }} class="peer sr-only">
                                        <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700 transition-colors">
                                            {{ $label }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.activation_mode_label') }}</label>
                            <div class="flex gap-3">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="activate_mode" value="immediate" checked class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700 transition-colors">
                                        {{ __('supervisor.subscriptions.mode_immediate') }}
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="activate_mode" value="pending" class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-lg border border-gray-300 text-sm peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700 transition-colors">
                                        {{ __('supervisor.subscriptions.mode_pending') }}
                                    </div>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button type="button" onclick="document.getElementById('show-resubscribe-modal').classList.add('hidden')"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" onclick="document.getElementById('show-resubscribe-form').submit()"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-xl transition-all shadow-md">
                        {{ __('supervisor.subscriptions.action_resubscribe') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Create Circle Modal (Quran individual only) --}}
@if(($isAdmin ?? false) && $type === 'quran' && ($subscription->subscription_type ?? '') === 'individual' && !$subscription->education_unit_id)
    <div id="show-create-circle-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                <div class="p-6 pb-4 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full bg-primary-100 mb-4">
                        <i class="ri-add-circle-line text-3xl md:text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.create_circle_title') }}</h3>
                    <form method="POST" action="{{ route('manage.subscriptions.create-circle', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-create-circle-form">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.specialization_label') }}</label>
                                <select name="specialization" class="w-full rounded-lg border-gray-300 text-sm" required>
                                    <option value="memorization">{{ __('subscriptions.specialization_memorization') }}</option>
                                    <option value="recitation">{{ __('subscriptions.specialization_recitation') }}</option>
                                    <option value="tajweed">{{ __('subscriptions.specialization_tajweed') }}</option>
                                    <option value="complete">{{ __('subscriptions.specialization_complete') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.memorization_level_label') }}</label>
                                <select name="memorization_level" class="w-full rounded-lg border-gray-300 text-sm" required>
                                    @foreach (\App\Enums\QuranLearningLevel::cases() as $level)
                                        <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button type="button" onclick="document.getElementById('show-create-circle-modal').classList.add('hidden')"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" onclick="document.getElementById('show-create-circle-form').submit()"
                        class="cursor-pointer inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition-all shadow-md">
                        {{ __('supervisor.subscriptions.action_create_circle') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</x-layouts.supervisor>
