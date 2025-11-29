<x-layouts.teacher
    :title="'تقرير الكورس - ' . $course->title . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الشامل للكورس التفاعلي'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $course->id]) }}" class="hover:text-primary">{{ $course->title }}</a></li>
            <li>/</li>
            <li class="text-gray-900">التقرير الشامل</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">التقرير الشامل - {{ $course->title }}</h1>
                <p class="text-gray-600 mt-1">عدد الطلاب: {{ $progress['enrolled_students'] ?? 0 }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('my.interactive-course.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $course->id]) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ml-1"></i>
                    عودة للكورس
                </a>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الطلاب المسجلين</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['enrolled_students'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-group-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الجلسات المكتملة</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['sessions_completed'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-check-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">متوسط نسبة الحضور</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $attendance['attendance_rate'] ?? 0 }}%</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-star-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">متوسط الأداء</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($performance['average_overall_performance'] ?? 0, 1) }}/10</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-star-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">تقارير الطلاب</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">اسم الطالب</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">تاريخ الانضمام</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">نسبة الحضور</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">الأداء</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($course->enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $studentUser = $student?->user;

                            // Calculate student-specific metrics from session reports
                            $studentReports = $course->sessions->flatMap(function($session) use ($student) {
                                return $session->studentReports->where('student_id', $student?->id);
                            });

                            $completedSessions = $course->sessions->filter(function($s) {
                                $status = is_object($s->status) ? $s->status->value : $s->status;
                                return $status === 'completed';
                            })->count();

                            $attendedSessions = $studentReports->filter(function($r) {
                                $status = is_object($r->attendance_status) ? $r->attendance_status->value : $r->attendance_status;
                                return in_array($status, ['present', 'late', 'partial']);
                            })->count();

                            $attendanceRate = $completedSessions > 0 ? round(($attendedSessions / $completedSessions) * 100) : 0;
                            $avgPerformance = $studentReports->whereNotNull('homework_degree')->avg('homework_degree') ?? 0;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center ml-3">
                                        <i class="ri-user-line text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $studentUser?->name ?? 'غير معروف' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4 text-sm text-gray-600">
                                {{ $enrollment->created_at?->format('Y-m-d') ?? '-' }}
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($completedSessions > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attendanceRate >= 80 ? 'bg-green-100 text-green-800' : ($attendanceRate >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $attendanceRate }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($avgPerformance > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $avgPerformance >= 7 ? 'bg-green-100 text-green-800' : ($avgPerformance >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($avgPerformance, 1) }}/10
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                <a href="{{ route('teacher.interactive-courses.student-report', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $course->id, 'student' => $studentUser?->id]) }}"
                                   class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="ri-file-chart-line ml-1"></i>
                                    عرض التفاصيل
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">
                                <i class="ri-user-line text-4xl mb-2"></i>
                                <p>لا يوجد طلاب مسجلين في هذا الكورس</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</x-layouts.teacher>
