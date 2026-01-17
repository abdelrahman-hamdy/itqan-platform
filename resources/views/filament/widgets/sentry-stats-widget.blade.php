<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header --}}
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-[#362D59] dark:text-[#8B7FBA]" viewBox="0 0 72 66" fill="currentColor">
                    <path d="M29,2.26a4.67,4.67,0,0,0-8,0L14.42,13.53A32.21,32.21,0,0,1,32.17,40.19H27.55A27.68,27.68,0,0,0,12.09,17.47L6,28a15.92,15.92,0,0,1,9.23,12.17H4.62A.76.76,0,0,1,4,39.06l2.94-5a10.74,10.74,0,0,0-3.36-1.9l-2.91,5a4.54,4.54,0,0,0,1.69,6.24A4.66,4.66,0,0,0,4.62,44H19.15a19.4,19.4,0,0,0-8-17.31l2.31-4A23.87,23.87,0,0,1,23.76,44H36.07a35.88,35.88,0,0,0-16.41-31.8l4.67-8a.77.77,0,0,1,1.05-.27c.53.29,20.29,34.77,20.66,35.17a.76.76,0,0,1-.68,1.13H40.6q.09,1.91,0,3.81h4.78A4.59,4.59,0,0,0,50,39.43a4.49,4.49,0,0,0-.62-2.28Z"/>
                </svg>
                <span>{{ __('تتبع الأخطاء') }}</span>
            </div>
        </x-slot>

        {{-- Header Actions --}}
        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                @if($this->isConfigured())
                    <x-filament::badge :color="$this->getHealthColor()">
                        {{ $this->getHealthLabel() }}
                    </x-filament::badge>
                @endif

                <x-filament::icon-button
                    icon="heroicon-m-arrow-path"
                    wire:click="refresh"
                    wire:loading.attr="disabled"
                    wire:loading.class="animate-spin"
                    color="gray"
                    size="sm"
                    :tooltip="__('تحديث')"
                />

                @if($this->getSentryUrl())
                    <x-filament::icon-button
                        icon="heroicon-m-arrow-top-right-on-square"
                        tag="a"
                        :href="$this->getSentryUrl()"
                        target="_blank"
                        color="gray"
                        size="sm"
                        :tooltip="__('فتح Sentry')"
                    />
                @endif
            </div>
        </x-slot>

        {{-- Content --}}
        @if(!($stats['configured'] ?? true))
            {{-- Not Configured State --}}
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <x-heroicon-o-cog-6-tooth class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" />
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Sentry غير مُعدّ') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $stats['error'] ?? __('يرجى إعداد متغيرات البيئة') }}
                </p>
            </div>

        @elseif($this->hasError())
            {{-- Error State --}}
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-danger-500 mb-3" />
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('خطأ في الاتصال') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $this->getError() }}</p>
                <x-filament::button wire:click="refresh" size="sm" color="gray" class="mt-3">
                    {{ __('إعادة المحاولة') }}
                </x-filament::button>
            </div>

        @else
            {{-- Stats Grid - 4 columns --}}
            <div class="grid grid-cols-4 gap-4">
                {{-- Unresolved Issues --}}
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-icon flex h-8 w-8 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-400/10">
                            <x-heroicon-o-bug-ant class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                        </span>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('مشاكل غير محلولة') }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ number_format($stats['total_issues'] ?? 0) }}
                        </span>
                    </div>
                </div>

                {{-- Events 24h --}}
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-icon flex h-8 w-8 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-400/10">
                            <x-heroicon-o-fire class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                        </span>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('أحداث (24 ساعة)') }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-x-2">
                        <span class="text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ number_format($stats['error_count_24h'] ?? 0) }}
                        </span>
                        <x-dynamic-component :component="$this->getTrendIcon()" @class(['h-5 w-5', $this->getTrendColor()]) />
                    </div>
                </div>

                {{-- Crash-Free Rate --}}
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-icon flex h-8 w-8 items-center justify-center rounded-lg bg-success-50 dark:bg-success-400/10">
                            <x-heroicon-o-shield-check class="h-5 w-5 text-success-600 dark:text-success-400" />
                        </span>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('معدل الاستقرار') }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ isset($stats['crash_free_rate']) ? $stats['crash_free_rate'] . '%' : 'N/A' }}
                        </span>
                    </div>
                </div>

                {{-- Affected Users --}}
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-icon flex h-8 w-8 items-center justify-center rounded-lg bg-info-50 dark:bg-info-400/10">
                            <x-heroicon-o-users class="h-5 w-5 text-info-600 dark:text-info-400" />
                        </span>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('مستخدمين متأثرين') }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ number_format($stats['affected_users'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Recent Issues Section --}}
            @if(!empty($stats['recent_issues']))
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/10">
                    <button wire:click="toggleIssues" class="w-full flex items-center justify-between text-start mb-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-chevron-down @class([
                                'w-4 h-4 text-gray-400 transition-transform',
                                '-rotate-90' => !$showIssues,
                            ]) />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('أحدث المشاكل') }}
                            </span>
                            <x-filament::badge color="gray" size="sm">
                                {{ count($stats['recent_issues']) }}
                            </x-filament::badge>
                        </div>
                    </button>

                    @if($showIssues)
                        <div class="space-y-2">
                            @foreach($stats['recent_issues'] as $issue)
                                <a
                                    href="{{ $issue['permalink'] }}"
                                    target="_blank"
                                    class="block p-3 rounded-lg bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 ring-1 ring-gray-950/5 dark:ring-white/10 transition-colors"
                                >
                                    <div class="flex items-start gap-3">
                                        <x-filament::badge
                                            :color="match($issue['level']) {
                                                'error' => 'danger',
                                                'warning' => 'warning',
                                                'info' => 'info',
                                                default => 'gray'
                                            }"
                                            size="sm"
                                        >
                                            {{ $issue['level'] }}
                                        </x-filament::badge>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $issue['title'] }}
                                            </p>
                                            @if($issue['culprit'])
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                                    {{ $issue['culprit'] }}
                                                </p>
                                            @endif
                                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ number_format($issue['count']) }} {{ __('حدث') }}</span>
                                                @if($issue['userCount'] > 0)
                                                    <span>{{ number_format($issue['userCount']) }} {{ __('مستخدم') }}</span>
                                                @endif
                                                <span>{{ $issue['lastSeen'] }}</span>
                                            </div>
                                        </div>

                                        <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @elseif(($stats['total_issues'] ?? 0) === 0)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/10">
                    <div class="flex flex-col items-center justify-center py-4 text-center">
                        <x-heroicon-o-check-circle class="w-8 h-8 text-success-500 mb-2" />
                        <p class="text-sm font-medium text-success-600 dark:text-success-400">
                            {{ __('لا توجد مشاكل غير محلولة!') }}
                        </p>
                    </div>
                </div>
            @endif
        @endif

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="refresh" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 items-center justify-center rounded-xl z-10">
            <x-filament::loading-indicator class="h-6 w-6" />
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
