<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-3">
            {{-- Status Colors Legend (main - matches calendar colors) --}}
            @if($showStatusIndicators && count($statusIndicators) > 0)
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <x-heroicon-m-swatch class="w-3.5 h-3.5" />
                        ألوان الجلسات على التقويم
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($statusIndicators as $status)
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span
                                    class="w-3 h-3 rounded-full flex-shrink-0"
                                    style="background-color: {{ $status['color'] }}"
                                ></span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ $status['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Session Types (secondary - icons only) --}}
            @if($showSessionTypes && count($sessionTypes) > 0)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <x-heroicon-m-squares-2x2 class="w-3.5 h-3.5" />
                        أنواع الجلسات
                    </h4>
                    <div class="flex flex-wrap gap-3">
                        @foreach($sessionTypes as $type)
                            <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                @if(isset($type['icon']))
                                    <x-dynamic-component
                                        :component="$type['icon']"
                                        class="w-4 h-4 text-gray-500 dark:text-gray-400"
                                    />
                                @endif
                                <span>{{ $type['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Calendar Hints --}}
            <div class="flex flex-wrap justify-center gap-4 text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-m-cursor-arrow-rays class="w-3.5 h-3.5" />
                    <span>اسحب لإعادة الجدولة</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <x-heroicon-m-arrows-pointing-out class="w-3.5 h-3.5" />
                    <span>اسحب الحواف لتغيير المدة</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <x-heroicon-m-cursor-arrow-ripple class="w-3.5 h-3.5" />
                    <span>اضغط على اليوم لعرض الجلسات</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
