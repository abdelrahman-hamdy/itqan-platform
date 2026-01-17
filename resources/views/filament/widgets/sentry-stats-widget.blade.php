<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header --}}
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                {{-- Sentry Logo --}}
                <div class="flex-shrink-0 w-8 h-8 bg-[#362D59] rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 72 66" fill="currentColor">
                        <path d="M29,2.26a4.67,4.67,0,0,0-8,0L14.42,13.53A32.21,32.21,0,0,1,32.17,40.19H27.55A27.68,27.68,0,0,0,12.09,17.47L6,28a15.92,15.92,0,0,1,9.23,12.17H4.62A.76.76,0,0,1,4,39.06l2.94-5a10.74,10.74,0,0,0-3.36-1.9l-2.91,5a4.54,4.54,0,0,0,1.69,6.24A4.66,4.66,0,0,0,4.62,44H19.15a19.4,19.4,0,0,0-8-17.31l2.31-4A23.87,23.87,0,0,1,23.76,44H36.07a35.88,35.88,0,0,0-16.41-31.8l4.67-8a.77.77,0,0,1,1.05-.27c.53.29,20.29,34.77,20.66,35.17a.76.76,0,0,1-.68,1.13H40.6q.09,1.91,0,3.81h4.78A4.59,4.59,0,0,0,50,39.43a4.49,4.49,0,0,0-.62-2.28Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('تتبع الأخطاء') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sentry Error Tracking</p>
                </div>
            </div>
        </x-slot>

        {{-- Header Actions --}}
        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                {{-- Health Status Badge --}}
                @if($this->isConfigured())
                    <span @class([
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $this->getHealthStatus() === 'healthy',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' => $this->getHealthStatus() === 'warning',
                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $this->getHealthStatus() === 'critical',
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' => $this->getHealthStatus() === 'unknown',
                    ])>
                        <span @class([
                            'w-2 h-2 rounded-full',
                            'bg-green-500 animate-pulse' => $this->getHealthStatus() === 'healthy',
                            'bg-yellow-500 animate-pulse' => $this->getHealthStatus() === 'warning',
                            'bg-red-500 animate-pulse' => $this->getHealthStatus() === 'critical',
                            'bg-gray-500' => $this->getHealthStatus() === 'unknown',
                        ])></span>
                        {{ $this->getHealthLabel() }}
                    </span>
                @endif

                {{-- Refresh Button --}}
                <x-filament::icon-button
                    icon="heroicon-m-arrow-path"
                    wire:click="refresh"
                    wire:loading.attr="disabled"
                    wire:loading.class="animate-spin"
                    color="gray"
                    size="sm"
                    :tooltip="__('تحديث')"
                />

                {{-- Open Sentry Link --}}
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
        <div class="space-y-4">
            {{-- Not Configured State --}}
            @if(!($stats['configured'] ?? true))
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-cog-6-tooth class="w-8 h-8 text-gray-400" />
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                        {{ __('Sentry غير مُعدّ') }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm">
                        {{ $stats['error'] ?? __('يرجى إعداد متغيرات البيئة SENTRY_ORG_SLUG و SENTRY_PROJECT_SLUG و SENTRY_AUTH_TOKEN') }}
                    </p>
                </div>

            {{-- Error State --}}
            @elseif($this->hasError())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-500" />
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                        {{ __('خطأ في الاتصال') }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm">
                        {{ $this->getError() }}
                    </p>
                    <x-filament::button
                        wire:click="refresh"
                        size="sm"
                        color="gray"
                        class="mt-4"
                    >
                        {{ __('إعادة المحاولة') }}
                    </x-filament::button>
                </div>

            {{-- Stats Display --}}
            @else
                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {{-- Unresolved Issues --}}
                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/10 p-4 border border-red-200/50 dark:border-red-800/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">
                                    {{ __('مشاكل غير محلولة') }}
                                </p>
                                <p class="text-2xl font-bold text-red-700 dark:text-red-300">
                                    {{ number_format($stats['total_issues'] ?? 0) }}
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center">
                                <x-heroicon-o-bug-ant class="w-5 h-5 text-red-500" />
                            </div>
                        </div>
                    </div>

                    {{-- Events 24h with Trend --}}
                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-900/10 p-4 border border-orange-200/50 dark:border-orange-800/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-orange-600 dark:text-orange-400 mb-1">
                                    {{ __('أحداث (24 ساعة)') }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                                        {{ number_format($stats['error_count_24h'] ?? 0) }}
                                    </p>
                                    <x-dynamic-component
                                        :component="$this->getTrendIcon()"
                                        @class(['w-4 h-4', $this->getTrendColor()])
                                    />
                                </div>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center">
                                <x-heroicon-o-fire class="w-5 h-5 text-orange-500" />
                            </div>
                        </div>
                    </div>

                    {{-- Crash-Free Rate --}}
                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-900/10 p-4 border border-emerald-200/50 dark:border-emerald-800/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-1">
                                    {{ __('معدل الاستقرار') }}
                                </p>
                                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                                    @if(isset($stats['crash_free_rate']))
                                        {{ $stats['crash_free_rate'] }}%
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-emerald-500" />
                            </div>
                        </div>
                    </div>

                    {{-- Affected Users --}}
                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/10 p-4 border border-purple-200/50 dark:border-purple-800/30">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-purple-600 dark:text-purple-400 mb-1">
                                    {{ __('مستخدمين متأثرين') }}
                                </p>
                                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                                    {{ number_format($stats['affected_users'] ?? 0) }}
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <x-heroicon-o-users class="w-5 h-5 text-purple-500" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Issues Section --}}
                @if(!empty($stats['recent_issues']))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        {{-- Section Header with Toggle --}}
                        <button
                            wire:click="toggleIssues"
                            class="w-full flex items-center justify-between text-left mb-3 group"
                        >
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-chevron-down @class([
                                    'w-4 h-4 text-gray-400 transition-transform duration-200',
                                    'rotate-180' => !$showIssues,
                                ]) />
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('أحدث المشاكل') }}
                                </span>
                                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">
                                    {{ count($stats['recent_issues']) }}
                                </span>
                            </div>
                        </button>

                        {{-- Issues List --}}
                        @if($showIssues)
                            <div class="space-y-2" wire:transition>
                                @foreach($stats['recent_issues'] as $issue)
                                    <a
                                        href="{{ $issue['permalink'] }}"
                                        target="_blank"
                                        class="block p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200/50 dark:border-gray-700/50 transition-colors group"
                                    >
                                        <div class="flex items-start gap-3">
                                            {{-- Level Badge --}}
                                            <span @class([
                                                'flex-shrink-0 mt-0.5 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                                'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' => $issue['level'] === 'error',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' => $issue['level'] === 'warning',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' => $issue['level'] === 'info',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => !in_array($issue['level'], ['error', 'warning', 'info']),
                                            ])>
                                                {{ $issue['level'] }}
                                            </span>

                                            {{-- Issue Details --}}
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                                    {{ $issue['title'] }}
                                                </p>
                                                @if($issue['culprit'])
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                                        {{ $issue['culprit'] }}
                                                    </p>
                                                @endif
                                                <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="inline-flex items-center gap-1">
                                                        <x-heroicon-m-fire class="w-3.5 h-3.5" />
                                                        {{ number_format($issue['count']) }} {{ __('حدث') }}
                                                    </span>
                                                    @if($issue['userCount'] > 0)
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-m-users class="w-3.5 h-3.5" />
                                                            {{ number_format($issue['userCount']) }} {{ __('مستخدم') }}
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex items-center gap-1">
                                                        <x-heroicon-m-clock class="w-3.5 h-3.5" />
                                                        {{ $issue['lastSeen'] }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- External Link Icon --}}
                                            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors flex-shrink-0" />
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    {{-- No Issues - Healthy State --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-3">
                                <x-heroicon-o-check-circle class="w-6 h-6 text-green-500" />
                            </div>
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">
                                {{ __('لا توجد مشاكل غير محلولة!') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('التطبيق يعمل بشكل سليم') }}
                            </p>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="refresh" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 items-center justify-center rounded-xl z-10">
            <div class="flex flex-col items-center gap-2">
                <x-filament::loading-indicator class="h-8 w-8" />
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('جاري التحديث...') }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
