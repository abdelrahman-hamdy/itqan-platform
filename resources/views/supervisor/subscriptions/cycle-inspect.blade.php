<x-layouts.supervisor>

@php
    $studentUser = $subscription->student;
    $studentName = $studentUser ? trim($studentUser->first_name . ' ' . $studentUser->last_name) : '-';

    $stateColor = match($cycle->cycle_state) {
        'active' => 'bg-green-100 text-green-800',
        'queued' => 'bg-blue-100 text-blue-800',
        'archived' => 'bg-gray-100 text-gray-700',
        default => 'bg-gray-100 text-gray-700',
    };
    $paymentColor = match($cycle->payment_status) {
        'paid' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'failed' => 'bg-red-100 text-red-800',
        'waived' => 'bg-gray-100 text-gray-600',
        default => 'bg-gray-100 text-gray-700',
    };

    $sessionsUsedDrifted = (int) $cycle->sessions_used !== (int) $derivedSessionsUsed;

    // INV-A5 anchor mismatch indicator: queued.starts_at should equal active.ends_at.
    $anchorMismatch = false;
    if ($activeCycle && $queuedCycle) {
        $anchorMismatch = ! ($activeCycle->ends_at && $queuedCycle->starts_at
            && $activeCycle->ends_at->equalTo($queuedCycle->starts_at));
    }
@endphp

