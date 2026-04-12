<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $studentUser = $subscription->student;
    $studentName = $studentUser ? trim($studentUser->first_name . ' ' . $studentUser->last_name) : '-';
    $teacherName = $teacherUser ? trim($teacherUser->first_name . ' ' . $teacherUser->last_name) : '-';

    $sessionsUsed = $subscription->sessions_used ?? 0;
    $sessionsTotal = $subscription->total_sessions ?? 0;
    $sessionsRemaining = $subscription->sessions_remaining ?? ($sessionsTotal - $sessionsUsed);
    $progressPct = $sessionsTotal > 0 ? min(100, round(($sessionsUsed / $sessionsTotal) * 100)) : 0;

    $subType = $type === 'quran' ? ($subscription->subscription_type ?? 'individual') : 'academic';
    $typeLabel = match($subType) {
        'individual' => __('supervisor.subscriptions.type_quran_individual'),
        'group' => __('supervisor.subscriptions.type_quran_group'),
        default => __('supervisor.subscriptions.type_academic'),
    };
    $typeColor = $type === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700';
    $teacherUserType = $type === 'quran' ? 'quran_teacher' : 'academic_teacher';

    $daysRemaining = $subscription->ends_at ? (int) max(0, nowInAcademyTimezone()->diffInDays(toAcademyTimezone($subscription->ends_at), false)) : 0;
    $packageName = $subscription->package_name_ar ?? $subscription->package?->name ?? $subscription->academicPackage?->name ?? '-';
    $source = $subscription->purchase_source?->value ?? 'web';
@endphp

