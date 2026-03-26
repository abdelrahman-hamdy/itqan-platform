<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = ($currentTeacherId ?? null) || ($currentMonth ?? null);
    $filterCount = (($currentTeacherId ?? null) ? 1 : 0) + (($currentMonth ?? null) ? 1 : 0);

    $methodLabels = [
        'individual_rate' => __('supervisor.teacher_earnings.summary_rate_per_session'),
        'group_rate' => __('supervisor.teacher_earnings.summary_rate_per_session'),
        'per_session' => __('supervisor.teacher_earnings.summary_rate_per_session'),
        'per_student' => __('supervisor.teacher_earnings.summary_rate_per_student'),
        'fixed_amount' => __('supervisor.teacher_earnings.summary_rate_fixed'),
    ];
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

    <!-- Summary Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.teacher_earnings.summary_list_title') }} ({{ count($teacherSummaries) }})
            </h2>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-indigo-500"></i>
                    {{ __('supervisor.teachers.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-indigo-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('manage.teacher-earnings.teacher-summary', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                        {{-- Teacher --}}
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teacher_earnings.all_teachers') }}</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t['id'] }}" {{ ($currentTeacherId ?? null) == $t['id'] ? 'selected' : '' }}>{{ $t['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Month --}}
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_month') }}</label>
                            <select name="month" id="month" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teacher_earnings.all_months') }}</option>
                                @foreach($availableMonths as $m)
                                    <option value="{{ $m['value'] }}" {{ ($currentMonth ?? null) === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.teachers.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.teacher-earnings.teacher-summary', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.teachers.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

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

                                @php
                                    $sourceColumns = [
                                        'quran_individual' => 'text-green-700',
                                        'quran_group' => 'text-emerald-700',
                                        'academic' => 'text-violet-700',
                                        'interactive' => 'text-blue-700',
                                    ];
                                @endphp
                                @foreach($sourceColumns as $sourceKey => $colorClass)
                                    <td class="px-3 py-4 text-center">
                                        @if($summary[$sourceKey]['amount'] > 0)
                                            <span class="text-sm font-medium {{ $colorClass }}">{{ number_format($summary[$sourceKey]['amount'], 2) }}</span>
                                            @foreach($summary[$sourceKey]['details'] as $detail)
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    {{ $detail['sessions_count'] }} × {{ number_format($detail['rate'] ?? 0, 2) }}
                                                    @if(count($summary[$sourceKey]['details']) > 1)
                                                        <span class="text-gray-300">({{ $methodLabels[$detail['method']] ?? $detail['method'] }})</span>
                                                    @endif
                                                </p>
                                            @endforeach
                                        @else
                                            <span class="text-sm text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endforeach

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
                                @php $totalQI = collect($teacherSummaries)->sum(fn($s) => $s['quran_individual']['amount']); @endphp
                                {{ $totalQI > 0 ? number_format($totalQI, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-emerald-700">
                                @php $totalQG = collect($teacherSummaries)->sum(fn($s) => $s['quran_group']['amount']); @endphp
                                {{ $totalQG > 0 ? number_format($totalQG, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-violet-700">
                                @php $totalAc = collect($teacherSummaries)->sum(fn($s) => $s['academic']['amount']); @endphp
                                {{ $totalAc > 0 ? number_format($totalAc, 2) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-blue-700">
                                @php $totalIC = collect($teacherSummaries)->sum(fn($s) => $s['interactive']['amount']); @endphp
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
