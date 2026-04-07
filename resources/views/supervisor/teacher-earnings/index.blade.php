<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = !empty($currentTeacherIds) || $currentMonth || ($currentStatus && $currentStatus !== 'all') || ($startDate ?? null) || ($endDate ?? null);

    $filterCount = (!empty($currentTeacherIds) ? 1 : 0)
        + ($currentMonth ? 1 : 0)
        + ($currentStatus && $currentStatus !== 'all' ? 1 : 0)
        + (($startDate ?? null) || ($endDate ?? null) ? 1 : 0);
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
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teacher_earnings.page_subtitle') }}</p>
    </div>

    @include('supervisor.teacher-earnings.partials.tab-navigation', ['activeTab' => $activeTab ?? 'details', 'subdomain' => $subdomain])

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        {{-- Total Earnings This Month --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-money-dollar-circle-line text-blue-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['totalEarningsThisMonth'], 2) }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('supervisor.teacher_earnings.total_earnings') }}</p>
                </div>
            </div>
        </div>

        {{-- Finalized --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['finalizedAmount'], 2) }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('supervisor.teacher_earnings.finalized_earnings') }}</p>
                </div>
            </div>
        </div>

        {{-- Disputed --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-error-warning-line text-red-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['disputedAmount'], 2) }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('supervisor.teacher_earnings.status_disputed') }}</p>
                </div>
            </div>
        </div>

        {{-- Sessions Count --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-indigo-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ $stats['sessionsCount'] }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('supervisor.teacher_earnings.sessions_count') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.teacher_earnings.list_title') }} ({{ $earnings->total() }})
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
                <form method="GET" action="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        {{-- Teacher (Multi-select) --}}
                        <div>
                            <x-ui.multi-select
                                name="teacher_ids"
                                :options="$teachers"
                                :selected="$currentTeacherIds ?? []"
                                :placeholder="__('supervisor.teacher_earnings.all_teachers')"
                                :label="__('supervisor.teacher_earnings.filter_teacher')"
                            />
                        </div>

                        {{-- Month --}}
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_month') }}</label>
                            <select name="month" id="month" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teacher_earnings.all_months') }}</option>
                                @foreach($availableMonths as $m)
                                    <option value="{{ $m['value'] }}" {{ $currentMonth === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.all_statuses') }}</option>
                                <option value="finalized" {{ $currentStatus === 'finalized' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.status_finalized') }}</option>
                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.status_pending') }}</option>
                                <option value="disputed" {{ $currentStatus === 'disputed' ? 'selected' : '' }}>{{ __('supervisor.teacher_earnings.status_disputed') }}</option>
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
                            <a href="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.teachers.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Earnings Items -->
        @if($earnings->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($earnings as $earning)
                    @php
                        // Resolve teacher user from profile map
                        $profileKey = $earning->teacher_type . '_' . $earning->teacher_id;
                        $teacherUser = $profileUserMap[$profileKey] ?? null;
                        $teacherName = $teacherUser?->name ?? $earning->teacher_name;

                        // Determine source type and label
                        $session = $earning->session;
                        $sessionType = match ($earning->session_type) {
                            \App\Models\QuranSession::class, 'quran_session' => 'quran',
                            \App\Models\AcademicSession::class, 'academic_session' => 'academic',
                            \App\Models\InteractiveCourseSession::class, 'interactive_course_session' => 'interactive',
                            default => 'other',
                        };

                        // Quran: distinguish individual vs group
                        if ($sessionType === 'quran' && $session) {
                            $isIndividualQuran = $session->session_type === 'individual';
                            $sourceLabel = $isIndividualQuran
                                ? __('supervisor.teacher_earnings.source_quran_individual')
                                : __('supervisor.teacher_earnings.source_quran_group');
                        } else {
                            $sourceLabel = match ($sessionType) {
                                'academic' => __('supervisor.teacher_earnings.source_academic'),
                                'interactive' => __('supervisor.teacher_earnings.source_interactive'),
                                default => __('supervisor.teacher_earnings.source_other'),
                            };
                        }

                        $sourceBadgeClass = match ($sessionType) {
                            'quran' => 'bg-green-100 text-green-700',
                            'academic' => 'bg-violet-100 text-violet-700',
                            'interactive' => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };

                        // Get circle/lesson/course name
                        $sourceName = null;
                        if ($session) {
                            if ($sessionType === 'quran') {
                                $sourceName = ($session->session_type === 'individual')
                                    ? $session->individualCircle?->name
                                    : $session->circle?->name;
                            } elseif ($sessionType === 'academic') {
                                $sourceName = $session->academicIndividualLesson?->name;
                            } elseif ($sessionType === 'interactive') {
                                $sourceName = $session->course?->title;
                            }
                        }

                        $statusLabel = $earning->is_disputed
                            ? __('supervisor.teacher_earnings.status_disputed')
                            : ($earning->is_finalized
                                ? __('supervisor.teacher_earnings.status_finalized')
                                : __('supervisor.teacher_earnings.status_pending'));

                        $statusClass = $earning->is_disputed
                            ? 'bg-red-100 text-red-700'
                            : ($earning->is_finalized
                                ? 'bg-green-100 text-green-700'
                                : 'bg-amber-100 text-amber-700');

                        $isDisputed = $earning->is_disputed;
                        $canDispute = !$earning->is_disputed;
                    @endphp

                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-start gap-3 md:gap-4">
                            {{-- Teacher Avatar --}}
                            @if($teacherUser)
                                <x-avatar :user="$teacherUser" size="md" />
                            @else
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="ri-user-line text-gray-500"></i>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h3 class="text-base md:text-lg font-bold text-gray-900 truncate">{{ $teacherName }}</h3>
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $sourceBadgeClass }}">
                                        {{ $sourceLabel }}
                                    </span>
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                    <span class="flex items-center gap-1 font-bold text-gray-900">
                                        <i class="ri-money-dollar-circle-line text-green-500"></i>
                                        {{ $earning->formatted_amount }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-calculator-line text-gray-400"></i>
                                        {{ $earning->calculation_method_label }}
                                    </span>
                                    @if($earning->session_completed_at)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                            {{ $earning->session_completed_at->format('Y-m-d') }}
                                            @if($earning->earning_month && $earning->session_completed_at->format('Y-m') !== $earning->earning_month->format('Y-m'))
                                                <span class="text-gray-400">({{ $earning->earning_month->locale('ar')->translatedFormat('F Y') }})</span>
                                            @endif
                                        </span>
                                    @elseif($earning->earning_month)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                            {{ $earning->earning_month->locale('ar')->translatedFormat('F Y') }}
                                        </span>
                                    @endif
                                    @if($sourceName)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-book-open-line text-gray-400"></i>
                                            {{ $sourceName }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Dispute notes (shown when disputed) --}}
                                @if($isDisputed && $earning->dispute_notes)
                                    <div x-data="{ expanded: false }" class="mt-2">
                                        <button @click="expanded = !expanded" type="button" class="cursor-pointer text-xs text-red-600 hover:text-red-700 flex items-center gap-1">
                                            <i class="ri-chat-quote-line"></i>
                                            {{ __('supervisor.teacher_earnings.current_dispute_notes') }}
                                            <i class="ri-arrow-down-s-line transition-transform" :class="{ 'rotate-180': expanded }"></i>
                                        </button>
                                        <div x-show="expanded" x-collapse class="mt-1 p-2 bg-red-50 rounded-lg text-xs text-red-800 whitespace-pre-line">{{ $earning->dispute_notes }}</div>
                                    </div>
                                @endif
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($teacherUser)
                                    <a href="{{ route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $teacherUser->id]) }}"
                                       class="cursor-pointer min-h-[32px] inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg transition-colors text-xs font-medium border border-indigo-200">
                                        <i class="ri-user-line"></i>
                                        <span class="hidden sm:inline">{{ __('supervisor.teacher_earnings.view_teacher') }}</span>
                                    </a>
                                @endif
                                @if($canDispute)
                                    {{-- Dispute Button --}}
                                    <button type="button"
                                        onclick="document.getElementById('dispute-modal-{{ $earning->id }}').classList.remove('hidden')"
                                        class="cursor-pointer min-h-[32px] inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg transition-colors text-xs font-medium border border-amber-200">
                                        <i class="ri-error-warning-line"></i>
                                        <span class="hidden sm:inline">{{ __('supervisor.teacher_earnings.action_dispute') }}</span>
                                    </button>
                                @elseif($isDisputed)
                                    {{-- Resolve Button --}}
                                    <button type="button"
                                        onclick="document.getElementById('resolve-modal-{{ $earning->id }}').classList.remove('hidden')"
                                        class="cursor-pointer min-h-[32px] inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 hover:bg-green-100 rounded-lg transition-colors text-xs font-medium border border-green-200">
                                        <i class="ri-check-double-line"></i>
                                        <span class="hidden sm:inline">{{ __('supervisor.teacher_earnings.action_resolve') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Dispute Modal --}}
                    @if($canDispute)
                        <div id="dispute-modal-{{ $earning->id }}" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
                            <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
                                <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                                    <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                                    <div class="p-6 pb-4 pt-8 md:pt-6">
                                        <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 mb-4">
                                            <i class="ri-error-warning-line text-2xl text-amber-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.teacher_earnings.action_dispute') }}</h3>
                                        <form method="POST" action="{{ route('manage.teacher-earnings.dispute', ['subdomain' => $subdomain, 'earning' => $earning->id]) }}">
                                            @csrf
                                            <div class="mb-4">
                                                <label for="dispute_notes_{{ $earning->id }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.dispute_notes_label') }} <span class="text-red-500">*</span></label>
                                                <textarea
                                                    name="dispute_notes"
                                                    id="dispute_notes_{{ $earning->id }}"
                                                    rows="3"
                                                    required
                                                    maxlength="1000"
                                                    placeholder="{{ __('supervisor.teacher_earnings.dispute_notes_placeholder') }}"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
                                            </div>
                                            <div class="flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                                                <button type="button" onclick="this.closest('[id^=dispute-modal]').classList.add('hidden')"
                                                    class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-colors">
                                                    {{ __('common.actions.cancel') }}
                                                </button>
                                                <button type="submit"
                                                    class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">
                                                    {{ __('supervisor.teacher_earnings.action_dispute') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Resolve Modal --}}
                    @if($isDisputed)
                        <div id="resolve-modal-{{ $earning->id }}" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
                            <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
                                <div class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
                                    <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
                                    <div class="p-6 pb-4 pt-8 md:pt-6">
                                        <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-green-100 mb-4">
                                            <i class="ri-check-double-line text-2xl text-green-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-center text-gray-900 mb-2">{{ __('supervisor.teacher_earnings.action_resolve') }}</h3>
                                        <p class="text-center text-gray-600 text-sm mb-4">{{ __('supervisor.teacher_earnings.confirm_resolve') }}</p>

                                        @if($earning->dispute_notes)
                                            <div class="mb-4 p-3 bg-red-50 rounded-lg border border-red-100">
                                                <p class="text-xs font-medium text-red-700 mb-1">{{ __('supervisor.teacher_earnings.current_dispute_notes') }}:</p>
                                                <p class="text-xs text-red-800 whitespace-pre-line">{{ $earning->dispute_notes }}</p>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('manage.teacher-earnings.resolve', ['subdomain' => $subdomain, 'earning' => $earning->id]) }}">
                                            @csrf
                                            <div class="mb-4">
                                                <label for="resolution_notes_{{ $earning->id }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.resolution_notes_label') }}</label>
                                                <textarea
                                                    name="resolution_notes"
                                                    id="resolution_notes_{{ $earning->id }}"
                                                    rows="3"
                                                    maxlength="500"
                                                    placeholder="{{ __('supervisor.teacher_earnings.resolution_notes_placeholder') }}"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
                                            </div>
                                            <div class="flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                                                <button type="button" onclick="this.closest('[id^=resolve-modal]').classList.add('hidden')"
                                                    class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-colors">
                                                    {{ __('common.actions.cancel') }}
                                                </button>
                                                <button type="submit"
                                                    class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                                                    {{ __('supervisor.teacher_earnings.action_resolve') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($earnings->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $earnings->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-money-dollar-circle-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teacher_earnings.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.teacher_earnings.no_results_description') }}</p>
                    <a href="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.teachers.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teacher_earnings.no_earnings') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.teacher_earnings.no_earnings_description') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
