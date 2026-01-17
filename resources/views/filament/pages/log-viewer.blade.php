<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Header with Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                {{-- Lines selector --}}
                <div class="flex items-center gap-2">
                    <label for="lines" class="text-sm text-gray-600 dark:text-gray-400">{{ __('عدد الأسطر:') }}</label>
                    <select wire:model.live="lines" id="lines"
                            class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Refresh button --}}
                <button wire:click="refresh" type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('تحديث') }}
                </button>

                {{-- Clear log button --}}
                <button wire:click="clearLog" wire:confirm="{{ __('هل أنت متأكد من حذف محتوى السجل؟') }}" type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    {{ __('مسح السجل') }}
                </button>

                {{-- External log viewer link --}}
                <a href="{{ $this->getExternalLogViewerUrl() }}" target="_blank"
                   class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    {{ __('عارض متقدم') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            {{-- File List --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('ملفات السجلات') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        @forelse($logFiles as $file)
                            <button wire:click="selectFile('{{ $file['path'] }}')" type="button"
                                    class="w-full px-4 py-3 text-start hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors
                                           {{ $selectedFile === $file['path'] ? 'bg-primary-50 dark:bg-primary-900/20 border-r-2 border-primary-500' : '' }}">
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
            </div>

            {{-- Log Content --}}
            <div class="lg:col-span-3">
                <div class="bg-gray-900 rounded-xl border border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 bg-gray-800 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-white">
                            @if($selectedFile)
                                {{ basename($selectedFile) }}
                            @else
                                {{ __('محتوى السجل') }}
                            @endif
                        </h3>
                        <span class="text-xs text-gray-400">
                            {{ __('آخر :lines سطر', ['lines' => $lines]) }}
                        </span>
                    </div>
                    <div class="p-4 overflow-x-auto" style="max-height: 600px; overflow-y: auto;">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap break-all leading-relaxed">{{ $logContent }}</pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Help Text --}}
        <div class="text-xs text-gray-400 dark:text-gray-500">
            <p>{{ __('نصيحة: استخدم "عارض متقدم" للبحث والفلترة المتقدمة في السجلات.') }}</p>
        </div>
    </div>
</x-filament-panels::page>
