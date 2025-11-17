<x-layouts.teacher
    :title="'تقرير الحلقة الفردية - ' . $student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الكامل للحلقة الفردية'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الحلقات الفردية</a></li>
            <li>/</li>
            <li><a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" class="hover:text-primary">{{ $student->name }}</a></li>
            <li>/</li>
            <li class="text-gray-900">التقرير الكامل</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">التقرير الكامل</h1>
                <p class="text-gray-600 mt-1">الطالب: {{ $student->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ml-1"></i>
                    عودة للحلقة
                </a>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الجلسات المكتملة</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $overall['sessions_completed'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-checkbox-circle-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة الحضور</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $attendance['attendance_rate'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-star-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الأوجه المحفوظة</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['papers_memorized'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-book-open-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة التقدم</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $overall['progress_percentage'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-pie-chart-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Attendance Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">إحصائيات الحضور</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="border-r border-gray-200 pr-4">
                        <p class="text-sm text-gray-600">إجمالي الجلسات</p>
                        <p class="text-xl font-bold text-gray-900">{{ $attendance['total_sessions'] }}</p>
                    </div>
                    <div class="pr-4">
                        <p class="text-sm text-gray-600">حضر</p>
                        <p class="text-xl font-bold text-green-600">{{ $attendance['attended'] }}</p>
                    </div>
                    <div class="border-r border-gray-200 pr-4">
                        <p class="text-sm text-gray-600">غاب</p>
                        <p class="text-xl font-bold text-red-600">{{ $attendance['absent'] }}</p>
                    </div>
                    <div class="pr-4">
                        <p class="text-sm text-gray-600">متأخر</p>
                        <p class="text-xl font-bold text-yellow-600">{{ $attendance['late'] }}</p>
                    </div>
                    <div class="border-r border-gray-200 pr-4">
                        <p class="text-sm text-gray-600">متوسط المدة</p>
                        <p class="text-xl font-bold text-gray-900">{{ $attendance['average_duration_minutes'] }} د</p>
                    </div>
                    <div class="pr-4">
                        <p class="text-sm text-gray-600">نسبة الانضباط</p>
                        <p class="text-xl font-bold text-blue-600">{{ $attendance['punctuality_rate'] }}%</p>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">التقدم في الحفظ</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">الموضع الحالي</p>
                        <p class="text-lg font-bold text-gray-900">{{ $progress['current_position'] }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">الآيات المحفوظة</p>
                            <p class="text-xl font-bold text-gray-900">{{ $progress['verses_memorized'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">متوسط الحفظ الجديد</p>
                            <p class="text-xl font-bold text-green-600">{{ $progress['average_memorization_degree'] }}/10</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">متوسط المراجعة</p>
                            <p class="text-xl font-bold text-blue-600">{{ $progress['average_reservation_degree'] }}/10</p>
                        </div>
                    </div>
                    @if($progress['average_overall_performance'] > 0)
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <p class="text-sm text-purple-600">الأداء العام</p>
                        <p class="text-2xl font-bold text-purple-900">{{ $progress['average_overall_performance'] }}/10</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Homework Section -->
            @if($homework['total_assigned'] > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">الواجبات</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">إجمالي الواجبات</p>
                        <p class="text-xl font-bold text-gray-900">{{ $homework['total_assigned'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">مكتمل</p>
                        <p class="text-xl font-bold text-green-600">{{ $homework['completed'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">نسبة الإنجاز</p>
                        <p class="text-xl font-bold text-blue-600">{{ $homework['completion_rate'] }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">متوسط الدرجة</p>
                        <p class="text-xl font-bold text-purple-600">{{ $homework['average_score'] }}/10</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">معلومات الحلقة</h3>
                <div class="space-y-3 text-sm">
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
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                <div class="space-y-2">
                    <a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-arrow-right-line ml-2"></i>
                        عودة للحلقة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
