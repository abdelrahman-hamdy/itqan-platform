<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sentry Error Tracking Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('تتبع الأخطاء (Sentry)') }}</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="refreshSentry" type="button"
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('تحديث') }}
                    </button>
                    @if(isset($sentryData['sentry_url']))
                        <a href="{{ $sentryData['sentry_url'] }}" target="_blank"
                           class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors">
                            {{ __('فتح Sentry') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="p-4">
                @if(isset($sentryData['configured']) && !$sentryData['configured'])
                    <div class="text-center py-4">
                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $sentryData['error'] ?? 'Sentry not configured' }}</p>
                    </div>
                @elseif(isset($sentryData['error']))
                    <div class="text-center py-4 text-red-500">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">{{ $sentryData['error'] }}</p>
                    </div>
                @else
                    {{-- Stats Cards --}}
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-center">
                            <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                                {{ $sentryData['total_issues'] ?? 0 }}
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400">{{ __('مشاكل غير محلولة') }}</div>
                        </div>
                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-center">
                            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                                {{ $sentryData['error_count_24h'] ?? 0 }}
                            </div>
                            <div class="text-xs text-orange-600 dark:text-orange-400">{{ __('أخطاء (24 ساعة)') }}</div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-center">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ isset($sentryData['last_seen']) ? \Carbon\Carbon::parse($sentryData['last_seen'])->diffForHumans() : 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{ __('آخر خطأ') }}</div>
                        </div>
                    </div>

                    {{-- Recent Issues --}}
                    @if(!empty($sentryData['recent_issues']))
                        <div class="space-y-2">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('أحدث المشاكل') }}</h4>
                            @foreach($sentryData['recent_issues'] as $issue)
                                <a href="{{ $issue['permalink'] }}" target="_blank"
                                   class="block p-2 bg-gray-50 dark:bg-gray-700 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate text-xs">
                                                {{ $issue['title'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                    {{ $issue['level'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' :
                                                       ($issue['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' :
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300') }}">
                                                    {{ $issue['level'] }}
                                                </span>
                                                <span>{{ $issue['count'] }} {{ __('مرة') }}</span>
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
                        <div class="text-center py-4 text-green-600 dark:text-green-400">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm">{{ __('لا توجد مشاكل غير محلولة!') }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Local Logs Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('سجلات الخادم') }}</h3>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Lines selector --}}
                    <div class="flex items-center gap-2">
                        <label for="lines" class="text-xs text-gray-600 dark:text-gray-400">{{ __('الأسطر:') }}</label>
                        <select wire:model.live="lines" id="lines"
                                class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs py-1">
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Refresh button --}}
                        <button wire:click="refresh" type="button"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ __('تحديث') }}
                        </button>

                        {{-- Clear log button --}}
                        <button wire:click="clearLog" wire:confirm="{{ __('هل أنت متأكد من حذف محتوى السجل؟') }}" type="button"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('مسح') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4">
                {{-- File List --}}
                <div class="lg:col-span-1 border-b lg:border-b-0 lg:border-e border-gray-200 dark:border-gray-700">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                        @forelse($logFiles as $file)
                            <button wire:click="selectFile('{{ $file['path'] }}')" type="button"
                                    class="w-full px-4 py-3 text-start hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors
                                           {{ $selectedFile === $file['path'] ? 'bg-primary-50 dark:bg-primary-900/20 border-s-2 border-primary-500' : '' }}">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $file['name'] }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $file['size'] }} • {{ $file['modified'] }}
                                </div>
                            </button>
                        @empty
                            <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('لا توجد ملفات سجلات') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Log Content --}}
                <div class="lg:col-span-3 bg-gray-900">
                    <div class="px-4 py-2 bg-gray-800 border-b border-gray-700 flex items-center justify-between">
                        <span class="text-xs font-medium text-white">
                            @if($selectedFile)
                                {{ basename($selectedFile) }}
                            @else
                                {{ __('محتوى السجل') }}
                            @endif
                        </span>
                        <span class="text-xs text-gray-400">
                            {{ __('آخر :lines سطر', ['lines' => $lines]) }}
                        </span>
                    </div>
                    <div class="p-4 overflow-x-auto" style="max-height: 400px; overflow-y: auto;">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap break-all leading-relaxed">{{ $logContent }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
