<x-layouts.teacher
    :title="__('teacher.report.circle_report_title', ['name' => $circle->name]) . ' - ' . config('app.name', __('teacher.panel.academy_default'))"
    :description="__('teacher.report.circle_report_description')">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.circles.group.breadcrumb'), 'route' => route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $circle->name, 'route' => route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]), 'truncate' => true],
            ['label' => __('teacher.report.full_report')],
        ]"
        view-type="teacher"
    />

    <!-- Header -->
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900">{{ __('teacher.report.full_report') }} - {{ $circle->name }}</h1>
                <p class="text-sm md:text-base text-gray-600 mt-0.5 md:mt-1">{{ __('teacher.report.student_count') }}: {{ $aggregate_stats['total_students'] }}</p>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] inline-flex items-center px-3 md:px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ms-1"></i>
                    {{ __('teacher.report.back_to_circle') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-6">
        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('teacher.report.total_students') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $aggregate_stats['total_students'] }}</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-group-line text-blue-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('teacher.progress.total_sessions') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $aggregate_stats['total_sessions'] }}</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-green-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('teacher.report.avg_attendance') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $aggregate_stats['average_attendance_rate'] }}%</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-star-line text-purple-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-600 truncate">{{ __('teacher.progress.average_performance') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5 md:mt-1">{{ $aggregate_stats['average_performance'] }}/10</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-star-line text-yellow-600 text-lg md:text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
        <h2 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('teacher.report.student_reports') }}</h2>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">{{ __('teacher.report.student_name') }}</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">{{ __('teacher.report.enrollment_date') }}</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">{{ __('teacher.progress.attendance_rate') }}</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">{{ __('teacher.report.performance') }}</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">{{ __('teacher.report.actions') }}</th>
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
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center ms-3">
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
                                <a href="{{ route('teacher.group-circles.student-report', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id, 'student' => $student->id]) }}"
                                   class="min-h-[36px] inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="ri-file-chart-line ms-1"></i>
                                    {{ __('teacher.report.view_details') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3">
            @foreach($students as $student)
                @php
                    $report = $student_reports[$student->id] ?? null;
                @endphp
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="ri-user-line text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $student->name }}</p>
                            <p class="text-xs text-gray-500">{{ $report && $report['enrollment']['enrolled_at'] ? $report['enrollment']['enrolled_at']->format('Y-m-d') : __('teacher.progress.not_specified') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            @if($report && $report['attendance']['total_sessions'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $report['attendance']['attendance_rate'] >= 80 ? 'bg-green-100 text-green-800' : ($report['attendance']['attendance_rate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ __('teacher.progress.attendance') }}: {{ $report['attendance']['attendance_rate'] }}%
                                </span>
                            @endif
                            @if($report && $report['progress']['average_overall_performance'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $report['progress']['average_overall_performance'] >= 7 ? 'bg-green-100 text-green-800' : ($report['progress']['average_overall_performance'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ __('teacher.report.performance') }}: {{ $report['progress']['average_overall_performance'] }}/10
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('teacher.group-circles.student-report', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id, 'student' => $student->id]) }}"
                       class="min-h-[44px] w-full inline-flex items-center justify-center px-3 py-2 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="ri-file-chart-line ms-1"></i>
                        {{ __('teacher.report.view_details') }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

</x-layouts.teacher>
