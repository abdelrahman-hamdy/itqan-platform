<x-layouts.teacher
    :title="'تقرير الطالب - ' . $student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير التفصيلي للطالب في الحلقة الجماعية'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الحلقات الجماعية</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" class="hover:text-primary">{{ $circle->name }}</a></li>
            <li>/</li>
            <li class="text-gray-900">تقرير {{ $student->name }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تقرير الطالب: {{ $student->name }}</h1>
                <p class="text-gray-600 mt-1">الحلقة: {{ $circle->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.group-circles.report', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ml-1"></i>
                    عودة لتقرير الحلقة
                </a>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إجمالي الجلسات</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $attendance['total_sessions'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-line text-blue-600 text-2xl"></i>
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
                    <p class="text-sm text-gray-600">متوسط الأداء</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['average_overall_performance'] ?? 0 }}/10</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-star-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إنجاز الواجبات</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $homework['completion_rate'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-task-line text-yellow-600 text-2xl"></i>
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
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">حضر</p>
                        <p class="text-xl font-bold text-green-600">{{ $attendance['attended'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">غاب</p>
                        <p class="text-xl font-bold text-red-600">{{ $attendance['absent'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">متأخر</p>
                        <p class="text-xl font-bold text-yellow-600">{{ $attendance['late'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">الأداء الأكاديمي</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">متوسط الحفظ الجديد</p>
                        <p class="text-xl font-bold text-green-600">{{ $progress['average_memorization_degree'] }}/10</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">متوسط المراجعة</p>
                        <p class="text-xl font-bold text-blue-600">{{ $progress['average_reservation_degree'] }}/10</p>
                    </div>
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
            <!-- Enrollment Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">معلومات الانضمام</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاريخ الانضمام:</span>
                        <span class="font-medium text-gray-900">{{ $enrollment['enrolled_at'] ? $enrollment['enrolled_at']->format('Y-m-d') : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الحالة:</span>
                        <span class="font-medium text-gray-900">{{ $enrollment['status'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
