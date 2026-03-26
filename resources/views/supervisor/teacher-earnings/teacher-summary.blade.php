<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.teachers.page_title'), 'url' => route('manage.teachers.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.teacher_earnings.page_title')],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.teacher_earnings.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teacher_earnings.summary_page_subtitle') }}</p>
    </div>

    @include('supervisor.teacher-earnings.partials.tab-navigation', ['activeTab' => $activeTab ?? 'summary', 'subdomain' => $subdomain])

    <!-- Month Filter -->
    <div class="mb-6">
        <form method="GET" action="{{ route('manage.teacher-earnings.teacher-summary', ['subdomain' => $subdomain]) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_month') }}</label>
                <select name="month" id="month" onchange="this.form.submit()" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($availableMonths as $m)
                        <option value="{{ $m['value'] }}" {{ $currentMonth === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Summary Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if(count($teacherSummaries) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_teacher_name') }}
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    {{ __('supervisor.teacher_earnings.summary_quran_individual') }}
                                </span>
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    {{ __('supervisor.teacher_earnings.summary_quran_group') }}
                                </span>
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                                    {{ __('supervisor.teacher_earnings.summary_academic') }}
                                </span>
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    {{ __('supervisor.teacher_earnings.summary_interactive') }}
                                </span>
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_sessions_count') }}
                            </th>
                            <th scope="col" class="px-4 md:px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_total') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($teacherSummaries as $summary)
                            @php
                                $profileKey = $summary['teacher_type'] . '_' . $summary['teacher_id'];
                                $teacherUser = $profileUserMap[$profileKey] ?? null;
                                $teacherName = $teacherUser?->name ?? __('common.unknown');
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        @if($teacherUser)
                                            <x-avatar :user="$teacherUser" size="sm" />
                                        @else
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="ri-user-line text-gray-500 text-sm"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $teacherName }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $summary['teacher_type'] === 'quran_teacher' ? __('supervisor.teacher_earnings.source_quran') : __('supervisor.teacher_earnings.source_academic') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    @if($summary['quran_individual'] > 0)
                                        <span class="text-sm font-medium text-green-700">{{ number_format($summary['quran_individual'], 2) }}</span>
                                    @else
                                        <span class="text-sm text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    @if($summary['quran_group'] > 0)
                                        <span class="text-sm font-medium text-emerald-700">{{ number_format($summary['quran_group'], 2) }}</span>
                                    @else
                                        <span class="text-sm text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    @if($summary['academic'] > 0)
                                        <span class="text-sm font-medium text-violet-700">{{ number_format($summary['academic'], 2) }}</span>
                                    @else
                                        <span class="text-sm text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    @if($summary['interactive'] > 0)
                                        <span class="text-sm font-medium text-blue-700">{{ number_format($summary['interactive'], 2) }}</span>
                                    @else
                                        <span class="text-sm text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ $summary['sessions_count'] }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($summary['total'], 2) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    {{-- Totals Footer --}}
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-4 md:px-6 py-3 text-sm font-bold text-gray-900">{{ __('supervisor.teacher_earnings.summary_total') }}</td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-green-700">
                                @php $totalQI = collect($teacherSummaries)->sum('quran_individual'); @endphp
                                {{ $totalQI > 0 ? number_format($totalQI, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-emerald-700">
                                @php $totalQG = collect($teacherSummaries)->sum('quran_group'); @endphp
                                {{ $totalQG > 0 ? number_format($totalQG, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-violet-700">
                                @php $totalAc = collect($teacherSummaries)->sum('academic'); @endphp
                                {{ $totalAc > 0 ? number_format($totalAc, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-blue-700">
                                @php $totalIC = collect($teacherSummaries)->sum('interactive'); @endphp
                                {{ $totalIC > 0 ? number_format($totalIC, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-gray-700">
                                {{ collect($teacherSummaries)->sum('sessions_count') }}
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center text-sm font-bold text-gray-900">
                                {{ number_format(collect($teacherSummaries)->sum('total'), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-money-dollar-circle-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teacher_earnings.summary_no_results') }}</h3>
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
