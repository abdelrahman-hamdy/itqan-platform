<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $earningsCurrencySymbol = getTeacherEarningsCurrencySymbol();

    $hasActiveFilters = ($currentTeacherId ?? null) || ($currentMonth ?? null) || ($startDate ?? null) || ($endDate ?? null) || ($currentTeacherType ?? null) || ($currentGender ?? null);
    $filterCount = (($currentTeacherId ?? null) ? 1 : 0) + (($currentMonth ?? null) ? 1 : 0) + (($startDate ?? null) || ($endDate ?? null) ? 1 : 0) + (($currentTeacherType ?? null) ? 1 : 0) + (($currentGender ?? null) ? 1 : 0);
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
    <div class="mb-6 md:mb-8 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.teacher_earnings.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teacher_earnings.summary_page_subtitle') }}</p>
        </div>
        <button type="button" onclick="document.getElementById('export-modal').classList.remove('hidden')"
            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
            <i class="ri-download-line"></i>
            {{ __('supervisor.teacher_earnings.export_button') }}
        </button>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
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

                        {{-- Teacher Type --}}
                        <div>
                            <label for="teacher_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_teacher_type') }}</label>
                            <select name="teacher_type" id="teacher_type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teacher_earnings.all_types') }}</option>
                                <option value="quran" {{ ($currentTeacherType ?? null) === 'quran' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.source_quran') }}</option>
                                <option value="academic" {{ ($currentTeacherType ?? null) === 'academic' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.source_academic') }}</option>
                            </select>
                        </div>

                        {{-- Gender --}}
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_gender') }}</label>
                            <select name="gender" id="gender" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teacher_earnings.all_genders') }}</option>
                                <option value="male" {{ ($currentGender ?? null) === 'male' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.gender_male') }}</option>
                                <option value="female" {{ ($currentGender ?? null) === 'female' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.gender_female') }}</option>
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

                        {{-- Start Date --}}
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_start_date') }}</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_end_date') }}</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ __('supervisor.teacher_earnings.date_range_hint') }}</p>
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
            @php
                $summaryCollection = collect($teacherSummaries);
                $footerTotals = [
                    'quran_individual' => $summaryCollection->sum(fn($s) => $s['quran_individual']['amount']),
                    'quran_group' => $summaryCollection->sum(fn($s) => $s['quran_group']['amount']),
                    'academic' => $summaryCollection->sum(fn($s) => $s['academic']['amount']),
                    'interactive' => $summaryCollection->sum(fn($s) => $s['interactive']['amount']),
                    'sessions' => $summaryCollection->sum('sessions_count'),
                    'hours' => round($summaryCollection->sum('total_duration_minutes') / 60, 1),
                    'total' => $summaryCollection->sum('total'),
                ];

                $sourceLabels = [
                    'quran_individual' => ['label' => __('supervisor.teacher_earnings.summary_quran_individual'), 'color' => 'green', 'text' => 'text-green-700', 'bg' => 'bg-green-500'],
                    'quran_group' => ['label' => __('supervisor.teacher_earnings.summary_quran_group'), 'color' => 'emerald', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-500'],
                    'academic' => ['label' => __('supervisor.teacher_earnings.summary_academic'), 'color' => 'violet', 'text' => 'text-violet-700', 'bg' => 'bg-violet-500'],
                    'interactive' => ['label' => __('supervisor.teacher_earnings.summary_interactive'), 'color' => 'blue', 'text' => 'text-blue-700', 'bg' => 'bg-blue-500'],
                ];
            @endphp

            {{-- Mobile: Card Layout --}}
            <div class="md:hidden divide-y divide-gray-200">
                @foreach($teacherSummaries as $summary)
                    @php
                        $profileKey = $summary['teacher_type'] . '_' . $summary['teacher_id'];
                        $teacherUser = $profileUserMap[$profileKey] ?? null;
                        $teacherName = $teacherUser?->name ?? __('common.unknown');
                    @endphp
                    <div class="px-4 py-4">
                        {{-- Teacher header row --}}
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
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
                            <div class="text-end flex-shrink-0">
                                <p class="text-sm font-bold text-gray-900">{{ number_format($summary['total'], 2) }} {{ $earningsCurrencySymbol }}</p>
                            </div>
                        </div>

                        {{-- Stats row --}}
                        <div class="flex items-center gap-4 text-xs text-gray-500 mb-2">
                            <span class="flex items-center gap-1">
                                <i class="ri-calendar-check-line text-gray-400"></i>
                                {{ $summary['sessions_count'] }} {{ __('supervisor.teacher_earnings.summary_sessions_count') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <i class="ri-time-line text-gray-400"></i>
                                {{ round($summary['total_duration_minutes'] / 60, 1) }} {{ __('supervisor.teacher_earnings.hours_unit') }}
                            </span>
                        </div>

                        {{-- Source breakdown --}}
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($sourceLabels as $sourceKey => $sourceMeta)
                                @if($summary[$sourceKey]['amount'] > 0)
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 rounded-full {{ $sourceMeta['bg'] }} flex-shrink-0"></span>
                                        <span class="text-gray-500">{{ $sourceMeta['label'] }}:</span>
                                        <span class="font-medium {{ $sourceMeta['text'] }}">{{ number_format($summary[$sourceKey]['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Mobile totals --}}
                <div class="px-4 py-3 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900">{{ __('supervisor.teacher_earnings.summary_total') }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($footerTotals['total'], 2) }} {{ $earningsCurrencySymbol }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500 mt-1">
                        <span>{{ $footerTotals['sessions'] }} {{ __('supervisor.teacher_earnings.summary_sessions_count') }}</span>
                        <span>{{ $footerTotals['hours'] }} {{ __('supervisor.teacher_earnings.hours_unit') }}</span>
                    </div>
                </div>
            </div>

            {{-- Desktop: Full Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_teacher_name') }}
                            </th>
                            @foreach($sourceLabels as $sourceKey => $sourceMeta)
                                <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full {{ $sourceMeta['bg'] }}"></span>
                                        {{ $sourceMeta['label'] }}
                                    </span>
                                </th>
                            @endforeach
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_sessions_count') }}
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('supervisor.teacher_earnings.summary_total_hours') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
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
                                <td class="px-6 py-4 whitespace-nowrap">
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

                                @foreach($sourceLabels as $sourceKey => $sourceMeta)
                                    <td class="px-3 py-4 text-center">
                                        @if($summary[$sourceKey]['amount'] > 0)
                                            <span class="text-sm font-medium {{ $sourceMeta['text'] }}">{{ number_format($summary[$sourceKey]['amount'], 2) }} {{ $earningsCurrencySymbol }}</span>
                                            @foreach($summary[$sourceKey]['details'] as $detail)
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    {{ $detail['sessions_count'] }} × {{ $detail['duration_minutes'] }} {{ __('supervisor.teacher_earnings.minutes_short') }} × {{ number_format($detail['rate_per_session'], 2) }} {{ $earningsCurrencySymbol }}
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
                                <td class="px-3 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm text-gray-600">{{ round($summary['total_duration_minutes'] / 60, 1) }} {{ __('supervisor.teacher_earnings.hours_unit') }}</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($summary['total'], 2) }} {{ $earningsCurrencySymbol }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">{{ __('supervisor.teacher_earnings.summary_total') }}</td>
                            @foreach($sourceLabels as $sourceKey => $sourceMeta)
                                <td class="px-3 py-3 text-center text-sm font-bold {{ $sourceMeta['text'] }}">
                                    {{ $footerTotals[$sourceKey] > 0 ? number_format($footerTotals[$sourceKey], 2) . ' ' . $earningsCurrencySymbol : '-' }}
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-center text-sm font-bold text-gray-700">
                                {{ $footerTotals['sessions'] }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm font-bold text-gray-700">
                                {{ $footerTotals['hours'] }} {{ __('supervisor.teacher_earnings.hours_unit') }}
                            </td>
                            <td class="px-6 py-3 text-center text-sm font-bold text-gray-900">
                                {{ number_format($footerTotals['total'], 2) }} {{ $earningsCurrencySymbol }}
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

{{-- Export Modal --}}
<div id="export-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('export-modal').classList.add('hidden')"></div>
    <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
        <div class="relative bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
            <div class="p-6 pb-4 pt-8 md:pt-6">
                <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-green-100 mb-4">
                    <i class="ri-file-download-line text-2xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-bold text-center text-gray-900 mb-4">{{ __('supervisor.teacher_earnings.export_title') }}</h3>

                <form method="POST" action="{{ route('manage.teacher-earnings.export', ['subdomain' => $subdomain]) }}">
                    @csrf

                    <input type="hidden" name="export_type" value="summary">

                    {{-- Pass current filter state --}}
                    <input type="hidden" name="month" value="{{ $currentMonth ?? '' }}">
                    <input type="hidden" name="teacher_id" value="{{ $currentTeacherId ?? '' }}">
                    <input type="hidden" name="teacher_type" value="{{ $currentTeacherType ?? '' }}">
                    <input type="hidden" name="gender" value="{{ $currentGender ?? '' }}">
                    <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">

                    {{-- Active filters summary --}}
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                        <p class="font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.export_current_filters') }}:</p>
                        <ul class="space-y-1 text-xs">
                            <li>
                                <span class="text-gray-500">{{ __('supervisor.teacher_earnings.filter_teacher') }}:</span>
                                @if($currentTeacherId ?? null)
                                    @php $selectedTeacher = collect($teachers)->firstWhere('id', $currentTeacherId); @endphp
                                    <span class="font-medium">{{ $selectedTeacher['name'] ?? $currentTeacherId }}</span>
                                @else
                                    <span>{{ __('supervisor.teacher_earnings.export_all_teachers') }}</span>
                                @endif
                            </li>
                            <li>
                                <span class="text-gray-500">{{ __('supervisor.teacher_earnings.export_period_label') }}:</span>
                                @if(($startDate ?? null) || ($endDate ?? null))
                                    <span class="font-medium">{{ $startDate ?? '...' }} - {{ $endDate ?? '...' }}</span>
                                @elseif($currentMonth ?? null)
                                    @php
                                        $selectedMonth = collect($availableMonths)->firstWhere('value', $currentMonth);
                                    @endphp
                                    <span class="font-medium">{{ $selectedMonth['label'] ?? $currentMonth }}</span>
                                @else
                                    <span>{{ __('supervisor.teacher_earnings.export_all_periods') }}</span>
                                @endif
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                        <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')"
                            class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-colors">
                            {{ __('common.actions.cancel') }}
                        </button>
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors inline-flex items-center justify-center gap-2">
                            <i class="ri-download-line"></i>
                            {{ __('supervisor.teacher_earnings.export_download') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
