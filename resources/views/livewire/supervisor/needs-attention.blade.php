@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $severityHeaderClasses = match ($worstSeverity) {
        'critical' => 'bg-red-50 border-red-200',
        'warning' => 'bg-amber-50 border-amber-200',
        'info' => 'bg-blue-50 border-blue-200',
        default => 'bg-green-50 border-green-200',
    };

    $severityHeaderIcon = match ($worstSeverity) {
        'critical' => 'ri-alarm-warning-line text-red-600',
        'warning' => 'ri-alert-line text-amber-600',
        'info' => 'ri-information-line text-blue-600',
        default => 'ri-checkbox-circle-line text-green-600',
    };

    $groupColorMap = [
        'red' => [
            'border' => 'border-e-red-500',
            'bg' => 'bg-red-50',
            'text' => 'text-red-700',
            'count' => 'text-red-600',
            'badge' => 'bg-red-100 text-red-700',
            'header' => 'text-red-800',
            'headerBg' => 'bg-red-50',
        ],
        'amber' => [
            'border' => 'border-e-amber-500',
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-700',
            'count' => 'text-amber-600',
            'badge' => 'bg-amber-100 text-amber-700',
            'header' => 'text-amber-800',
            'headerBg' => 'bg-amber-50',
        ],
        'blue' => [
            'border' => 'border-e-blue-500',
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-700',
            'count' => 'text-blue-600',
            'badge' => 'bg-blue-100 text-blue-700',
            'header' => 'text-blue-800',
            'headerBg' => 'bg-blue-50',
        ],
    ];
@endphp

<div wire:poll.30s="loadData">
    {{-- Header Bar --}}
    <div class="rounded-xl border {{ $severityHeaderClasses }} p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="{{ $severityHeaderIcon }} text-xl"></i>
                <h2 class="text-base md:text-lg font-bold text-gray-900">
                    {{ __('supervisor.attention.title') }}
                </h2>
            </div>
            @if($totalCount > 0)
                <span class="text-sm text-gray-600">
                    {{ __('supervisor.attention.total_items', ['count' => $totalCount]) }}
                </span>
            @endif
        </div>
    </div>

    @if($totalCount === 0)
        {{-- All Clear State --}}
        <div class="text-center py-10">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-checkbox-circle-line text-3xl text-green-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-1">
                {{ __('supervisor.attention.all_clear') }}
            </h3>
            <p class="text-sm text-gray-500">
                {{ __('supervisor.attention.all_clear_description') }}
            </p>
        </div>
    @else
        {{-- Severity Groups --}}
        @foreach($groups as $group)
            @php $colors = $groupColorMap[$group['color']] ?? $groupColorMap['blue']; @endphp
            <div
                x-data="{ open: localStorage.getItem('attention_{{ $group['key'] }}') !== 'collapsed' }"
                x-init="$watch('open', val => localStorage.setItem('attention_{{ $group['key'] }}', val ? 'open' : 'collapsed'))"
                class="mb-4"
            >
                {{-- Section Header --}}
                <button
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg {{ $colors['headerBg'] }} hover:opacity-90 transition-opacity"
                >
                    <div class="flex items-center gap-2">
                        <i
                            :class="open ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
                            class="{{ $colors['header'] }} text-lg"
                        ></i>
                        <span class="text-sm font-bold {{ $colors['header'] }}">
                            {{ __($group['label_key']) }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $colors['badge'] }}">
                            {{ collect($group['items'])->sum('count') }}
                        </span>
                    </div>
                </button>

                {{-- Cards Grid --}}
                <div
                    x-show="open"
                    x-collapse
                    class="mt-2"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($group['items'] as $item)
                            @if($item['route'])
                                <a
                                    href="{{ route($item['route'], ['subdomain' => $subdomain]) }}"
                                    class="block bg-white rounded-xl border border-gray-200 border-e-4 {{ $colors['border'] }} p-4 hover:shadow-md transition-shadow group"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg {{ $colors['bg'] }} flex items-center justify-center flex-shrink-0">
                                                <i class="{{ $item['icon'] }} text-lg {{ $colors['text'] }}"></i>
                                            </div>
                                            <div>
                                                <p class="text-2xl font-bold {{ $colors['count'] }}">{{ $item['count'] }}</p>
                                                <p class="text-xs text-gray-600">{{ __($item['label_key']) }}</p>
                                            </div>
                                        </div>
                                        <i class="ri-arrow-left-s-line text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                                    </div>
                                </a>
                            @else
                                {{-- Reviews card (no navigation — toggles panel) --}}
                                <button
                                    wire:click="toggleReviewsPanel"
                                    class="w-full text-start bg-white rounded-xl border border-gray-200 border-e-4 {{ $colors['border'] }} p-4 hover:shadow-md transition-shadow group"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg {{ $colors['bg'] }} flex items-center justify-center flex-shrink-0">
                                                <i class="{{ $item['icon'] }} text-lg {{ $colors['text'] }}"></i>
                                            </div>
                                            <div>
                                                <p class="text-2xl font-bold {{ $colors['count'] }}">{{ $item['count'] }}</p>
                                                <p class="text-xs text-gray-600">{{ __($item['label_key']) }}</p>
                                            </div>
                                        </div>
                                        <i class="ri-arrow-down-s-line text-gray-400 group-hover:text-gray-600 transition-colors {{ $showReviewsPanel ? 'rotate-180' : '' }}"></i>
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </div>

                    {{-- Reviews Panel (after warning group cards) --}}
                    @if($group['key'] === 'warning' && $showReviewsPanel && !empty($pendingReviews['items']))
                        <div class="mt-3 bg-white rounded-xl border border-gray-200 p-4">
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

                            <div class="space-y-3">
                                @foreach($pendingReviews['items'] as $review)
                                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg" wire:key="review-{{ $review['type'] }}-{{ $review['id'] }}">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-medium text-gray-900">{{ $review['reviewer_name'] }}</span>
                                                <span class="text-xs px-1.5 py-0.5 rounded {{ $review['type'] === 'course' ? 'bg-violet-100 text-violet-700' : 'bg-green-100 text-green-700' }}">
                                                    {{ $review['type'] === 'course' ? __('supervisor.attention.course_review') : __('supervisor.attention.teacher_review') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1 mb-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="ri-star-{{ $i <= $review['rating'] ? 'fill' : 'line' }} text-sm {{ $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' }}"></i>
                                                @endfor
                                                <span class="text-xs text-gray-500 ms-1">→ {{ $review['target_name'] }}</span>
                                            </div>
                                            @if($review['comment'])
                                                <p class="text-xs text-gray-600 line-clamp-2">{{ $review['comment'] }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <button
                                                wire:click="approveReview('{{ $review['type'] }}', {{ $review['id'] }})"
                                                wire:loading.attr="disabled"
                                                class="px-2.5 py-1.5 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors disabled:opacity-50"
                                            >
                                                <i class="ri-check-line me-0.5"></i>
                                                {{ __('supervisor.attention.approve') }}
                                            </button>
                                            <button
                                                wire:click="deleteReview('{{ $review['type'] }}', {{ $review['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:confirm="{{ __('supervisor.attention.confirm_delete_review') }}"
                                                class="px-2.5 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors disabled:opacity-50"
                                            >
                                                <i class="ri-delete-bin-line me-0.5"></i>
                                                {{ __('supervisor.attention.delete_review') }}
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Auto-refresh note --}}
        <p class="text-xs text-gray-400 text-center mt-2">
            {{ __('supervisor.attention.auto_refresh') }}
        </p>
    @endif
</div>
