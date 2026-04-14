@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $severityIconClass = match ($worstSeverity) {
        'critical' => 'text-red-500',
        'warning' => 'text-amber-500',
        'info' => 'text-blue-500',
        default => 'text-green-500',
    };

    $groupColorMap = [
        'red' => [
            'dot' => 'bg-red-500',
            'icon' => 'text-red-500',
            'iconBg' => 'bg-red-50',
            'count' => 'text-red-600 bg-red-50',
            'label' => 'text-red-700',
            'labelBg' => 'bg-red-50',
            'action' => 'text-red-700 bg-red-50 hover:bg-red-100',
            'border' => 'border-red-100',
        ],
        'amber' => [
            'dot' => 'bg-amber-500',
            'icon' => 'text-amber-500',
            'iconBg' => 'bg-amber-50',
            'count' => 'text-amber-600 bg-amber-50',
            'label' => 'text-amber-700',
            'labelBg' => 'bg-amber-50',
            'action' => 'text-amber-700 bg-amber-50 hover:bg-amber-100',
            'border' => 'border-amber-100',
        ],
        'blue' => [
            'dot' => 'bg-blue-500',
            'icon' => 'text-blue-500',
            'iconBg' => 'bg-blue-50',
            'count' => 'text-blue-600 bg-blue-50',
            'label' => 'text-blue-700',
            'labelBg' => 'bg-blue-50',
            'action' => 'text-blue-700 bg-blue-50 hover:bg-blue-100',
            'border' => 'border-blue-100',
        ],
    ];
@endphp

