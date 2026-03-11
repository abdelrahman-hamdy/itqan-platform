<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $studentName = $type === 'quran' ? ($subscription->student?->name ?? '-') : ($subscription->student?->name ?? '-');
    $teacherName = $type === 'quran' ? ($subscription->quranTeacherUser?->name ?? '-') : ($subscription->teacher?->user?->name ?? '-');
@endphp

<div class="max-w-5xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName],
        ]"
        view-type="supervisor"
    />

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('supervisor.subscriptions.show_title') }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $type === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                    {{ $type === 'quran' ? __('supervisor.subscriptions.type_quran') : __('supervisor.subscriptions.type_academic') }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $subscription->status->badgeClasses() }} ms-1">
                    {{ $subscription->status->label() }}
                </span>
            </p>
        </div>
        @if($isAdmin)
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('manage.subscriptions.edit', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                    <i class="ri-edit-line"></i>{{ __('supervisor.subscriptions.action_edit') }}
                </a>
            </div>
        @endif
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
                    {{ $subscription->total_sessions_completed ?? 0 }} / {{ $subscription->total_sessions ?? 0 }}
                    <span class="text-xs text-gray-500 font-normal">({{ __('supervisor.subscriptions.remaining') }}: {{ $subscription->sessions_remaining ?? (($subscription->total_sessions ?? 0) - ($subscription->total_sessions_completed ?? 0)) }})</span>
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.subscriptions.col_dates') }}</p>
                <p class="text-sm font-semibold text-gray-900">{{ $subscription->starts_at?->format('Y-m-d') ?? '-' }}</p>
                <p class="text-xs text-gray-500">{{ __('supervisor.subscriptions.to') }} {{ $subscription->ends_at?->format('Y-m-d') ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    @if($isAdmin)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('supervisor.subscriptions.quick_actions') }}</h3>
            <div class="flex flex-wrap gap-2">
                @if($subscription->status === \App\Enums\SessionSubscriptionStatus::ACTIVE)
                    <form method="POST" action="{{ route('manage.subscriptions.pause', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-sm hover:bg-amber-200 transition-colors">
                            <i class="ri-pause-circle-line"></i>{{ __('supervisor.subscriptions.action_pause') }}
                        </button>
                    </form>
                @endif
                @if($subscription->status === \App\Enums\SessionSubscriptionStatus::PAUSED)
                    <form method="POST" action="{{ route('manage.subscriptions.resume', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200 transition-colors">
                            <i class="ri-play-circle-line"></i>{{ __('supervisor.subscriptions.action_resume') }}
                        </button>
                    </form>
                @endif
                @if($subscription->status === \App\Enums\SessionSubscriptionStatus::CANCELLED)
                    <form method="POST" action="{{ route('manage.subscriptions.activate', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200 transition-colors">
                            <i class="ri-checkbox-circle-line"></i>{{ __('supervisor.subscriptions.action_activate') }}
                        </button>
                    </form>
                @endif
                @if($subscription->status->canCancel())
                    <form method="POST" action="{{ route('manage.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-sm hover:bg-red-200 transition-colors">
                            <i class="ri-close-circle-line"></i>{{ __('supervisor.subscriptions.action_cancel') }}
                        </button>
                    </form>
                @endif

                {{-- Extend form --}}
                <form method="POST" action="{{ route('manage.subscriptions.extend', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}" class="inline-flex items-center gap-2">
                    @csrf
                    <input type="number" name="extend_days" value="30" min="1" max="365" class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg text-sm hover:bg-indigo-200 transition-colors">
                        <i class="ri-calendar-2-line"></i>{{ __('supervisor.subscriptions.action_extend') }}
                    </button>
                </form>
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

</x-layouts.supervisor>
