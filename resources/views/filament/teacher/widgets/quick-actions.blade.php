<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            الإجراءات السريعة
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Today's Session --}}
            @if($todaySession)
                <a href="{{ $todaySessionUrl }}"
                   class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-play class="w-6 h-6 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-green-900 dark:text-green-100">جلسة اليوم القادمة</p>
                        <p class="text-xs text-green-600 dark:text-green-400 truncate">{{ $todaySession->title ?? 'جلسة مجدولة' }}</p>
                        <p class="text-xs text-green-500">{{ $todaySession->scheduled_at->format('H:i') }}</p>
                    </div>
                </a>
            @else
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0 w-12 h-12 bg-gray-400 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-calendar class="w-6 h-6 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">لا توجد جلسات اليوم</p>
                        <p class="text-xs text-gray-500">استمتع بيومك!</p>
                    </div>
                </div>
            @endif

            {{-- Trial Requests --}}
            <a href="{{ $trialRequestsUrl }}"
               class="flex items-center gap-3 p-4 {{ $pendingTrials > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' }} rounded-xl border hover:opacity-90 transition-opacity">
                <div class="flex-shrink-0 w-12 h-12 {{ $pendingTrials > 0 ? 'bg-amber-500' : 'bg-blue-500' }} rounded-xl flex items-center justify-center relative">
                    <x-heroicon-o-user-plus class="w-6 h-6 text-white" />
                    @if($pendingTrials > 0)
                        <span class="absolute -top-1 -end-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            {{ $pendingTrials }}
                        </span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium {{ $pendingTrials > 0 ? 'text-amber-900 dark:text-amber-100' : 'text-blue-900 dark:text-blue-100' }}">طلبات الجلسات التجريبية</p>
                    <p class="text-xs {{ $pendingTrials > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400' }}">
                        {{ $pendingTrials > 0 ? $pendingTrials . ' طلب في الانتظار' : 'لا توجد طلبات معلقة' }}
                    </p>
                </div>
            </a>

            {{-- My Sessions --}}
            <a href="{{ $sessionsUrl }}"
               class="flex items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-video-camera class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-100">جلساتي</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400">عرض وإدارة جميع الجلسات</p>
                </div>
            </a>

            {{-- Student Reports --}}
            <a href="{{ $reportsUrl }}"
               class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-document-chart-bar class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-purple-900 dark:text-purple-100">تقارير الطلاب</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400">متابعة أداء الطلاب</p>
                </div>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
