<x-layouts.student
    :title="'تقريري في الحلقة - ' . config('app.name', 'منصة إتقان')"
    :description="'تقريري الشامل في حلقة القرآن'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">لوحة التحكم</a></li>
            <li>/</li>
            @if($circleType === 'individual')
                <li><a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" class="hover:text-primary">حلقتي الفردية</a></li>
            @else
                <li><a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" class="hover:text-primary">{{ $circle->name }}</a></li>
            @endif
            <li>/</li>
            <li class="text-gray-900">تقريري</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-8 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">تقريري في الحلقة</h1>
                <p class="mt-2 text-blue-100">
                    @if($circleType === 'individual')
                        حلقة فردية
                    @else
                        الحلقة: {{ $circle->name }}
                    @endif
                </p>
            </div>
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                <i class="ri-trophy-line text-5xl"></i>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إجمالي الجلسات</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $attendance['total_sessions'] }}</p>
                </div>
                <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-check-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة الحضور</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $attendance['attendance_rate'] }}%</p>
                </div>
                <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-star-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        @if($circleType === 'individual')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الصفحات المحفوظة</p>
                    <p class="text-3xl font-bold text-purple-600 mt-1">{{ number_format($progress['pages_memorized'] ?? 0, 1) }}</p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-book-open-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة التقدم</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $progress['progress_percentage'] ?? 0 }}%</p>
                </div>
                <div class="w-14 h-14 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-pie-chart-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">متوسط الأداء</p>
                    <p class="text-3xl font-bold text-purple-600 mt-1">{{ $progress['average_overall_performance'] ?? 0 }}/10</p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-star-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إنجاز الواجبات</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $homework['completion_rate'] ?? 0 }}%</p>
                </div>
                <div class="w-14 h-14 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-task-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Attendance Card -->
            <x-reports.attendance-card
                :attendance="$attendance"
                title="إحصائيات حضوري" />

            <!-- Progress Card -->
            <x-reports.progress-card
                :progress="$progress"
                title="تقدمي في الحفظ" />

            <!-- Performance Card -->
            <x-reports.performance-card
                :performance="$progress"
                title="أدائي الأكاديمي" />

            <!-- Homework Section -->
            @if(isset($homework) && $homework['total_assigned'] > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="ri-task-line text-yellow-600 ml-2"></i>
                    واجباتي
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">إجمالي الواجبات</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $homework['total_assigned'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">واجبات مكتملة</p>
                        <p class="text-2xl font-bold text-green-600">{{ $homework['completed'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">نسبة الإنجاز</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $homework['completion_rate'] }}%</p>
                    </div>
                    @if($homework['average_score'] > 0)
                    <div>
                        <p class="text-sm text-gray-600">متوسط درجاتي</p>
                        <p class="text-2xl font-bold text-purple-600">{{ $homework['average_score'] }}/10</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            @if($circleType === 'individual' && isset($overall))
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
                        <span class="font-medium text-gray-900">{{ $overall['total_sessions_planned'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الجلسات المتبقية:</span>
                        <span class="font-medium text-gray-900">{{ $overall['sessions_remaining'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            @elseif(isset($enrollment))
            <!-- Enrollment Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">معلومات انضمامي</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاريخ الانضمام:</span>
                        <span class="font-medium text-gray-900">{{ $enrollment['enrolled_at'] ? $enrollment['enrolled_at']->format('Y-m-d') : '-' }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Motivational Card -->
            <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="ri-trophy-line text-5xl mb-3"></i>
                    <h3 class="font-bold text-lg mb-2">بارك الله فيك!</h3>
                    @if($attendance['attendance_rate'] >= 80)
                        <p class="text-sm text-green-100">حضورك ممتاز، استمر في التقدم!</p>
                    @elseif($attendance['attendance_rate'] >= 60)
                        <p class="text-sm text-green-100">حضورك جيد، يمكنك التحسين!</p>
                    @else
                        <p class="text-sm text-green-100">احرص على الحضور بانتظام!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.student>