<div class="max-w-5xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName],
        ]"
        view-type="supervisor"
    />

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    {{-- ═══ HEADER ═══ --}}
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('supervisor.subscriptions.show_title') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $subscription->subscription_code }}</p>
            </div>
            <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
                <i class="ri-arrow-right-line"></i>{{ __('supervisor.subscriptions.page_title') }}
            </a>
        </div>

        {{-- Badges --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $typeColor }}">{{ $typeLabel }}</span>
            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $subscription->status->badgeClasses() }}">{{ $subscription->status->label() }}</span>
            @if($subscription->is_sessions_exhausted)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800"><i class="ri-check-double-line"></i>{{ __('supervisor.subscriptions.sessions_exhausted_badge') }}</span>
            @endif
            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $subscription->payment_status->badgeClasses() }}">{{ $subscription->payment_status->label() }}</span>
            @if($subscription->is_recurring_discount && $subscription->discount_amount > 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><i class="ri-refresh-line"></i>{{ __('subscriptions.recurring_discount_badge') }}</span>
            @endif
            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">{{ $source === 'admin' ? __('supervisor.subscriptions.source_admin') : __('supervisor.subscriptions.source_student') }}</span>
        </div>

        {{-- Action Buttons (directly visible) --}}
        @if($isAdmin)
            <div class="flex flex-wrap gap-2">
                {{-- View Circle/Lesson --}}
                @if($type === 'quran')
                    @if($subscription->education_unit_id)
                        @php
                            $circleRoute = ($subscription->subscription_type ?? 'individual') === 'individual'
                                ? route('manage.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $subscription->education_unit_id])
                                : route('manage.group-circles.show', ['subdomain' => $subdomain, 'circle' => $subscription->education_unit_id]);
                        @endphp
                        <a href="{{ $circleRoute }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 cursor-pointer"><i class="ri-eye-line"></i>{{ __('supervisor.subscriptions.view_circle') }}</a>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-400 rounded-lg text-sm cursor-default"><i class="ri-link-unlink"></i>{{ __('supervisor.subscriptions.no_circle_linked') }}</span>
                    @endif
                @elseif($type === 'academic')
                    <a href="{{ route('manage.academic-lessons.show', ['subdomain' => $subdomain, 'subscription' => $subscription->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 cursor-pointer"><i class="ri-eye-line"></i>{{ __('supervisor.subscriptions.view_lesson') }}</a>
                @endif

                @if($subscription->status === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                    <form id="show-pause-form" method="POST" action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf</form>
                    <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_pause')),message:@js(__('supervisor.subscriptions.confirm_pause')),confirmText:@js(__('supervisor.subscriptions.action_pause')),isDangerous:false,icon:'ri-pause-circle-line',onConfirm:()=>document.getElementById('show-pause-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600 cursor-pointer"><i class="ri-pause-circle-line"></i>{{ __('supervisor.subscriptions.action_pause') }}</button>
                @endif
                @if($subscription->status === \App\Enums\SessionSubscriptionStatus::PAUSED)
                    <form id="show-resume-form" method="POST" action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf</form>
                    <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_resume')),message:@js(__('supervisor.subscriptions.confirm_resume')),confirmText:@js(__('supervisor.subscriptions.action_resume')),isDangerous:false,icon:'ri-play-circle-line',onConfirm:()=>document.getElementById('show-resume-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 cursor-pointer"><i class="ri-play-circle-line"></i>{{ __('supervisor.subscriptions.action_resume') }}</button>
                @endif
                @if(in_array($subscription->status, [\App\Enums\SessionSubscriptionStatus::CANCELLED, \App\Enums\SessionSubscriptionStatus::EXPIRED]))
                    <form id="show-activate-form" method="POST" action="{{ route('manage.subscriptions.activate', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf</form>
                    <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_activate')),message:@js(__('supervisor.subscriptions.confirm_activate')),confirmText:@js(__('supervisor.subscriptions.action_activate')),isDangerous:false,icon:'ri-checkbox-circle-line',onConfirm:()=>document.getElementById('show-activate-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 cursor-pointer"><i class="ri-checkbox-circle-line"></i>{{ __('supervisor.subscriptions.action_activate') }}</button>
                @endif
                @if(in_array($subscription->payment_status, [\App\Enums\SubscriptionPaymentStatus::PENDING, \App\Enums\SubscriptionPaymentStatus::FAILED]))
                    <button onclick="document.getElementById('show-confirm-payment-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700 cursor-pointer"><i class="ri-check-double-line"></i>{{ __('supervisor.subscriptions.action_confirm_payment') }}</button>
                @endif
                @if($subscription->canRenew() || $subscription->is_sessions_exhausted)
                    <button onclick="document.getElementById('show-renew-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 cursor-pointer"><i class="ri-refresh-line"></i>{{ __('supervisor.subscriptions.action_renew') }}</button>
                @endif
                @if(in_array($subscription->status, [\App\Enums\SessionSubscriptionStatus::CANCELLED, \App\Enums\SessionSubscriptionStatus::EXPIRED]))
                    <button onclick="document.getElementById('show-resubscribe-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700 cursor-pointer"><i class="ri-arrow-go-back-line"></i>{{ __('supervisor.subscriptions.action_resubscribe') }}</button>
                @endif
                @if($subscription->isInGracePeriod())
                    <form id="show-cancel-extension-form" method="POST" action="{{ route('manage.subscriptions.cancel-extension', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf</form>
                    <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_cancel_extension')),message:@js(__('supervisor.subscriptions.confirm_cancel_extension')),confirmText:@js(__('supervisor.subscriptions.action_cancel_extension')),isDangerous:true,icon:'ri-calendar-close-line',onConfirm:()=>document.getElementById('show-cancel-extension-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700 cursor-pointer"><i class="ri-calendar-close-line"></i>{{ __('supervisor.subscriptions.action_cancel_extension') }}</button>
                @else
                    <button onclick="document.getElementById('show-extend-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 cursor-pointer"><i class="ri-calendar-check-line"></i>{{ __('supervisor.subscriptions.action_extend') }}</button>
                @endif
                @if($subscription->status->canCancel())
                    <form id="show-cancel-form" method="POST" action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf</form>
                    <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_cancel')),message:@js(__('supervisor.subscriptions.confirm_cancel')),confirmText:@js(__('supervisor.subscriptions.action_cancel')),isDangerous:true,icon:'ri-close-circle-line',onConfirm:()=>document.getElementById('show-cancel-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 cursor-pointer"><i class="ri-close-circle-line"></i>{{ __('supervisor.subscriptions.action_cancel') }}</button>
                @endif
                <form id="show-delete-form" method="POST" action="{{ route('manage.subscriptions.destroy', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}">@csrf @method('DELETE')</form>
                <button onclick="window.confirmAction({title:@js(__('supervisor.subscriptions.action_delete')),message:@js(__('supervisor.subscriptions.confirm_delete')),confirmText:@js(__('supervisor.subscriptions.action_delete')),isDangerous:true,icon:'ri-delete-bin-line',onConfirm:()=>document.getElementById('show-delete-form').submit()})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-700 text-white rounded-lg text-sm hover:bg-red-800 cursor-pointer"><i class="ri-delete-bin-line"></i>{{ __('supervisor.subscriptions.action_delete') }}</button>
            </div>
        @endif
    </div>

    {{-- ═══ INFO CARD ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">

        {{-- People Row --}}
        <div class="p-5 border-b border-gray-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Student --}}
                <a href="{{ route('manage.students.show', ['subdomain' => $subdomain, 'student' => $studentUser?->id ?? 0]) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                    @if($studentUser) <x-avatar :user="$studentUser" size="sm" userType="student" /> @endif
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.col_student') }}</div>
                        <div class="font-semibold text-gray-900 truncate">{{ $studentName }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $studentUser?->email }}</div>
                    </div>
                </a>

                {{-- Teacher --}}
                <a href="{{ route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $teacherUser?->id ?? 0]) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                    @if($teacherUser) <x-avatar :user="$teacherUser" size="sm" :userType="$teacherUserType" /> @endif
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.col_teacher') }}</div>
                        <div class="font-semibold text-gray-900 truncate">{{ $teacherName }}</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="p-5 border-b border-gray-100">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
                {{-- Package --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.detail_package') }}</div>
                    <div class="font-semibold text-gray-900">{{ $packageName }}</div>
                    <div class="text-xs text-gray-500">{{ $subscription->sessions_per_month ?? '-' }} {{ __('subscriptions.sessions_per_month') }} &middot; {{ $subscription->session_duration_minutes ?? '-' }} {{ __('supervisor.subscriptions.minutes') }}</div>
                </div>

                {{-- Price --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.detail_price') }}</div>
                    <div class="font-semibold text-gray-900">{{ number_format($subscription->final_price ?? 0, 2) }} {{ $subscription->currency ?? 'SAR' }}</div>
                    @if($subscription->discount_amount > 0)
                        <div class="text-xs text-green-600">{{ __('subscriptions.discount_label') }}: {{ number_format($subscription->discount_amount, 2) }}</div>
                    @endif
                </div>

                {{-- Billing Cycle --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.detail_cycle') }}</div>
                    <div class="font-semibold text-gray-900">{{ $subscription->billing_cycle?->label() ?? '-' }}</div>
                </div>

                {{-- Sessions --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.col_sessions') }}</div>
                    <div class="font-semibold text-gray-900">{{ $sessionsUsed }}/{{ $sessionsTotal }}</div>
                    <div class="w-full h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden">
                        <div class="h-full rounded-full {{ $progressPct >= 90 ? 'bg-red-500' : ($progressPct >= 70 ? 'bg-amber-500' : 'bg-blue-500') }}" style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>

                {{-- Dates --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.col_dates') }}</div>
                    <div class="font-semibold text-gray-900">{{ $subscription->starts_at?->format('d/m/Y') ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.to') }} {{ $subscription->ends_at?->format('d/m/Y') ?? '-' }}</div>
                    @if($daysRemaining > 0 && $subscription->isActive())
                        <div class="text-xs text-blue-600 mt-0.5">{{ $daysRemaining }} {{ __('supervisor.subscriptions.days_remaining') }}</div>
                    @endif
                </div>

                {{-- Created --}}
                <div>
                    <div class="text-xs text-gray-500 mb-0.5">{{ __('supervisor.subscriptions.detail_created') }}</div>
                    <div class="font-semibold text-gray-900">{{ $subscription->created_at?->format('d/m/Y') }}</div>
                    <div class="text-xs text-gray-500">{{ $subscription->created_at?->format('H:i') }}</div>
                </div>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="p-5 text-sm space-y-2">
            @if($subscription->isInGracePeriod())
                @php $graceEnd = $subscription->getGracePeriodEndsAt(); @endphp
                <div class="flex items-center gap-2 text-orange-700 bg-orange-50 p-2 rounded-lg">
                    <i class="ri-timer-line"></i>
                    {{ __('supervisor.subscriptions.grace_period_until', ['date' => $graceEnd?->format('Y-m-d')]) }}
                </div>
            @endif
            @if($renewedBy)
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="ri-links-line"></i>
                    {{ __('supervisor.subscriptions.renewed_by') }}
                    <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $renewedBy->id]) }}" class="text-primary-600 hover:underline font-medium">#{{ $renewedBy->subscription_code }}</a>
                </div>
            @endif
            @if($subscription->admin_notes)
                <div class="flex items-start gap-2 text-gray-600"><i class="ri-sticky-note-line mt-0.5"></i><span>{{ $subscription->admin_notes }}</span></div>
            @endif
        </div>
    </div>

    {{-- ═══ CYCLES HISTORY ═══ --}}
    @if($subscriptionCycles->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 flex items-center gap-2">
                <i class="ri-history-line text-gray-500"></i>
                <h3 class="text-sm font-semibold text-gray-900">{{ __('supervisor.subscriptions.cycles_history_title') }}</h3>
                <span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full">{{ $subscriptionCycles->count() }}</span>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($subscriptionCycles as $sCycle)
                    @php
                        $stateColor = match($sCycle->cycle_state) {
                            'active' => 'bg-green-100 text-green-800',
                            'queued' => 'bg-blue-100 text-blue-800',
                            'archived' => 'bg-gray-100 text-gray-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $stateLabel = __('supervisor.subscriptions.cycle_state_'.$sCycle->cycle_state);
                        $paymentColor = match($sCycle->payment_status) {
                            'paid' => 'bg-green-100 text-green-800',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'waived' => 'bg-gray-100 text-gray-600',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $paymentLabel = __('supervisor.subscriptions.cycle_payment_'.$sCycle->payment_status);
                    @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex-shrink-0 font-bold text-gray-600">#{{ $sCycle->cycle_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stateColor }}">{{ $stateLabel }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $paymentColor }}">{{ $paymentLabel }}</span>
                            <span class="text-xs text-gray-500 truncate">
                                {{ $sCycle->starts_at?->format('Y-m-d') ?? '—' }}
                                &nbsp;→&nbsp;
                                {{ $sCycle->ends_at?->format('Y-m-d') ?? '—' }}
                            </span>
                            @if($sCycle->grace_period_ends_at && $sCycle->grace_period_ends_at->isFuture())
                                <span class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded px-2 py-0.5">
                                    <i class="ri-time-line"></i> {{ __('supervisor.subscriptions.grace_until', ['date' => $sCycle->grace_period_ends_at->format('Y-m-d')]) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 flex-shrink-0">
                            <span>{{ $sCycle->sessions_used }} / {{ $sCycle->total_sessions }}</span>
                            <span class="font-medium text-gray-700">{{ number_format((float) $sCycle->final_price, 2) }} {{ $sCycle->currency }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══ SESSIONS ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Tab Header --}}
        <div class="px-5 pt-4 pb-0 border-b border-gray-200">
            <div class="flex gap-4">
                <a href="?cycle=current" class="pb-3 text-sm font-medium border-b-2 {{ $cycle === 'current' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ __('supervisor.subscriptions.tab_current_cycle') }} <span class="text-xs bg-gray-100 px-1.5 py-0.5 rounded-full">{{ $currentCycleCount }}</span>
                </a>
                <a href="?cycle=all" class="pb-3 text-sm font-medium border-b-2 {{ $cycle === 'all' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ __('supervisor.subscriptions.tab_all_sessions') }} <span class="text-xs bg-gray-100 px-1.5 py-0.5 rounded-full">{{ $allSessionsCount }}</span>
                </a>
            </div>
        </div>

        {{-- Session List --}}
        @if($sessions->isEmpty())
            <div class="px-6 py-12 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="ri-calendar-line text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ __('supervisor.subscriptions.no_sessions_title') }}</h3>
                <p class="text-xs text-gray-500">{{ $cycle === 'current' ? __('supervisor.subscriptions.no_sessions_current_cycle') : __('supervisor.subscriptions.no_sessions') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($sessions as $session)
                    @php
                        $sessionType = match(true) {
                            $session instanceof \App\Models\QuranSession => 'quran',
                            $session instanceof \App\Models\AcademicSession => 'academic',
                            default => 'quran',
                        };
                        $isLive = in_array($session->status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
                    @endphp
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]) }}"
                       class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 transition-colors">
                        {{-- Status --}}
                        <div class="flex items-center gap-1.5">
                            @if($isLive)<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>@endif
                            @php
                                $sessionBadge = match($session->status->color()) {
                                    'success' => 'bg-green-100 text-green-800',
                                    'danger' => 'bg-red-100 text-red-800',
                                    'warning' => 'bg-amber-100 text-amber-800',
                                    'info' => 'bg-blue-100 text-blue-800',
                                    'primary' => 'bg-cyan-100 text-cyan-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $sessionBadge }}">
                                {{ $session->status->label() }}
                            </span>
                        </div>

                        {{-- Session Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $session->title ?? $session->name ?? $session->session_code ?? '#'.$session->id }}</div>
                            <div class="text-xs text-gray-500">{{ $session->session_duration_minutes ?? $subscription->session_duration_minutes ?? '-' }} {{ __('supervisor.subscriptions.minutes') }}</div>
                        </div>

                        {{-- Date --}}
                        <div class="text-end text-sm shrink-0">
                            @if($session->scheduled_at)
                                <div class="text-gray-900">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('d M') }}</div>
                                <div class="text-xs text-gray-500">{{ toAcademyTimezone($session->scheduled_at)->format('h:i A') }}</div>
                            @else
                                <div class="text-gray-400 text-xs">{{ __('supervisor.subscriptions.not_scheduled') }}</div>
                            @endif
                        </div>

                        <i class="ri-arrow-left-s-line text-gray-400"></i>
                    </a>
                @endforeach
            </div>

            @if($sessions->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">{{ $sessions->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
</div>

{{-- ═══ MODALS ═══ --}}
@if($isAdmin ?? false)
    {{-- Extend Modal --}}
    <div id="show-extend-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="p-6 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-green-100 mb-4"><i class="ri-calendar-check-line text-2xl text-green-600"></i></div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.extend_title') }}</h3>
                    <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.subscriptions.extend_message', ['name' => $studentName]) }}</p>
                    <form method="POST" action="{{ route('manage.subscriptions.extend', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-extend-form">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.extend_days') }} ({{ __('supervisor.subscriptions.extend_max_days', ['max' => 30]) }})</label>
                        <input type="number" name="extend_days" min="1" max="30" value="3" required class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @if($subscription->ends_at)<p class="text-xs text-gray-500 mt-1">{{ __('supervisor.subscriptions.current_end_date') }}: {{ $subscription->ends_at->format('Y-m-d') }}</p>@endif
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button onclick="document.getElementById('show-extend-modal').classList.add('hidden')" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl">{{ __('common.cancel') }}</button>
                    <button onclick="document.getElementById('show-extend-form').submit()" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-md">{{ __('supervisor.subscriptions.action_extend') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Payment Modal --}}
    <div id="show-confirm-payment-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="p-6 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 mb-4"><i class="ri-check-double-line text-2xl text-emerald-600"></i></div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.confirm_payment_title') }}</h3>
                    <form method="POST" action="{{ route('manage.subscriptions.confirm-payment', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-confirm-payment-form">
                        @csrf
                        <input type="text" name="payment_reference" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{{ __('supervisor.subscriptions.payment_reference_placeholder') }}">
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button onclick="document.getElementById('show-confirm-payment-modal').classList.add('hidden')" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl">{{ __('common.cancel') }}</button>
                    <button onclick="document.getElementById('show-confirm-payment-form').submit()" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">{{ __('supervisor.subscriptions.action_confirm_payment') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Renew Modal --}}
    <div id="show-renew-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="p-6 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-indigo-100 mb-4"><i class="ri-refresh-line text-2xl text-indigo-600"></i></div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.renew_title') }}</h3>
                    @if($sessionsRemaining > 0)<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-700"><i class="ri-information-line"></i> {{ __('supervisor.subscriptions.sessions_carryover_message', ['count' => $sessionsRemaining]) }}</div>@endif
                    <form method="POST" action="{{ route('manage.subscriptions.renew', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-renew-form">
                        @csrf
                        <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.billing_cycle_label') }}</label><div class="flex gap-2">@foreach(['monthly'=>__('enums.billing_cycle.monthly'),'quarterly'=>__('enums.billing_cycle.quarterly'),'yearly'=>__('enums.billing_cycle.yearly')] as $v=>$l)<label class="flex-1 cursor-pointer"><input type="radio" name="billing_cycle" value="{{ $v }}" {{ $subscription->billing_cycle?->value===$v?'checked':'' }} class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">{{ $l }}</div></label>@endforeach</div></div>
                        <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.payment_mode_label') }}</label><div class="flex gap-2"><label class="flex-1 cursor-pointer"><input type="radio" name="payment_mode" value="paid" checked class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-green-600 peer-checked:bg-green-50 peer-checked:text-green-700">{{ __('supervisor.subscriptions.payment_mode_paid') }}</div></label><label class="flex-1 cursor-pointer"><input type="radio" name="payment_mode" value="unpaid" class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-yellow-600 peer-checked:bg-yellow-50 peer-checked:text-yellow-700">{{ __('supervisor.subscriptions.payment_mode_unpaid') }}</div></label></div></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button onclick="document.getElementById('show-renew-modal').classList.add('hidden')" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl">{{ __('common.cancel') }}</button>
                    <button onclick="document.getElementById('show-renew-form').submit()" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md">{{ __('supervisor.subscriptions.action_renew') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Resubscribe Modal --}}
    <div id="show-resubscribe-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                <div class="p-6 pt-8 md:pt-6">
                    <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-teal-100 mb-4"><i class="ri-arrow-go-back-line text-2xl text-teal-600"></i></div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.subscriptions.resubscribe_title') }}</h3>
                    <form method="POST" action="{{ route('manage.subscriptions.resubscribe', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}" id="show-resubscribe-form">
                        @csrf
                        <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.billing_cycle_label') }}</label><div class="flex gap-2">@foreach(['monthly'=>__('enums.billing_cycle.monthly'),'quarterly'=>__('enums.billing_cycle.quarterly'),'yearly'=>__('enums.billing_cycle.yearly')] as $v=>$l)<label class="flex-1 cursor-pointer"><input type="radio" name="billing_cycle" value="{{ $v }}" {{ $subscription->billing_cycle?->value===$v?'checked':'' }} class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-700">{{ $l }}</div></label>@endforeach</div></div>
                        <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.subscriptions.payment_mode_label') }}</label><div class="flex gap-2"><label class="flex-1 cursor-pointer"><input type="radio" name="payment_mode" value="paid" checked class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-green-600 peer-checked:bg-green-50 peer-checked:text-green-700">{{ __('supervisor.subscriptions.payment_mode_paid') }}</div></label><label class="flex-1 cursor-pointer"><input type="radio" name="payment_mode" value="unpaid" class="peer sr-only"><div class="text-center py-2 px-2 rounded-lg border border-gray-300 text-xs peer-checked:border-yellow-600 peer-checked:bg-yellow-50 peer-checked:text-yellow-700">{{ __('supervisor.subscriptions.payment_mode_unpaid') }}</div></label></div></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                    <button onclick="document.getElementById('show-resubscribe-modal').classList.add('hidden')" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl">{{ __('common.cancel') }}</button>
                    <button onclick="document.getElementById('show-resubscribe-form').submit()" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-xl shadow-md">{{ __('supervisor.subscriptions.action_resubscribe') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
</x-layouts.supervisor>
