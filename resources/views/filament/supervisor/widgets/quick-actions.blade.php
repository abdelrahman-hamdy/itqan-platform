<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            الإجراءات السريعة
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Ongoing Sessions --}}
            <a href="{{ $ongoingSessionsUrl }}"
               class="flex items-center gap-3 p-4 {{ $ongoingSessions > 0 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} rounded-xl border hover:opacity-90 transition-opacity">
                <div class="flex-shrink-0 w-12 h-12 {{ $ongoingSessions > 0 ? 'bg-green-500' : 'bg-gray-400' }} rounded-xl flex items-center justify-center relative">
                    <x-heroicon-o-video-camera class="w-6 h-6 text-white" />
                    @if($ongoingSessions > 0)
                        <span class="absolute -top-1 -end-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                            {{ $ongoingSessions }}
                        </span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium {{ $ongoingSessions > 0 ? 'text-green-900 dark:text-green-100' : 'text-gray-700 dark:text-gray-300' }}">جلسات جارية الآن</p>
                    <p class="text-xs {{ $ongoingSessions > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                        {{ $ongoingSessions > 0 ? $ongoingSessions . ' جلسة قيد التنفيذ' : 'لا توجد جلسات جارية' }}
                    </p>
                </div>
            </a>

            {{-- Today's Scheduled --}}
            <a href="{{ $todaySessionsUrl }}"
               class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-calendar class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">جلسات اليوم</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ $todayScheduled }} جلسة مجدولة</p>
                </div>
            </a>

            {{-- Monitored Circles --}}
            <a href="{{ $circlesUrl }}"
               class="flex items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-user-group class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-100">الحلقات المراقبة</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400">{{ $activeCircles }} حلقة نشطة</p>
                </div>
            </a>

            {{-- All Sessions --}}
            <a href="{{ $allSessionsUrl }}"
               class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-queue-list class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-purple-900 dark:text-purple-100">جميع الجلسات</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400">عرض وتصفية الجلسات</p>
                </div>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
