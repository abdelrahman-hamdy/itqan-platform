<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            الإجراءات السريعة
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Today's Academic Session --}}
            @if($todaySession)
                <a href="{{ $todaySessionUrl }}"
                   class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-play class="w-6 h-6 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-green-900 dark:text-green-100">جلسة أكاديمية اليوم</p>
                        <p class="text-xs text-green-600 dark:text-green-400 truncate">{{ $todaySession->title ?? 'جلسة مجدولة' }}</p>
                        <p class="text-xs text-green-500">{{ $todaySession->scheduled_at->format('H:i') }}</p>
                    </div>
                </a>
            @elseif($todayCourseSession)
                <a href="{{ $todayCourseSessionUrl }}"
                   class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-play class="w-6 h-6 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-green-900 dark:text-green-100">جلسة دورة اليوم</p>
                        <p class="text-xs text-green-600 dark:text-green-400 truncate">{{ $todayCourseSession->course->name ?? 'دورة تفاعلية' }}</p>
                        <p class="text-xs text-green-500">{{ $todayCourseSession->scheduled_at->format('H:i') }}</p>
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

            {{-- Academic Sessions --}}
            <a href="{{ $academicSessionsUrl }}"
               class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-video-camera class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">الجلسات الأكاديمية</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">عرض وإدارة الجلسات</p>
                </div>
            </a>

            {{-- Interactive Courses --}}
            <a href="{{ $interactiveCoursesUrl }}"
               class="flex items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-academic-cap class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-100">الدورات التفاعلية</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400">إدارة الدورات والجلسات</p>
                </div>
            </a>

            {{-- Session Reports --}}
            <a href="{{ $reportsUrl }}"
               class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-document-chart-bar class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-purple-900 dark:text-purple-100">تقارير الجلسات</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400">متابعة أداء الطلاب</p>
                </div>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
