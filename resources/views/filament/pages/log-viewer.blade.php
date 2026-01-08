<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Quick Actions --}}
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('View and manage application logs. Click on any log file to view its contents.') }}
            </div>
            <a href="{{ url('/log-viewer') }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                {{ __('Open in New Tab') }}
            </a>
        </div>

        {{-- Embedded Log Viewer --}}
        <div class="w-full rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <iframe
                src="{{ url('/log-viewer') }}"
                class="w-full border-0"
                style="height: calc(100vh - 250px); min-height: 600px;"
                frameborder="0"
                title="Log Viewer"
            ></iframe>
        </div>

        {{-- Help Text --}}
        <div class="text-xs text-gray-400 dark:text-gray-500">
            <p>{{ __('Tip: Use the search functionality within Log Viewer to find specific errors. You can also filter by log level and clear old logs.') }}</p>
        </div>
    </div>
</x-filament-panels::page>
