<x-layouts.teacher
    :title="'تقرير الحلقة الجماعية - ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الكامل للحلقة الجماعية'">

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
            <li class="text-gray-900">التقرير الكامل</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">التقرير الكامل - {{ $circle->name }}</h1>
                <p class="text-gray-600 mt-1">عدد الطلاب: {{ $aggregate_stats['total_students'] }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
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
                    <p class="text-sm text-gray-600">إجمالي الطلاب</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $aggregate_stats['total_students'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-group-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إجمالي الجلسات</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $aggregate_stats['total_sessions'] }}</p>
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
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $aggregate_stats['average_attendance_rate'] }}%</p>
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
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $aggregate_stats['average_performance'] }}/10</p>
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
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">الواجبات</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        @php
                            $report = $student_reports[$student->id] ?? null;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center ml-3">
                                        <i class="ri-user-line text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $student->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4 text-sm text-gray-600">
                                {{ $report && $report['enrollment']['enrolled_at'] ? $report['enrollment']['enrolled_at']->format('Y-m-d') : '-' }}
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($report && $report['attendance']['total_sessions'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $report['attendance']['attendance_rate'] >= 80 ? 'bg-green-100 text-green-800' : ($report['attendance']['attendance_rate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $report['attendance']['attendance_rate'] }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($report && $report['progress']['average_overall_performance'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $report['progress']['average_overall_performance'] >= 7 ? 'bg-green-100 text-green-800' : ($report['progress']['average_overall_performance'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $report['progress']['average_overall_performance'] }}/10
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($report && $report['homework']['total_assigned'] > 0)
                                    <span class="text-sm text-gray-900">{{ $report['homework']['completion_rate'] }}%</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                <a href="{{ route('teacher.group-circles.student-report', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id, 'student' => $student->id]) }}"
                                   class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="ri-file-chart-line ml-1"></i>
                                    عرض التفاصيل
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

</x-layouts.teacher>
