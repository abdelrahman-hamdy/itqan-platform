<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.session_reports.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.session_reports.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.session_reports.page_subtitle') }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-file-chart-line text-indigo-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $totalReports }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.session_reports.total_reports') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-check-line text-green-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $presentCount }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.session_reports.present_count') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-close-line text-red-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $absentCount }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.session_reports.absent_count') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-time-line text-amber-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $lateCount }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.session_reports.late_count') }}</p>
            </div>
        </div>
    </div>

    <!-- Reports List -->
    @if($reports->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.observation.student_info') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.observation.teacher') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.observation.status') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.common.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($reports as $report)
                            @php
                                $attendanceClass = match($report['attendance_status'] ?? '') {
                                    'present' => 'bg-green-100 text-green-700',
                                    'absent' => 'bg-red-100 text-red-700',
                                    'late' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $report['type'] === 'quran' ? 'bg-green-400' : 'bg-violet-400' }}"></span>
                                        <span class="text-sm text-gray-900">{{ $report['student_name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $report['teacher_name'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $attendanceClass }}">{{ $report['attendance_status'] ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $report['created_at']?->format('Y/m/d') ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-file-chart-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">{{ __('supervisor.common.no_data') }}</h3>
            <p class="text-sm text-gray-500">{{ __('supervisor.session_reports.page_subtitle') }}</p>
        </div>
    @endif
</div>

</x-layouts.supervisor>
