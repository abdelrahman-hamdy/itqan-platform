<x-layouts.teacher
    :title="'تقرير الحلقة الفردية - ' . $student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الكامل للحلقة الفردية'">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => 'الحلقات الفردية', 'route' => route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $student->name, 'route' => route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]), 'truncate' => true],
            ['label' => 'التقرير الكامل'],
        ]"
        view-type="teacher"
    />

    <!-- Header -->
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900">التقرير الكامل</h1>
                <p class="text-sm md:text-base text-gray-600 mt-0.5 md:mt-1">الطالب: {{ $student->name }}</p>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] inline-flex items-center px-3 md:px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ml-1"></i>
                    عودة للحلقة
                </a>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-6">
        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">الجلسات المكتملة</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $overall['sessions_completed'] }}</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-blue-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">نسبة الحضور</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $attendance['attendance_rate'] }}%</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-star-line text-green-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">الصفحات المحفوظة</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ number_format($progress['pages_memorized'] ?? 0, 1) }}</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-book-open-line text-purple-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">نسبة التقدم</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $overall['progress_percentage'] }}%</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-pie-chart-line text-yellow-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Attendance Card -->
            <x-reports.attendance-card :attendance="$attendance" />

            <!-- Progress Card -->
            <x-reports.progress-card
                :progress="$progress"
                :showLifetime="isset($progress['lifetime_pages_memorized'])" />

            <!-- Performance Card -->
            <x-reports.performance-card
                :performance="$progress"
                title="الأداء الأكاديمي" />

            <!-- Homework Section -->
            @if($homework['total_assigned'] > 0)
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">الواجبات</h2>
                <div class="grid grid-cols-2 gap-3 md:gap-4">
                    <div>
                        <p class="text-xs md:text-sm text-gray-600">إجمالي الواجبات</p>
                        <p class="text-lg md:text-xl font-bold text-gray-900">{{ $homework['total_assigned'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs md:text-sm text-gray-600">مكتمل</p>
                        <p class="text-lg md:text-xl font-bold text-green-600">{{ $homework['completed'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs md:text-sm text-gray-600">نسبة الإنجاز</p>
                        <p class="text-lg md:text-xl font-bold text-blue-600">{{ $homework['completion_rate'] }}%</p>
                    </div>
                    <div>
                        <p class="text-xs md:text-sm text-gray-600">متوسط الدرجة</p>
                        <p class="text-lg md:text-xl font-bold text-purple-600">{{ $homework['average_score'] }}/10</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <!-- Circle Info -->
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="font-bold text-gray-900 text-sm md:text-base mb-3 md:mb-4">معلومات الحلقة</h3>
                <div class="space-y-2 md:space-y-3 text-xs md:text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاريخ البداية:</span>
                        <span class="font-medium text-gray-900">{{ $overall['started_at'] ? $overall['started_at']->format('Y-m-d') : 'لم تبدأ' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الجلسات المخططة:</span>
                        <span class="font-medium text-gray-900">{{ $overall['total_sessions_planned'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الجلسات المتبقية:</span>
                        <span class="font-medium text-gray-900">{{ $overall['sessions_remaining'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="font-bold text-gray-900 text-sm md:text-base mb-3 md:mb-4">إجراءات سريعة</h3>
                <div class="space-y-2">
                    <a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                       class="min-h-[44px] w-full flex items-center justify-center px-3 md:px-4 py-2 bg-gray-100 text-gray-700 text-xs md:text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-arrow-right-line ml-1.5 md:ml-2"></i>
                        عودة للحلقة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
