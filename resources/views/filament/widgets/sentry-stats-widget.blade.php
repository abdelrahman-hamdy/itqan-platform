<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ __('Error Tracking (Sentry)') }}</span>
            </div>
        </x-slot>

        @if(isset($sentry_url))
            <x-slot name="headerEnd">
                <a href="{{ $sentry_url }}" target="_blank"
                   class="text-sm text-primary-600 hover:text-primary-500 flex items-center gap-1">
                    {{ __('Open Sentry Dashboard') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </x-slot>
        @endif

        @if(isset($configured) && !$configured)
            <div class="p-4 text-center">
                <div class="text-gray-400 mb-2">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    {{ $error ?? 'Sentry not configured' }}
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    {{ __('Add SENTRY_ORG_SLUG, SENTRY_PROJECT_SLUG, and SENTRY_AUTH_TOKEN to your .env file') }}
                </p>
            </div>
        @elseif(isset($error))
            <div class="p-4 text-center text-red-500">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $error }}
            </div>
        @else
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-center">
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                        {{ $total_issues ?? 0 }}
                    </div>
                    <div class="text-xs text-red-600 dark:text-red-400">{{ __('Unresolved Issues') }}</div>
                </div>
                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-center">
                    <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                        {{ $error_count_24h ?? 0 }}
                    </div>
                    <div class="text-xs text-orange-600 dark:text-orange-400">{{ __('Errors (24h)') }}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $last_seen ? \Carbon\Carbon::parse($last_seen)->diffForHumans() : 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('Last Error') }}</div>
                </div>
            </div>

            @if(!empty($recent_issues))
                <div class="space-y-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Recent Issues') }}</h4>
                    @foreach($recent_issues as $issue)
                        <a href="{{ $issue['permalink'] }}" target="_blank"
                           class="block p-2 bg-gray-50 dark:bg-gray-800 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $issue['title'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center gap-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                            {{ $issue['level'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' :
                                               ($issue['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' :
                                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                            {{ $issue['level'] }}
                                        </span>
                                        <span>{{ $issue['count'] }} {{ __('occurrences') }}</span>
                                        <span>{{ $issue['lastSeen'] }}</span>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center text-green-600 dark:text-green-400">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('No unresolved issues!') }}
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
