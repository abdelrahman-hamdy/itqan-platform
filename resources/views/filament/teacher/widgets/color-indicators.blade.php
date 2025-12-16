<x-filament-widgets::widget>
    {{-- Color Indicators - Under Calendar Table --}}
    <div class="mt-4 flex flex-col gap-4 text-sm border-t pt-4 rounded-lg px-6 py-4
                text-gray-600 dark:text-gray-300
                bg-white dark:bg-gray-800
                border-gray-200 dark:border-gray-700">

        {{-- Session Type Indicators --}}
        <div class="flex flex-wrap items-center gap-6">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">نوع الجلسة:</span>
            @foreach ($this->getSessionTypeIndicators() as $indicator)
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $indicator['color'] }} !important;"></div>
                    <span class="dark:text-white">{{ $indicator['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
