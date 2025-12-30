@props([
    'course' => null,
    'attendance' => null,
    'performance' => null,
    'progress' => null,
    'studentRows' => null,
    'statsCards' => null,
])

@php
$academySubdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

// Build breadcrumbs
$breadcrumbs = [
    [
        'label' => auth()->user()->name,
        'url' => route('teacher.profile', ['subdomain' => $academySubdomain])
    ],
    [
        'label' => $course?->title,
        'url' => route('interactive-courses.show', ['subdomain' => $academySubdomain, 'courseId' => $course?->id])
    ],
    ['label' => 'التقرير الشامل']
];

// Header stats
$headerStats = [
    [
        'icon' => 'ri-group-line',
        'label' => 'عدد الطلاب',
        'value' => $progress['enrolled_students'] ?? 0
    ],
];
@endphp

<x-reports.layouts.base-report
    :title="'تقرير الكورس - ' . $course?->title . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الشامل للكورس التفاعلي'"
    layoutType="teacher">

<div>
    <!-- Report Header with Breadcrumbs -->
    <x-reports.report-header
        :title="'التقرير الشامل - ' . $course?->title"
        subtitle="إحصائيات شاملة لجميع الطلاب"
        :breadcrumbs="$breadcrumbs"
        :stats="$headerStats" />

    <!-- Stats Grid -->
    @if(isset($statsCards))
        <x-reports.stats-grid :stats="$statsCards" />
    @endif

    <!-- Students List Table -->
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
                    @forelse($studentRows as $row)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center ms-3">
                                        <i class="ri-user-line text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $row->studentName ?? 'غير معروف' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4 text-sm text-gray-600">
                                {{ $row->enrollmentDate ?? '-' }}
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($row->attendanceRate > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $row->attendanceRate >= 80 ? 'bg-green-100 text-green-800' : ($row->attendanceRate >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $row->attendanceRate }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                @if($row->performanceScore > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $row->performanceScore >= 7 ? 'bg-green-100 text-green-800' : ($row->performanceScore >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($row->performanceScore, 1) }}/10
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-center py-3 px-4">
                                <a href="{{ $row->detailUrl }}"
                                   class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="ri-file-chart-line ms-1"></i>
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

</x-reports.layouts.base-report>
