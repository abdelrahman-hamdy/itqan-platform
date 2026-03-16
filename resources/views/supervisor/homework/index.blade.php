<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('teacher_id') || request('student_id') || request('date_from') || request('date_to');
    $filterCount = (request('teacher_id') ? 1 : 0) + (request('student_id') ? 1 : 0) + (request('date_from') ? 1 : 0) + (request('date_to') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.homework.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.homework.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.homework.page_subtitle') }}</p>
    </div>

    <!-- Tabs -->
    <x-tabs id="homework-tabs" default-tab="quran" :url-sync="true">
        <x-slot name="tabs">
            <x-tabs.tab
                id="quran"
                :label="__('supervisor.homework.type_quran')"
                icon="ri-book-read-line"
                :badge="$quranStats['total']"
                badge-color="green"
            />
            <x-tabs.tab
                id="academic"
                :label="__('supervisor.homework.type_academic')"
                icon="ri-graduation-cap-line"
                :badge="$academicStats['total']"
                badge-color="violet"
            />
            <x-tabs.tab
                id="interactive"
                :label="__('supervisor.homework.type_interactive')"
                icon="ri-live-line"
                :badge="$interactiveStats['total']"
                badge-color="blue"
            />
        </x-slot>

        <x-slot name="panels">
            {{-- ==================== QURAN TAB ==================== --}}
            <x-tabs.panel id="quran" padding="p-0">
                <!-- Quran Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 p-4 md:p-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <i class="ri-book-2-line text-xl text-blue-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.total_assigned') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $quranStats['total'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-green-50 rounded-lg">
                                <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.evaluated') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $quranStats['evaluated'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-yellow-50 rounded-lg">
                                <i class="ri-time-line text-xl text-yellow-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.not_evaluated') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $quranStats['notEvaluated'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-purple-50 rounded-lg">
                                <i class="ri-bar-chart-line text-xl text-purple-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.avg_score') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">
                            {{ $quranStats['avgScore'] !== null ? $quranStats['avgScore'] . '/10' : '-' }}
                        </div>
                    </div>
                </div>

                <!-- Quran Table Card -->
                <div class="mx-4 md:mx-6 mb-6 bg-white rounded-xl shadow-sm border border-gray-200">
                    <!-- Filters -->
                    @include('supervisor.homework._filters', ['tabType' => 'quran'])

                    @if($quranHomework->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.student') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.homework_content') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.session_date') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.evaluation') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.scores') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($quranHomework as $hw)
                                        @php
                                            $session = $hw->session;
                                            $report = $session?->studentReport;
                                            $isEvaluated = $report && ($report->new_memorization_degree !== null || $report->reservation_degree !== null);
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('manage.homework.submissions', ['subdomain' => $subdomain, 'type' => 'quran', 'id' => $hw->id]) }}'">
                                            <td class="px-4 md:px-6 py-3">
                                                <div class="font-medium text-gray-900">{{ $session?->student?->name ?? '-' }}</div>
                                                <div class="text-xs text-gray-500 md:hidden">{{ $session?->scheduled_at?->format('Y-m-d') }}</div>
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                <div class="max-w-[250px] space-y-0.5">
                                                    @if($hw->has_new_memorization && $hw->new_memorization_range)
                                                        <div class="text-xs"><span class="font-medium text-green-700">{{ __('supervisor.homework.new_memorization') }}:</span> {{ $hw->new_memorization_range }}</div>
                                                    @endif
                                                    @if($hw->has_review && $hw->review_range)
                                                        <div class="text-xs"><span class="font-medium text-blue-700">{{ __('supervisor.homework.review') }}:</span> {{ $hw->review_range }}</div>
                                                    @endif
                                                    @if($hw->has_comprehensive_review && $hw->comprehensive_review_surahs_formatted)
                                                        <div class="text-xs"><span class="font-medium text-purple-700">{{ __('supervisor.homework.comprehensive_review') }}:</span> {{ $hw->comprehensive_review_surahs_formatted }}</div>
                                                    @endif
                                                    @if(!$hw->has_any_homework)
                                                        <span class="text-gray-400 text-xs">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">
                                                {{ $session?->scheduled_at?->format('Y-m-d') ?? '-' }}
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                @if($isEvaluated)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 whitespace-nowrap">{{ __('supervisor.homework.evaluated') }}</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700 whitespace-nowrap">{{ __('supervisor.homework.awaiting_evaluation') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                                @if($isEvaluated)
                                                    <div class="text-xs space-y-0.5">
                                                        @if($report->new_memorization_degree !== null)
                                                            <div>{{ __('supervisor.homework.memorization_degree') }}: <span class="font-semibold">{{ number_format($report->new_memorization_degree, 1) }}/10</span></div>
                                                        @endif
                                                        @if($report->reservation_degree !== null)
                                                            <div>{{ __('supervisor.homework.reservation_degree') }}: <span class="font-semibold">{{ number_format($report->reservation_degree, 1) }}/10</span></div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($quranHomework->hasPages())
                            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                                {{ $quranHomework->links() }}
                            </div>
                        @endif
                    @else
                        @include('supervisor.homework._empty_state')
                    @endif
                </div>
            </x-tabs.panel>

            {{-- ==================== ACADEMIC TAB ==================== --}}
            <x-tabs.panel id="academic" padding="p-0">
                <!-- Academic Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 p-4 md:p-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <i class="ri-book-2-line text-xl text-blue-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.total_assigned') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $academicStats['total'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-yellow-50 rounded-lg">
                                <i class="ri-time-line text-xl text-yellow-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.pending_submissions') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $academicStats['pending'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-green-50 rounded-lg">
                                <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.graded') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $academicStats['graded'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-red-50 rounded-lg">
                                <i class="ri-error-warning-line text-xl text-red-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.overdue') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $academicStats['overdue'] }}</div>
                    </div>
                </div>

                <!-- Academic Table Card -->
                <div class="mx-4 md:mx-6 mb-6 bg-white rounded-xl shadow-sm border border-gray-200">
                    @include('supervisor.homework._filters', ['tabType' => 'academic'])

                    @if($academicHomework->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.title_column') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.teacher') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.assigned_date') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.due_date') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.submission_progress') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.avg_score_label') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($academicHomework as $hw)
                                        @php
                                            $submittedCount = $hw->submissions->count();
                                            $gradedCount = $hw->submissions->filter(fn($s) => $s->score !== null)->count();
                                            $avgScore = $hw->submissions->filter(fn($s) => $s->score !== null)->avg('score');
                                            $isOverdue = $hw->due_date && $hw->due_date->isPast() && $gradedCount < $submittedCount;
                                            $status = $gradedCount > 0 ? 'graded' : ($isOverdue ? 'overdue' : 'pending');
                                            $statusBadges = [
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'graded' => 'bg-green-100 text-green-700',
                                                'overdue' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('manage.homework.submissions', ['subdomain' => $subdomain, 'type' => 'academic', 'id' => $hw->id]) }}'">
                                            <td class="px-4 md:px-6 py-3">
                                                <div class="font-medium text-gray-900 max-w-[200px] truncate">{{ $hw->title ?? __('supervisor.homework.academic_homework') . ' #' . $hw->id }}</div>
                                                <div class="text-xs text-gray-500 md:hidden">{{ $hw->teacher?->name }}</div>
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">{{ $hw->teacher?->name ?? '-' }}</td>
                                            <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">{{ ($hw->assigned_at ?? $hw->created_at)?->format('Y-m-d') ?? '-' }}</td>
                                            <td class="px-4 md:px-6 py-3 text-gray-600">
                                                @if($hw->due_date)
                                                    <span class="{{ $hw->due_date->isPast() ? 'text-red-600 font-medium' : '' }}">{{ $hw->due_date->format('Y-m-d') }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                <span class="whitespace-nowrap"><span class="text-gray-700 font-medium">{{ $gradedCount }}/{{ $submittedCount }}</span> <span class="text-xs text-gray-500">{{ __('supervisor.homework.submissions') }}</span></span>
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                                @if($avgScore !== null)
                                                    <span class="font-semibold">{{ number_format($avgScore, 1) }}/10</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap {{ $statusBadges[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                                    {{ __('supervisor.homework.status_' . $status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($academicHomework->hasPages())
                            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                                {{ $academicHomework->links() }}
                            </div>
                        @endif
                    @else
                        @include('supervisor.homework._empty_state')
                    @endif
                </div>
            </x-tabs.panel>

            {{-- ==================== INTERACTIVE TAB ==================== --}}
            <x-tabs.panel id="interactive" padding="p-0">
                <!-- Interactive Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 p-4 md:p-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <i class="ri-book-2-line text-xl text-blue-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.total_assigned') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $interactiveStats['total'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-yellow-50 rounded-lg">
                                <i class="ri-time-line text-xl text-yellow-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.pending_submissions') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $interactiveStats['pending'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-green-50 rounded-lg">
                                <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.graded') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $interactiveStats['graded'] }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 bg-red-50 rounded-lg">
                                <i class="ri-error-warning-line text-xl text-red-600"></i>
                            </div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.homework.overdue') }}</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $interactiveStats['overdue'] }}</div>
                    </div>
                </div>

                <!-- Interactive Table Card -->
                <div class="mx-4 md:mx-6 mb-6 bg-white rounded-xl shadow-sm border border-gray-200">
                    @include('supervisor.homework._filters', ['tabType' => 'interactive'])

                    @if($interactiveHomework->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.title_column') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.teacher') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.assigned_date') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.due_date') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.submission_progress') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.avg_score_label') }}</th>
                                        <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($interactiveHomework as $hw)
                                        @php
                                            $submittedCount = $hw->submissions->count();
                                            $gradedCount = $hw->submissions->filter(fn($s) => $s->score !== null)->count();
                                            $avgScore = $hw->submissions->filter(fn($s) => $s->score !== null)->avg('score');
                                            $isOverdue = $hw->due_date && $hw->due_date->isPast() && $gradedCount < $submittedCount;
                                            $status = $gradedCount > 0 ? 'graded' : ($isOverdue ? 'overdue' : 'pending');
                                            $statusBadges = [
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'graded' => 'bg-green-100 text-green-700',
                                                'overdue' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('manage.homework.submissions', ['subdomain' => $subdomain, 'type' => 'interactive', 'id' => $hw->id]) }}'">
                                            <td class="px-4 md:px-6 py-3">
                                                <div class="font-medium text-gray-900 max-w-[200px] truncate">{{ $hw->title ?? ($hw->session?->course?->title ?? __('supervisor.homework.interactive_homework')) . ' #' . $hw->id }}</div>
                                                <div class="text-xs text-gray-500 md:hidden">{{ $hw->teacher?->name }}</div>
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">{{ $hw->teacher?->name ?? '-' }}</td>
                                            <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">{{ $hw->created_at?->format('Y-m-d') ?? '-' }}</td>
                                            <td class="px-4 md:px-6 py-3 text-gray-600">
                                                @if($hw->due_date)
                                                    <span class="{{ $hw->due_date->isPast() ? 'text-red-600 font-medium' : '' }}">{{ $hw->due_date->format('Y-m-d') }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                <span class="whitespace-nowrap"><span class="text-gray-700 font-medium">{{ $gradedCount }}/{{ $submittedCount }}</span> <span class="text-xs text-gray-500">{{ __('supervisor.homework.submissions') }}</span></span>
                                            </td>
                                            <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                                @if($avgScore !== null)
                                                    <span class="font-semibold">{{ number_format($avgScore, 1) }}/10</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 md:px-6 py-3">
                                                <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap {{ $statusBadges[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                                    {{ __('supervisor.homework.status_' . $status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($interactiveHomework->hasPages())
                            <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                                {{ $interactiveHomework->links() }}
                            </div>
                        @endif
                    @else
                        @include('supervisor.homework._empty_state')
                    @endif
                </div>
            </x-tabs.panel>
        </x-slot>
    </x-tabs>
</div>

</x-layouts.supervisor>