<div wire:poll.30s="loadData" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
            <i class="ri-notification-3-line text-xl {{ $severityIconClass }}"></i>
            <h2 class="text-base md:text-lg font-bold text-gray-900">
                {{ __('supervisor.attention.title') }}
            </h2>
            @if($totalCount > 0)
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                    {{ $totalCount }}
                </span>
            @endif
        </div>
        <span class="text-xs text-gray-400 hidden sm:inline">
            {{ __('supervisor.attention.auto_refresh') }}
        </span>
    </div>

    {{-- Body --}}
    <div class="p-5">
        @if($totalCount === 0)
            {{-- All Clear State --}}
            <div class="text-center py-8">
                <div class="w-14 h-14 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="ri-checkbox-circle-line text-2xl text-green-500"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800 mb-1">
                    {{ __('supervisor.attention.all_clear') }}
                </h3>
                <p class="text-sm text-gray-500">
                    {{ __('supervisor.attention.all_clear_description') }}
                </p>
            </div>
        @else
            <div class="space-y-5">
                @foreach($groups as $group)
                    @php $colors = $groupColorMap[$group['color']] ?? $groupColorMap['blue']; @endphp
                    <div
                        x-data="{ open: localStorage.getItem('attention_{{ $group['key'] }}') !== 'collapsed' }"
                        x-init="$watch('open', val => localStorage.setItem('attention_{{ $group['key'] }}', val ? 'open' : 'collapsed'))"
                    >
                        {{-- Section Label --}}
                        <button
                            @click="open = !open"
                            class="flex items-center gap-2 mb-2.5 group"
                        >
                            <span class="w-2 h-2 rounded-full {{ $colors['dot'] }} flex-shrink-0"></span>
                            <span class="text-sm font-bold {{ $colors['label'] }}">
                                {{ __($group['label_key']) }}
                            </span>
                            <span class="text-xs font-medium px-1.5 py-0.5 rounded {{ $colors['count'] }}">
                                {{ collect($group['items'])->sum('count') }}
                            </span>
                            <i
                                :class="open ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
                                class="text-gray-400 text-sm group-hover:text-gray-600 transition-colors"
                            ></i>
                        </button>

                        {{-- Items List --}}
                        <div x-show="open" x-collapse>
                            <div class="border border-gray-100 rounded-lg overflow-hidden divide-y divide-gray-50">
                                @foreach($group['items'] as $item)
                                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50/50 transition-colors">
                                        {{-- Icon --}}
                                        <div class="w-8 h-8 rounded-lg {{ $colors['iconBg'] }} flex items-center justify-center flex-shrink-0">
                                            <i class="{{ $item['icon'] }} {{ $colors['icon'] }}"></i>
                                        </div>

                                        {{-- Label & Count --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-700">{{ __($item['label_key']) }}</span>
                                                <span class="text-xs font-bold {{ $colors['count'] }} px-1.5 py-0.5 rounded">{{ $item['count'] }}</span>
                                            </div>
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            @if($item['route'])
                                                @php
                                                    $href = route($item['route'], ['subdomain' => $subdomain]);
                                                    if (!empty($item['query_params'])) {
                                                        $href .= '?' . http_build_query($item['query_params']);
                                                    }
                                                @endphp
                                                <a
                                                    href="{{ $href }}"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-lg {{ $colors['action'] }} transition-colors"
                                                >
                                                    {{ __($item['action_key']) }}
                                                </a>
                                                {{-- Secondary action (e.g. supervisor calendar for trials) --}}
                                                @if(!empty($item['secondary']))
                                                    @php
                                                        $secHref = route($item['secondary']['route'], ['subdomain' => $subdomain]);
                                                        if (!empty($item['secondary']['query_params'])) {
                                                            $secHref .= '?' . http_build_query($item['secondary']['query_params']);
                                                        }
                                                    @endphp
                                                    <a
                                                        href="{{ $secHref }}"
                                                        class="text-xs font-medium px-3 py-1.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                                                    >
                                                        {{ __($item['secondary']['action_key']) }}
                                                    </a>
                                                @endif
                                            @elseif($item['key'] === 'reviews_awaiting_approval')
                                                {{-- Reviews — toggle inline panel --}}
                                                <button
                                                    wire:click="toggleReviewsPanel"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-lg {{ $colors['action'] }} transition-colors"
                                                >
                                                    {{ __($item['action_key']) }}
                                                </button>
                                            @elseif($item['key'] === 'unconfirmed_student_emails')
                                                {{-- Unconfirmed emails — toggle inline panel --}}
                                                <button
                                                    wire:click="toggleUnconfirmedPanel"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-lg {{ $colors['action'] }} transition-colors"
                                                >
                                                    {{ __($item['action_key']) }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Unconfirmed Emails Panel (inside info group) --}}
                            @if($group['key'] === 'info' && $showUnconfirmedPanel && !empty($unconfirmedStudents['items']))
                                <div class="mt-3 bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-sm font-bold text-gray-800">
                                            <i class="ri-mail-unread-line text-blue-500 me-1"></i>
                                            {{ __('supervisor.attention.unconfirmed_students_panel_title') }}
                                            @if($unconfirmedStudents['total'] > 10)
                                                <span class="text-xs text-gray-500 font-normal">({{ $unconfirmedStudents['total'] }})</span>
                                            @endif
                                        </h3>
                                        <button wire:click="toggleUnconfirmedPanel" class="text-xs text-gray-400 hover:text-gray-600">
                                            {{ __('supervisor.attention.hide_unconfirmed') }}
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($unconfirmedStudents['items'] as $student)
                                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100" wire:key="student-{{ $student['id'] }}">
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-sm font-medium text-gray-900 truncate">{{ $student['name'] }}</span>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $student['email'] }}</p>
                                                </div>
                                                <button
                                                    @click="confirmAction({
                                                        title: @js(__('supervisor.attention.confirm_email')),
                                                        message: @js(__('supervisor.attention.confirm_email_confirmation')),
                                                        confirmText: @js(__('supervisor.attention.confirm_email')),
                                                        isDangerous: false,
                                                        theme: 'green',
                                                        icon: 'ri-mail-check-line',
                                                        onConfirm: () => $wire.confirmStudentEmail({{ $student['id'] }})
                                                    })"
                                                    class="px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors disabled:opacity-50"
                                                >
                                                    {{ __('supervisor.attention.confirm_email') }}
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($unconfirmedStudents['hasMore'])
                                        <div class="mt-2 text-center">
                                            <button wire:click="loadMoreUnconfirmed" wire:loading.attr="disabled"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                <span wire:loading.remove wire:target="loadMoreUnconfirmed">{{ __('supervisor.attention.load_more') }}</span>
                                                <span wire:loading wire:target="loadMoreUnconfirmed">...</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Reviews Panel (inside warning group) --}}
                            @if($group['key'] === 'warning' && $showReviewsPanel && !empty($pendingReviews['items']))
                                <div class="mt-3 bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-sm font-bold text-gray-800">
                                            <i class="ri-star-line text-amber-500 me-1"></i>
                                            {{ __('supervisor.attention.pending_reviews') }}
                                            @if($pendingReviews['total'] > 10)
                                                <span class="text-xs text-gray-500 font-normal">({{ $pendingReviews['total'] }})</span>
                                            @endif
                                        </h3>
                                        <button wire:click="toggleReviewsPanel" class="text-xs text-gray-400 hover:text-gray-600">
                                            {{ __('supervisor.attention.hide_reviews') }}
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($pendingReviews['items'] as $review)
                                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100" wire:key="review-{{ $review['type'] }}-{{ $review['id'] }}">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-0.5">
                                                        <span class="text-sm font-medium text-gray-900 truncate">{{ $review['reviewer_name'] }}</span>
                                                        <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 {{ $review['type'] === 'course' ? 'bg-violet-50 text-violet-600' : 'bg-green-50 text-green-600' }}">
                                                            {{ $review['type'] === 'course' ? __('supervisor.attention.course_review') : __('supervisor.attention.teacher_review') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i class="ri-star-{{ $i <= $review['rating'] ? 'fill' : 'line' }} text-xs {{ $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' }}"></i>
                                                        @endfor
                                                        <span class="text-xs text-gray-400 ms-1 truncate">→ {{ $review['target_name'] }}</span>
                                                    </div>
                                                    @if($review['comment'])
                                                        <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $review['comment'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                                    <button
                                                        wire:click="approveReview('{{ $review['type'] }}', {{ $review['id'] }})"
                                                        wire:loading.attr="disabled"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors disabled:opacity-50"
                                                    >
                                                        {{ __('supervisor.attention.approve') }}
                                                    </button>
                                                    <button
                                                        @click="confirmAction({
                                                            title: @js(__('supervisor.attention.delete_review')),
                                                            message: @js(__('supervisor.attention.confirm_delete_review')),
                                                            confirmText: @js(__('supervisor.attention.delete_review')),
                                                            isDangerous: true,
                                                            icon: 'ri-delete-bin-line',
                                                            onConfirm: () => $wire.deleteReview('{{ $review['type'] }}', {{ $review['id'] }})
                                                        })"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors disabled:opacity-50"
                                                    >
                                                        {{ __('supervisor.attention.delete_review') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($pendingReviews['hasMore'])
                                        <div class="mt-2 text-center">
                                            <button wire:click="loadMoreReviews" wire:loading.attr="disabled"
                                                    class="text-xs text-amber-600 hover:text-amber-800 font-medium">
                                                <span wire:loading.remove wire:target="loadMoreReviews">{{ __('supervisor.attention.load_more') }}</span>
                                                <span wire:loading wire:target="loadMoreReviews">...</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
