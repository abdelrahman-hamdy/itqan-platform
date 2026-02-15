<x-layouts.teacher
    :title="__('teacher.progress.report_title_group') . ' - ' . $circle->name . ' - ' . config('app.name', __('teacher.panel.academy_default'))"
    :description="__('teacher.progress.report_title_group') . ': ' . $circle->name">

<!-- Progress page CSS is loaded via resources/css/progress.css through Vite -->
<!-- Chart.js is bundled via Vite (resources/js/chart-init.js) -->

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 p-3 md:p-6">
    <!-- Enhanced Header with Circle Profile -->
    <div class="progress-gradient rounded-xl md:rounded-2xl shadow-lg text-white p-4 md:p-8 mb-4 md:mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6">
            <div class="flex items-start gap-3 md:gap-6">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-white/80 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line text-xl md:text-2xl"></i>
                </a>

                <!-- Circle Icon and Basic Info -->
                <div class="flex items-start gap-3 md:gap-4">
                    <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-xl md:rounded-2xl flex items-center justify-center">
                            <i class="ri-group-line text-2xl md:text-4xl text-white"></i>
                        </div>
                        <div class="absolute -bottom-1 -end-1 w-4 h-4 md:w-6 md:h-6 bg-green-500 rounded-full border-2 md:border-3 border-white flex items-center justify-center">
                            <i class="ri-check-line text-[8px] md:text-xs text-white"></i>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-1 truncate">{{ $circle->name }}</h1>
                        <div class="flex flex-wrap items-center gap-2 md:gap-4 text-white/90 text-xs md:text-sm">
                            <span class="flex items-center gap-1">
                                <i class="ri-group-line"></i>
                                <span>{{ __('teacher.progress.student_count', ['count' => $stats['enrolled_students']]) }}</span>
                            </span>
                            <span class="flex items-center gap-1 hidden sm:flex">
                                <i class="ri-user-line"></i>
                                <span class="truncate max-w-[100px] md:max-w-none">{{ $circle->quranTeacher->user->name ?? __('teacher.progress.teacher') }}</span>
                            </span>
                        </div>

                        <!-- Quick Progress Indicator -->
                        <div class="mt-2 md:mt-3 flex flex-wrap items-center gap-2 md:gap-4">
                            <div class="flex items-center gap-2">
                                <div class="w-16 md:w-24 h-1.5 md:h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500"
                                         style="width: {{ $stats['attendance_rate'] }}%"></div>
                                </div>
                                <span class="text-xs md:text-sm font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                            </div>
                            <span class="text-xs md:text-sm text-white/80">{{ __('teacher.progress.attendance_rate') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 md:gap-3 me-auto lg:mr-0" x-data>
                <button @click="window.print()" class="min-h-[44px] inline-flex items-center justify-center px-3 md:px-6 py-2 md:py-3 bg-white/20 backdrop-blur-sm text-white text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:bg-white/30 transition-colors">
                    <i class="ri-printer-line md:ms-2"></i>
                    <span class="hidden md:inline">{{ __('teacher.progress.print_report') }}</span>
                </button>
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] inline-flex items-center justify-center px-3 md:px-6 py-2 md:py-3 bg-white text-purple-600 text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="ri-group-line md:ms-2"></i>
                    <span class="hidden md:inline">{{ __('teacher.progress.view_circle') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Progress Statistics with Better UX -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-6 mb-4 md:mb-8">
        <x-circle.progress-stat-card
            :label="__('teacher.progress.total_sessions')"
            :value="$stats['total_sessions']"
            :subtitle="__('teacher.progress.since_start')"
            subtitleIcon="ri-calendar-line"
            icon="ri-book-line"
            color="blue"
        />

        <x-circle.progress-stat-card
            :label="__('teacher.progress.completed_sessions')"
            :value="$stats['completed_sessions']"
            :subtitle="__('teacher.progress.with_group')"
            subtitleIcon="ri-check-line"
            icon="ri-checkbox-circle-line"
            color="green"
        />

        <x-circle.progress-stat-card
            :label="__('teacher.progress.enrolled_students')"
            :value="$stats['enrolled_students']"
            :subtitle="__('teacher.progress.from_max', ['max' => $stats['max_students'] ?? '∞'])"
            subtitleIcon="ri-user-add-line"
            icon="ri-group-line"
            color="purple"
        />

        <x-circle.progress-stat-card
            :label="__('teacher.progress.upcoming_sessions')"
            :value="$stats['upcoming_sessions']"
            :subtitle="__('teacher.progress.scheduled')"
            subtitleIcon="ri-time-line"
            icon="ri-calendar-check-line"
            color="orange"
        />

        <x-circle.progress-stat-card
            :label="__('teacher.progress.enrollment_rate')"
            :value="number_format($stats['enrollment_rate'], 1) . '%'"
            icon="ri-user-check-line"
            color="emerald"
            :showProgressBar="true"
            :progressValue="$stats['enrollment_rate']"
            colSpan="col-span-2 sm:col-span-1"
        />
    </div>

    <!-- Comprehensive Analytics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8 mb-4 md:mb-8">
        <x-circle.chart-container
            chartId="attendanceChart"
            :title="__('teacher.progress.attendance_trends')"
            :subtitle="__('teacher.progress.attendance_over_time')"
            iconClass="ri-line-chart-line"
            iconColor="text-blue-600"
        />

        <x-circle.chart-container
            chartId="sessionStatusChart"
            :title="__('teacher.progress.session_status_distribution')"
            :subtitle="__('teacher.progress.session_overview')"
            iconClass="ri-pie-chart-line"
            iconColor="text-green-600"
        />

        <x-circle.chart-container
            chartId="performanceChart"
            :title="__('teacher.progress.performance_evolution')"
            :subtitle="__('teacher.progress.recitation_tajweed_over_time')"
            iconClass="ri-bar-chart-line"
            iconColor="text-purple-600"
        />

        <x-circle.chart-container
            chartId="weeklyActivityChart"
            :title="__('teacher.progress.weekly_activity_map')"
            :subtitle="__('teacher.progress.sessions_distribution_week')"
            iconClass="ri-calendar-2-line"
            iconColor="text-orange-600"
        />
    </div>

    <!-- Enhanced Group Progress Overview -->
    <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8 mb-4 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 md:mb-6 gap-3">
            <div>
                <h3 class="text-lg md:text-2xl font-bold text-gray-900">{{ __('teacher.progress.circle_overview') }}</h3>
                <p class="text-gray-600 text-xs md:text-base">{{ __('teacher.progress.circle_group_progress') }}</p>
            </div>
            <div class="text-end sm:text-start">
                <span class="text-2xl md:text-4xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                    {{ number_format($stats['consistency_score'], 1) }}/10
                </span>
                <p class="text-xs md:text-sm text-gray-500">{{ __('teacher.progress.consistency_score') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Consistency Score -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">{{ __('teacher.progress.circle_consistency') }}</span>
                    <span class="font-medium">{{ number_format($stats['consistency_score'], 1) }}/10</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-500 to-purple-500 h-3 rounded-full transition-all duration-1000 shadow-lg"
                         style="width: {{ $stats['consistency_score'] * 10 }}%"></div>
                </div>
            </div>

            <!-- Schedule Adherence -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">{{ __('teacher.progress.schedule_adherence') }}</span>
                    <span class="font-medium">{{ number_format($stats['schedule_adherence'], 1) }}/10</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-3 rounded-full transition-all duration-1000 shadow-lg"
                         style="width: {{ $stats['schedule_adherence'] * 10 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">
        <!-- Main Content - Group Sessions and Activity -->
        <div class="lg:col-span-2 space-y-4 md:space-y-8">
            
            <!-- Enhanced Group Sessions History -->
            @if($circle->sessions->count() > 0)
                <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 md:mb-6">
                        <h3 class="text-lg md:text-2xl font-bold text-gray-900">{{ __('teacher.progress.group_session_log') }}</h3>
                        <span class="text-xs md:text-sm bg-green-100 text-green-700 px-2 md:px-3 py-1 rounded-full font-medium w-fit">
                            {{ __('teacher.progress.last_10_sessions') }}
                        </span>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($circle->sessions->sortByDesc('scheduled_at')->take(10) as $session)
                            <div class="attendance-indicator bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300 cursor-pointer"
                                 x-data @click="openSessionDetail({{ $session->id }})">
                                <div class="flex items-center justify-between">
                                    <!-- Session Info -->
                                    <div class="flex items-center gap-4">
                                        <!-- Session Status Indicator -->
                                        <div class="flex flex-col items-center">
                                            @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                                <span class="text-xs text-green-600 font-bold">{{ __('teacher.progress.completed_status') }}</span>
                                            @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                                <div class="w-4 h-4 bg-blue-500 rounded-full mb-1 animate-bounce"></div>
                                                <span class="text-xs text-blue-600 font-bold">{{ __('teacher.progress.scheduled_status') }}</span>
                                            @elseif($session->status === App\Enums\SessionStatus::CANCELLED)
                                                <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">{{ __('teacher.progress.cancelled') }}</span>
                                            @elseif($session->status === App\Enums\SessionStatus::ABSENT)
                                                <div class="w-4 h-4 bg-red-400 rounded-full mb-1"></div>
                                                <span class="text-xs text-red-700 font-bold">{{ __('teacher.progress.absent') }}</span>
                                            @else
                                                <div class="w-4 h-4 bg-gray-300 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">{{ $session->status->label() }}</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Session Details -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h4 class="font-bold text-gray-900 text-lg">{{ $session->title ?? __('teacher.progress.group_quran_session') }}</h4>
                                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                                        <i class="ri-group-line ms-1"></i>
                                                        {{ __('teacher.progress.student_count', ['count' => $stats['enrolled_students']]) }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                                <span class="flex items-center gap-1">
                                                    <i class="ri-calendar-line"></i>
                                                    <span>{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at) : __('teacher.progress.unscheduled') }}</span>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="ri-time-line"></i>
                                                    <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                                                </span>
                                                @if($session->actual_duration_minutes)
                                                    <span class="flex items-center gap-1">
                                                        <i class="ri-timer-line"></i>
                                                        <span>{{ __('teacher.progress.duration_minutes', ['minutes' => $session->actual_duration_minutes]) }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Session Status and Performance -->
                                    <div class="text-start">
                                        <div class="flex flex-col items-end space-y-2">
                                            <!-- Status Badge -->
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'bg-green-100 text-green-800' :
                                                   ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-blue-100 text-blue-800' : 
                                                   ($session->status === App\Enums\SessionStatus::CANCELLED ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800')) }}">
                                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                    <i class="ri-check-double-line ms-1"></i> {{ __('teacher.progress.completed_status') }}
                                                @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                                    <i class="ri-calendar-check-line ms-1"></i> {{ __('teacher.progress.scheduled_status') }}
                                                @elseif($session->status === App\Enums\SessionStatus::CANCELLED)
                                                    <i class="ri-close-line ms-1"></i> {{ __('teacher.progress.cancelled') }}
                                                @else
                                                    <i class="ri-question-line ms-1"></i> {{ $session->status->label() }}
                                                @endif
                                            </span>
                                            
                                            <!-- Performance Indicators -->
                                            @if($session->status === App\Enums\SessionStatus::COMPLETED && ($session->recitation_quality || $session->tajweed_accuracy))
                                                <div class="flex items-center gap-1 text-xs">
                                                    @if($session->recitation_quality)
                                                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded">
                                                            {{ __('teacher.progress.recitation_score', ['score' => $session->recitation_quality]) }}
                                                        </span>
                                                    @endif
                                                    @if($session->tajweed_accuracy)
                                                        <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">
                                                            {{ __('teacher.progress.tajweed_score', ['score' => $session->tajweed_accuracy]) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <i class="ri-calendar-line text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">{{ __('teacher.progress.no_sessions_recorded') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if($circle->sessions->count() > 10)
                        <div class="mt-6 text-center">
                            <button class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors">
                                <i class="ri-more-line ms-2"></i>
                                {{ __('teacher.progress.view_all_sessions') }} {{ __('teacher.progress.session_count_total', ['count' => $circle->sessions->count()]) }}
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Enhanced Sidebar with Group-Specific Information -->
        <div class="space-y-4 md:space-y-8">

            <!-- Performance Summary -->
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('teacher.progress.circle_performance') }}</h3>
                    <i class="ri-bar-chart-line text-2xl text-primary-600"></i>
                </div>

                <div class="space-y-4">
                    <!-- Average Recitation Quality -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('teacher.progress.average_recitation') }}</span>
                            <span class="font-medium">{{ number_format($stats['avg_recitation_quality'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['avg_recitation_quality'] * 10 }}%"></div>
                        </div>
                    </div>

                    <!-- Average Tajweed Accuracy -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('teacher.progress.average_tajweed') }}</span>
                            <span class="font-medium">{{ number_format($stats['avg_tajweed_accuracy'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['avg_tajweed_accuracy'] * 10 }}%"></div>
                        </div>
                    </div>

                    <!-- Session Attendance Rate -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('teacher.progress.session_attendance_rate') }}</span>
                            <span class="font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Overall Grade -->
                <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-1">{{ __('teacher.progress.circle_overall_grade') }}</p>
                        @php
                            $overallGrade = ($stats['avg_recitation_quality'] + $stats['avg_tajweed_accuracy'] + ($stats['attendance_rate']/10)) / 3;
                            $gradeText = $overallGrade >= 8.5 ? __('teacher.progress.excellent') : ($overallGrade >= 7 ? __('teacher.progress.very_good') : ($overallGrade >= 6 ? __('teacher.progress.good') : __('teacher.progress.needs_improvement')));
                            $gradeColor = $overallGrade >= 8.5 ? 'text-green-600' : ($overallGrade >= 7 ? 'text-blue-600' : ($overallGrade >= 6 ? 'text-yellow-600' : 'text-red-600'));
                        @endphp
                        <p class="text-2xl font-bold {{ $gradeColor }}">{{ $gradeText }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($overallGrade, 1) }}/10</p>
                    </div>
                </div>
            </div>

            <!-- Circle Details -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('teacher.progress.circle_details') }}</h3>

                <div class="space-y-4">
                    <!-- Basic Info -->
                    <div class="text-center pb-4 border-b border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <i class="ri-group-line text-3xl text-white"></i>
                        </div>
                        <p class="font-bold text-gray-900 text-lg">{{ $circle->name }}</p>
                        <p class="text-sm text-gray-500">{{ $circle->description ?? __('teacher.progress.group_quran_circle') }}</p>
                    </div>

                    <div class="space-y-3 text-sm">
                        <!-- Teacher -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-user-star-line ms-1"></i>
                                {{ __('teacher.progress.teacher') }}
                            </span>
                            <span class="font-medium">{{ $circle->quranTeacher->user->name ?? __('teacher.progress.not_specified_status') }}</span>
                        </div>

                        <!-- Schedule -->
                        @if($circle->schedule_days_text)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 flex items-center">
                                    <i class="ri-calendar-line ms-1"></i>
                                    {{ __('teacher.progress.circle_days') }}
                                </span>
                                <span class="font-medium">{{ $circle->schedule_days_text }}</span>
                            </div>
                        @endif

                        <!-- Status -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-pulse-line ms-1"></i>
                                {{ __('teacher.progress.status') }}
                            </span>
                            <span class="font-medium px-3 py-1 rounded-full text-sm
                                {{ $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $circle->status ? __('teacher.progress.active') : __('common.inactive') }}
                            </span>
                        </div>

                        <!-- Students -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-group-line ms-1"></i>
                                {{ __('teacher.progress.students_count') }}
                            </span>
                            <span class="font-medium">{{ $stats['enrolled_students'] }}/{{ $stats['max_students'] ?? '∞' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Overview -->
            @if($circle->students && $circle->students->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('teacher.progress.enrolled_students_list') }}</h3>

                    <div class="space-y-3">
                        @foreach($circle->students->take(5) as $student)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <x-avatar :user="$student" size="sm" />
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 text-sm">{{ $student->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student->email ?? __('teacher.progress.student_label') }}</p>
                                </div>
                                <div class="text-end">
                                    <span class="text-primary-600 text-xs">
                                        <i class="ri-user-line"></i>
                                    </span>
                                </div>
                            </div>
                        @endforeach

                        @if($circle->students->count() > 5)
                            <div class="text-center pt-2">
                                <span class="text-sm text-gray-500">{{ __('teacher.progress.other_students', ['count' => $circle->students->count() - 5]) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('teacher.progress.quick_actions') }}</h3>

                <div class="space-y-3">
                    <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                       class="block w-full text-center px-5 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-colors shadow-lg">
                        <i class="ri-eye-line ms-2"></i>
                        {{ __('teacher.progress.view_circle_btn') }}
                    </a>

                    <!-- Schedule management removed - now handled in Filament dashboard -->

                    <button class="w-full px-5 py-3 border-2 border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors">
                        <i class="ri-download-line ms-2"></i>
                        {{ __('teacher.progress.export_report') }}
                    </button>

                    <button class="w-full px-5 py-3 border-2 border-orange-200 text-orange-700 text-sm font-medium rounded-xl hover:bg-orange-50 hover:border-orange-300 transition-colors">
                        <i class="ri-notification-line ms-2"></i>
                        {{ __('teacher.progress.send_report_students') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for teacher sessions
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        window.location.href = finalUrl;
    @else
    @endif
}

// Chart.js Configuration
document.addEventListener('DOMContentLoaded', function() {
    
    // Sample data - this would come from the controller in real implementation
    const sessionData = [
        @foreach($circle->sessions as $session)
            {
                date: '{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at, 'Y-m-d') : '' }}',
                status: '{{ $session->status }}',
                recitation_quality: {{ $session->recitation_quality ?? 0 }},
                tajweed_accuracy: {{ $session->tajweed_accuracy ?? 0 }},
                attendance_rate: {{ rand(70, 100) }}, // This should come from actual data
                day_of_week: {{ $session->scheduled_at ? toAcademyTimezone($session->scheduled_at)->format('w') : 'null' }}
            }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ];

    // 1. Attendance Trends Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'line',
        data: {
            labels: sessionData.filter(s => s.date).map(s => s.date).slice(-10),
            datasets: [{
                label: '{{ __('teacher.progress.attendance_rate_label') }}',
                data: sessionData.filter(s => s.date).map(s => s.attendance_rate).slice(-10),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 2. Session Status Distribution Chart
    const statusCounts = sessionData.reduce((acc, session) => {
        acc[session.status] = (acc[session.status] || 0) + 1;
        return acc;
    }, {});

    const statusCtx = document.getElementById('sessionStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['{{ __('teacher.progress.completed_status') }}', '{{ __('teacher.progress.scheduled_status') }}', '{{ __('teacher.progress.cancelled') }}', '{{ __('teacher.progress.unscheduled') }}'],
            datasets: [{
                data: [
                    statusCounts.completed || 0,
                    statusCounts.scheduled || 0,
                    statusCounts.cancelled || 0,
                    statusCounts.unscheduled || 0
                ],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(156, 163, 175)',
                    'rgb(251, 146, 60)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    // 3. Performance Metrics Over Time
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: sessionData.filter(s => s.date && s.recitation_quality > 0).map(s => s.date).slice(-8),
            datasets: [{
                label: '{{ __('teacher.progress.recitation_quality_label') }}',
                data: sessionData.filter(s => s.date && s.recitation_quality > 0).map(s => s.recitation_quality).slice(-8),
                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                borderColor: 'rgb(147, 51, 234)',
                borderWidth: 1
            }, {
                label: '{{ __('teacher.progress.tajweed_accuracy_label') }}',
                data: sessionData.filter(s => s.date && s.tajweed_accuracy > 0).map(s => s.tajweed_accuracy).slice(-8),
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 4. Weekly Activity Heatmap (simplified as bar chart)
    const weeklyData = Array(7).fill(0);
    sessionData.forEach(session => {
        if (session.day_of_week !== null) {
            weeklyData[session.day_of_week]++;
        }
    });

    const weeklyCtx = document.getElementById('weeklyActivityChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: ['{{ __('teacher.progress.day_sunday') }}', '{{ __('teacher.progress.day_monday') }}', '{{ __('teacher.progress.day_tuesday') }}', '{{ __('teacher.progress.day_wednesday') }}', '{{ __('teacher.progress.day_thursday') }}', '{{ __('teacher.progress.day_friday') }}', '{{ __('teacher.progress.day_saturday') }}'],
            datasets: [{
                label: '{{ __('teacher.progress.sessions_count_label') }}',
                data: weeklyData,
                backgroundColor: 'rgba(251, 146, 60, 0.8)',
                borderColor: 'rgb(251, 146, 60)',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

</x-layouts.teacher>