<div class="max-w-6xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName, 'url' => route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id])],
            ['label' => __('supervisor.subscriptions.inspect_cycle') . ' #' . $cycle->cycle_number],
        ]"
        view-type="supervisor"
    />

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    {{-- ═══ HEADER ═══ --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                {{ __('supervisor.subscriptions.cycle_inspect_title', ['number' => $cycle->cycle_number]) }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $studentName }}
                · {{ $subscription->subscription_code }}
            </p>
        </div>
        <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
            <i class="ri-arrow-right-line"></i>{{ __('supervisor.subscriptions.back_to_subscription') }}
        </a>
    </div>

    {{-- ═══ INVARIANT VIOLATIONS (top of page — surfaces what's wrong first) ═══ --}}
    @if(count($cycleViolations) > 0)
        <div class="mb-6 bg-red-50 border-2 border-red-200 rounded-xl p-4">
            <h2 class="text-sm font-bold text-red-900 mb-2 flex items-center gap-2">
                <i class="ri-error-warning-line text-lg"></i>
                {{ __('supervisor.subscriptions.cycle_invariant_violations', ['count' => count($cycleViolations)]) }}
            </h2>
            <ul class="space-y-2">
                @foreach($cycleViolations as $v)
                    <li class="text-sm text-red-800">
                        <span class="inline-block px-2 py-0.5 rounded bg-red-200 text-red-900 font-mono text-xs">{{ $v['code'] }}</span>
                        {{ $v['message'] ?? '' }}
                        @if(! empty($v['context']))
                            <details class="mt-1 text-xs text-red-700">
                                <summary class="cursor-pointer">{{ __('supervisor.subscriptions.show_context') }}</summary>
                                <pre class="mt-1 p-2 bg-red-100 rounded overflow-x-auto" dir="ltr">{{ json_encode($v['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-800 flex items-center gap-2">
            <i class="ri-check-double-line"></i>
            {{ __('supervisor.subscriptions.no_violations_for_cycle') }}
        </div>
    @endif

    {{-- Conflict banner — block-on-conflict result from the last editCycle attempt --}}
    @if(session('cycle_edit_conflicts'))
        <div class="mb-6 bg-amber-50 border-2 border-amber-300 rounded-xl p-4">
            <h2 class="text-sm font-bold text-amber-900 mb-2 flex items-center gap-2">
                <i class="ri-error-warning-line text-lg"></i>
                {{ __('supervisor.subscriptions.cycle_edit_conflicts') }}
            </h2>
            <ul class="space-y-2 text-sm text-amber-800">
                @foreach(session('cycle_edit_conflicts') as $c)
                    <li>
                        <span class="inline-block px-2 py-0.5 rounded bg-amber-200 text-amber-900 font-mono text-xs">{{ $c['code'] ?? '' }}</span>
                        <strong>{{ $c['field'] ?? '' }}:</strong>
                        {{ $c['message'] ?? '' }}
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-amber-700 mt-3">{{ __('supervisor.subscriptions.cycle_edit_fix_first') }}</p>
        </div>
    @endif

    {{-- ═══ CYCLE ROW ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('supervisor.subscriptions.cycle_row') }}</h2>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stateColor }}">{{ __('supervisor.subscriptions.cycle_state_'.$cycle->cycle_state) }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $paymentColor }}">{{ __('supervisor.subscriptions.cycle_payment_'.$cycle->payment_status) }}</span>
                @if($canManage ?? false)
                    <button type="button"
                            onclick="document.getElementById('cycle-edit-modal').classList.remove('hidden')"
                            class="ml-2 inline-flex items-center gap-1 px-3 py-1 rounded text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                        <i class="ri-edit-line"></i>
                        {{ __('supervisor.subscriptions.edit_cycle') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="p-5 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.cycle_id_label') }}</div>
                <div class="font-mono text-gray-900">#{{ $cycle->id }} (cycle_number {{ $cycle->cycle_number }})</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.starts_at') }}</div>
                <div class="font-mono text-gray-900">{{ $cycle->starts_at?->toIso8601String() ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.ends_at') }}</div>
                <div class="font-mono text-gray-900">{{ $cycle->ends_at?->toIso8601String() ?? '—' }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.total_sessions') }}</div>
                <div class="font-semibold text-gray-900">{{ $cycle->total_sessions }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 flex items-center gap-1">
                    {{ __('supervisor.subscriptions.sessions_used') }}
                    <span class="text-amber-700 font-medium">{{ __('supervisor.subscriptions.read_only') }}</span>
                </div>
                <div class="font-semibold {{ $sessionsUsedDrifted ? 'text-amber-700' : 'text-gray-900' }}">
                    {{ $cycle->sessions_used }}
                    @if($sessionsUsedDrifted)
                        <span class="text-xs ml-1">⇄ {{ __('supervisor.subscriptions.derived_label') }}: {{ $derivedSessionsUsed }}</span>
                    @endif
                </div>
                <div class="text-[11px] text-gray-500 mt-0.5">{{ __('supervisor.subscriptions.sessions_used_inv_b3_hint') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.carryover_sessions') }}</div>
                <div class="text-gray-900">{{ $cycle->carryover_sessions }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.package_id_label') }}</div>
                <div class="text-gray-900">
                    @if($cycle->package_id)
                        #{{ $cycle->package_id }} · {{ optional($cycle->package)->name ?? '—' }}
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.pricing_source_label') }}</div>
                <div class="text-gray-900">{{ $cycle->pricing_source ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.final_price') }}</div>
                <div class="font-semibold text-gray-900">{{ number_format((float) $cycle->final_price, 2) }} {{ $cycle->currency }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.grace_period_ends_at') }}</div>
                <div class="text-gray-900">{{ $cycle->grace_period_ends_at?->toIso8601String() ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">{{ __('supervisor.subscriptions.archived_at') }}</div>
                <div class="text-gray-900">{{ $cycle->archived_at?->toIso8601String() ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ═══ QUEUED SIBLING ANCHOR (INV-A5) ═══ --}}
    @if($activeCycle && $queuedCycle && $activeCycle->id !== $queuedCycle->id)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('supervisor.subscriptions.queued_sibling_anchor') }}</h2>
            </div>
            <div class="p-5 text-sm flex flex-col sm:flex-row gap-4">
                <div class="flex-1 p-3 rounded-lg border border-green-200 bg-green-50">
                    <div class="text-xs text-green-700 font-semibold">{{ __('supervisor.subscriptions.active_cycle') }} #{{ $activeCycle->cycle_number }}</div>
                    <div class="font-mono text-xs">{{ $activeCycle->ends_at?->toIso8601String() ?? '—' }}</div>
                </div>
                <div class="self-center text-2xl text-gray-400">⇄</div>
                <div class="flex-1 p-3 rounded-lg border {{ $anchorMismatch ? 'border-red-300 bg-red-50' : 'border-blue-200 bg-blue-50' }}">
                    <div class="text-xs font-semibold {{ $anchorMismatch ? 'text-red-700' : 'text-blue-700' }}">{{ __('supervisor.subscriptions.queued_cycle') }} #{{ $queuedCycle->cycle_number }}</div>
                    <div class="font-mono text-xs">{{ $queuedCycle->starts_at?->toIso8601String() ?? '—' }}</div>
                    @if($anchorMismatch)
                        <div class="text-[11px] text-red-700 mt-1">{{ __('supervisor.subscriptions.inv_a5_mismatch') }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ SESSIONS LIST ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-900">
                {{ __('supervisor.subscriptions.sessions_anchored', ['count' => $cycleSessions->count()]) }}
            </h2>
        </div>
        @if($cycleSessions->isEmpty())
            <div class="p-5 text-sm text-gray-500">{{ __('supervisor.subscriptions.no_sessions') }}</div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($cycleSessions as $session)
                    @php
                        $sessionType = match(true) {
                            $session instanceof \App\Models\QuranSession => 'quran',
                            $session instanceof \App\Models\AcademicSession => 'academic',
                            default => 'quran',
                        };
                        $statusValue = $session->status?->value ?? (string) $session->status;
                        $hasConsumption = in_array($session->id, $consumedSessionIds, true);
                        $isClean = ! $hasConsumption;
                        $isFuture = $session->scheduled_at && $session->scheduled_at->isFuture();
                    @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900">
                                #{{ $session->id }} ·
                                {{ $session->scheduled_at?->toDateTimeString() ?? __('supervisor.subscriptions.not_scheduled') }}
                            </div>
                            <div class="text-xs text-gray-500 flex gap-2 mt-0.5">
                                <span>{{ __('supervisor.subscriptions.session_status') }}: <span class="font-mono">{{ $statusValue }}</span></span>
                                @if($hasConsumption)
                                    <span class="text-amber-700">{{ __('supervisor.subscriptions.has_data') }}</span>
                                @else
                                    <span class="text-green-700">{{ __('supervisor.subscriptions.clean') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]) }}"
                               class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">
                                {{ __('supervisor.subscriptions.view') }}
                            </a>
                            @if(($canManage ?? false) && $isFuture && $statusValue === 'scheduled' && $isClean)
                                {{-- Inline delete (clean future session — F2.5) --}}
                                <form method="POST" action="{{ route('manage.subscriptions.cycles.sessions.destroy', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id, 'cycle' => $cycle->id, 'session' => $session->id]) }}"
                                      onsubmit="return confirm('{{ __('supervisor.subscriptions.confirm_delete_session') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-red-100 hover:bg-red-200 text-red-700">{{ __('supervisor.subscriptions.delete') }}</button>
                                </form>
                            @endif
                            @if(($canManage ?? false) && $isFuture && $statusValue === 'scheduled')
                                <form method="POST" action="{{ route('manage.subscriptions.cycles.sessions.cancel', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id, 'cycle' => $cycle->id, 'session' => $session->id]) }}"
                                      onsubmit="return confirm('{{ __('supervisor.subscriptions.confirm_cancel_session') }}');">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-700">{{ __('supervisor.subscriptions.cancel') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══ CONSUMPTION ROWS (INV-B3 truth source) ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">
                {{ __('supervisor.subscriptions.consumption_rows', ['count' => $consumptionRows->count()]) }}
            </h2>
            <span class="text-xs text-gray-500">{{ __('supervisor.subscriptions.consumption_rows_hint') }}</span>
        </div>
        @if($consumptionRows->isEmpty())
            <div class="p-5 text-sm text-gray-500">{{ __('supervisor.subscriptions.no_consumption_rows') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-start">#</th>
                            <th class="px-4 py-2 text-start">{{ __('supervisor.subscriptions.session') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('supervisor.subscriptions.consumed_at') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('supervisor.subscriptions.consumption_type') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('supervisor.subscriptions.consumption_source') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('supervisor.subscriptions.consumption_status') }}</th>
                            @if($canManage ?? false)
                                <th class="px-4 py-2 text-end">{{ __('supervisor.subscriptions.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($consumptionRows as $row)
                            @php
                                $isReversed = $row->reversed_at !== null;
                                $sourceColor = match($row->source) {
                                    'admin_manual' => 'bg-purple-100 text-purple-700',
                                    'teacher_report' => 'bg-blue-100 text-blue-700',
                                    'auto_attendance' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr class="{{ $isReversed ? 'bg-gray-50 text-gray-500' : '' }}">
                                <td class="px-4 py-2 font-mono text-xs">{{ $row->id }}</td>
                                <td class="px-4 py-2 font-mono text-xs">#{{ $row->session_id }}</td>
                                <td class="px-4 py-2 text-xs">{{ $row->consumed_at?->toDateTimeString() ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs"><span class="font-mono">{{ $row->consumption_type }}</span></td>
                                <td class="px-4 py-2 text-xs"><span class="px-2 py-0.5 rounded {{ $sourceColor }}">{{ $row->source }}</span></td>
                                <td class="px-4 py-2 text-xs">
                                    @if($isReversed)
                                        <span class="text-red-700">{{ __('supervisor.subscriptions.reversed_at_label') }}</span>
                                        <div class="text-[10px] text-gray-500 mt-0.5">{{ $row->reversed_reason }}</div>
                                    @else
                                        <span class="text-green-700">{{ __('supervisor.subscriptions.active') }}</span>
                                    @endif
                                </td>
                                @if($canManage ?? false)
                                    <td class="px-4 py-2 text-end whitespace-nowrap">
                                        @if(! $isReversed)
                                            <form method="POST" action="{{ route('manage.subscriptions.cycles.consumption.reverse', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id, 'cycle' => $cycle->id, 'consumption' => $row->id]) }}"
                                                  class="inline-block"
                                                  onsubmit="return confirm('{{ __('supervisor.subscriptions.confirm_reverse_consumption') }}');">
                                                @csrf
                                                <button type="submit" class="text-xs px-2 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-700">{{ __('supervisor.subscriptions.reverse') }}</button>
                                            </form>
                                            @if($row->source !== \App\Models\SessionConsumption::SOURCE_ADMIN_MANUAL)
                                                <form method="POST" action="{{ route('manage.subscriptions.cycles.consumption.promote', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id, 'cycle' => $cycle->id, 'consumption' => $row->id]) }}"
                                                      class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-purple-100 hover:bg-purple-200 text-purple-700">{{ __('supervisor.subscriptions.promote') }}</button>
                                                </form>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route('manage.subscriptions.cycles.consumption.promote', ['subdomain' => $subdomain, 'type' => $type, 'subscription' => $subscription->id, 'cycle' => $cycle->id, 'consumption' => $row->id]) }}"
                                                  class="inline-block">
                                                @csrf
                                                <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-700">{{ __('supervisor.subscriptions.re_record') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ═══ CYCLE INSPECTOR MODAL (Phase 3: read-only) ═══

         The raw cycle editor was replaced by the semantic actions on the
         subscription page:
           - Grant N sessions      (top up total_sessions)
           - Override price        (set final_price via SubscriptionPricing)
           - Extend                (grace_period_ends_at)
         Window dates (starts_at / ends_at) are no longer hand-editable in
         this UI; the corresponding admin endpoints remain available for the
         specific incident-recovery flow the supervisor team uses.
    --}}
    @if($canManage ?? false)
        <div id="cycle-edit-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
            <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
                <div class="relative bg-white w-full md:max-w-xl rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('supervisor.subscriptions.cycle_inspector_modal_title', ['number' => $cycle->cycle_number]) }}</h3>
                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2 mb-4">{{ __('supervisor.subscriptions.cycle_inspector_readonly_notice') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.starts_at') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->starts_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.ends_at') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->ends_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.total_sessions') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->total_sessions ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.grace_period_ends_at') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->grace_period_ends_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.archived_at') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->archived_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ __('supervisor.subscriptions.final_price') }}</div>
                                <div class="font-mono text-sm text-gray-900">{{ $cycle->final_price ?? '—' }} {{ $cycle->currency ?? '' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700">
                            <strong>{{ __('supervisor.subscriptions.use_semantic_actions') }}:</strong>
                            <ul class="list-disc pr-5 mt-2 space-y-1">
                                <li>{{ __('supervisor.subscriptions.semantic_grant_sessions') }}</li>
                                <li>{{ __('supervisor.subscriptions.semantic_override_price') }}</li>
                                <li>{{ __('supervisor.subscriptions.semantic_extend') }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                        <button type="button" onclick="document.getElementById('cycle-edit-modal').classList.add('hidden')" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl">{{ __('common.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ PAYMENTS ═══ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-900">
                {{ __('supervisor.subscriptions.payments_for_cycle', ['count' => $cyclePayments->count()]) }}
            </h2>
        </div>
        @if($cyclePayments->isEmpty())
            <div class="p-5 text-sm text-gray-500">{{ __('supervisor.subscriptions.no_payments') }}</div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($cyclePayments as $payment)
                    <div class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <div class="font-medium text-gray-900">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $payment->payment_method }} · {{ $payment->payment_gateway }} · {{ $payment->created_at?->toDateTimeString() }}
                            </div>
                        </div>
                        <div class="text-xs">
                            <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-mono">{{ $payment->status?->value ?? $payment->status }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

</x-layouts.supervisor>
