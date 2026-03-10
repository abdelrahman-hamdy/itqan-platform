@php
    use App\Enums\AttendanceStatus;
    use App\Enums\PerformanceLevel;
    use App\Models\StudentSessionReport;
    use App\Models\AcademicSessionReport;
    use App\Models\InteractiveSessionReport;

    $isQuran = $report instanceof StudentSessionReport;
    $isAcademic = $report instanceof AcademicSessionReport;
    $isInteractive = $report instanceof InteractiveSessionReport;

    $session = $report->session;
    $student = $report->student;
    $teacher = $report->teacher;

    // Type config
    if ($isQuran) {
        $isIndividual = $session?->individual_circle_id !== null;
        $typeLabel = $isIndividual ? __('reports.type_quran_individual') : __('reports.type_quran_group');
        $typeIcon = $isIndividual ? 'ri-user-star-line' : 'ri-group-line';
        $typeColor = 'green';
        $sessionTitle = $session?->title ?? __('reports.quran_session');
        $teacherName = $session?->quranTeacher?->name ?? $teacher?->name ?? '';
        $teacherUser = $session?->quranTeacher ?? $teacher;
        $entityName = $isIndividual
            ? ($session?->individualCircle?->name ?? $student?->name ?? '')
            : ($session?->circle?->name ?? '');
    } elseif ($isAcademic) {
        $typeLabel = __('reports.type_academic_lesson');
        $typeIcon = 'ri-graduation-cap-line';
        $typeColor = 'violet';
        $sessionTitle = $session?->title ?? __('reports.academic_session');
        $teacherName = $session?->academicTeacher?->user?->name ?? $teacher?->name ?? '';
        $teacherUser = $session?->academicTeacher?->user ?? $teacher;
        $entityName = $session?->academicIndividualLesson?->name ?? '';
    } else {
        $typeLabel = __('reports.type_interactive_course');
        $typeIcon = 'ri-live-line';
        $typeColor = 'purple';
        $sessionTitle = $session?->course?->title ?? $session?->title ?? __('reports.interactive_session');
        $teacherName = $session?->course?->assignedTeacher?->user?->name ?? $teacher?->name ?? '';
        $teacherUser = $session?->course?->assignedTeacher?->user ?? $teacher;
        $entityName = $session?->course?->title ?? '';
    }

    // Attendance config
    $attendanceBadge = match($report->attendance_status) {
        AttendanceStatus::ATTENDED => ['class' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'icon' => 'ri-check-line', 'iconColor' => 'text-emerald-500'],
        AttendanceStatus::LATE => ['class' => 'bg-amber-100 text-amber-700 border-amber-200', 'icon' => 'ri-time-line', 'iconColor' => 'text-amber-500'],
        AttendanceStatus::LEFT => ['class' => 'bg-orange-100 text-orange-700 border-orange-200', 'icon' => 'ri-logout-box-line', 'iconColor' => 'text-orange-500'],
        AttendanceStatus::ABSENT => ['class' => 'bg-red-100 text-red-700 border-red-200', 'icon' => 'ri-close-line', 'iconColor' => 'text-red-500'],
        default => ['class' => 'bg-gray-100 text-gray-500 border-gray-200', 'icon' => 'ri-question-line', 'iconColor' => 'text-gray-400'],
    };

    // Performance
    $performanceScore = $report->overall_performance;
    $performanceLevel = $report->performance_level_enum;
    $performanceLevelLabel = $report->performance_level;
    $performanceColorMap = [
        'success' => 'text-emerald-600 bg-emerald-100 border-emerald-200',
        'info' => 'text-sky-600 bg-sky-100 border-sky-200',
        'primary' => 'text-indigo-600 bg-indigo-100 border-indigo-200',
        'warning' => 'text-amber-600 bg-amber-100 border-amber-200',
        'danger' => 'text-rose-600 bg-rose-100 border-rose-200',
    ];
    $performanceClass = $performanceLevel ? ($performanceColorMap[$performanceLevel->color()] ?? 'text-gray-600 bg-gray-100 border-gray-200') : 'text-gray-400 bg-gray-50 border-gray-200';

    // Breadcrumb
    $breadcrumbLabel = $layoutType === 'supervisor' ? __('supervisor.session_reports.breadcrumb') : __('teacher.reports.breadcrumb');

    // Session date in academy timezone
    $sessionDateFormatted = null;
    $sessionDateDay = null;
    $sessionDateTime = null;
    if ($session?->scheduled_at) {
        $scheduledInTz = toAcademyTimezone($session->scheduled_at);
        $sessionDateFormatted = $scheduledInTz->format('Y/m/d');
        $sessionDateDay = $scheduledInTz->translatedFormat('l');
        $sessionDateTime = $scheduledInTz->format('h:i A');
    }

    // Evaluated at in academy timezone
    $evaluatedAtFormatted = null;
    if ($report->evaluated_at) {
        $evaluatedAtFormatted = toAcademyTimezone($report->evaluated_at)->format('Y/m/d h:i A');
    }
@endphp

<x-reports.layouts.base-report
    :title="__('reports.detail_title') . ' - ' . config('app.name')"
    :layoutType="$layoutType">

    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => $breadcrumbLabel, 'route' => $backRoute],
            ['label' => $sessionTitle, 'truncate' => true],
        ]"
        :view-type="$layoutType === 'supervisor' ? 'supervisor' : 'teacher'"
    />

    {{-- Page Header --}}
    <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ $sessionTitle }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $typeColor }}-100 text-{{ $typeColor }}-700">
                        <i class="{{ $typeIcon }} text-xs"></i>
                        {{ $typeLabel }}
                    </span>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border {{ $attendanceBadge['class'] }}">
                <i class="{{ $attendanceBadge['icon'] }}"></i>
                {{ $report->attendance_status?->label() ?? __('reports.status_unknown') }}
            </span>
        </div>
    </div>

    {{-- Top Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        {{-- Performance Score --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-100">
                    <i class="ri-bar-chart-line text-lg text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ $performanceScore !== null ? $performanceScore . __('reports.out_of_ten') : __('reports.not_available') }}
                    </p>
                    <p class="text-xs text-gray-600">{{ __('reports.overall_score') }}</p>
                </div>
            </div>
        </div>

        {{-- Attendance Duration --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-sky-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-timer-line text-lg text-sky-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ $report->actual_attendance_minutes ? $report->actual_attendance_minutes . ' ' . __('reports.minutes_unit') : __('reports.not_available') }}
                    </p>
                    <p class="text-xs text-gray-600">{{ __('reports.attendance_duration') }}</p>
                </div>
            </div>
        </div>

        {{-- Attendance Percentage --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-pie-chart-line text-lg text-teal-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ $report->attendance_percentage !== null ? round($report->attendance_percentage) . '%' : __('reports.not_available') }}
                    </p>
                    <p class="text-xs text-gray-600">{{ __('reports.attendance_percentage') }}</p>
                </div>
            </div>
        </div>

        {{-- Performance Level --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0 bg-amber-100">
                    <i class="ri-award-line text-lg text-amber-600"></i>
                </div>
                <div>
                    <p class="text-base font-bold text-gray-900 truncate">{{ $performanceLevelLabel }}</p>
                    <p class="text-xs text-gray-600">{{ __('reports.performance_level') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Main Column (2/3) --}}
        <div class="lg:col-span-2 space-y-4 md:space-y-6">

            {{-- Session & Student Info Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-information-line text-indigo-500"></i>
                        {{ __('reports.session_info') }}
                    </h2>
                </div>
                <div class="px-4 md:px-6 py-4">
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-x-6 gap-y-4">
                        {{-- Student --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.student_name') }}</dt>
                            <dd class="flex items-center gap-2.5">
                                @if($student)
                                    <x-avatar :user="$student" size="xs" />
                                @endif
                                <span class="text-sm font-semibold text-gray-900">{{ $student?->name ?? __('reports.unknown_student') }}</span>
                            </dd>
                        </div>

                        {{-- Teacher --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.teacher_name') }}</dt>
                            <dd class="flex items-center gap-2.5">
                                @if($teacherUser)
                                    <x-avatar :user="$teacherUser" size="xs" />
                                @endif
                                <span class="text-sm font-semibold text-gray-900">{{ $teacherName ?: __('reports.not_available') }}</span>
                            </dd>
                        </div>

                        {{-- Session Title --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.session_title') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $sessionTitle }}</dd>
                        </div>

                        {{-- Entity --}}
                        @if($entityName)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.entity_label') }}</dt>
                                <dd class="text-sm text-gray-900">{{ $entityName }}</dd>
                            </div>
                        @endif

                        {{-- Session Date --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.session_date') }}</dt>
                            <dd>
                                @if($sessionDateFormatted)
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg">
                                        <i class="ri-calendar-event-line text-indigo-500"></i>
                                        <span class="text-sm font-medium text-indigo-700">{{ $sessionDateDay }} {{ $sessionDateFormatted }}</span>
                                        <span class="text-xs text-indigo-500">{{ $sessionDateTime }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">{{ __('reports.not_available') }}</span>
                                @endif
                            </dd>
                        </div>

                        {{-- Session Duration --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.session_duration') }}</dt>
                            <dd>
                                @if($session?->duration_minutes)
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 border border-sky-100 rounded-lg">
                                        <i class="ri-time-line text-sky-500"></i>
                                        <span class="text-sm font-medium text-sky-700">{{ $session->duration_minutes }} {{ __('reports.minutes_unit') }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">{{ __('reports.not_available') }}</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attendance Details Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-user-follow-line text-emerald-500"></i>
                        {{ __('reports.attendance_info') }}
                    </h2>
                </div>
                <div class="px-4 md:px-6 py-4">
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        {{-- Attendance Status --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.attendance_status_label') }}</dt>
                            <dd>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attendanceBadge['class'] }}">
                                    <i class="{{ $attendanceBadge['icon'] }} text-xs"></i>
                                    {{ $report->attendance_status?->label() ?? __('reports.status_unknown') }}
                                </span>
                            </dd>
                        </div>

                        {{-- Attendance Duration --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.attendance_duration') }}</dt>
                            <dd class="text-sm text-gray-900">
                                {{ $report->actual_attendance_minutes ? $report->actual_attendance_minutes . ' ' . __('reports.minutes_unit') : __('reports.not_available') }}
                            </dd>
                        </div>

                        {{-- Join Time --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.join_time') }}</dt>
                            <dd class="text-sm text-gray-900">
                                {{ $report->meeting_enter_time ? toAcademyTimezone($report->meeting_enter_time)->format('h:i:s A') : __('reports.not_available') }}
                            </dd>
                        </div>

                        {{-- Leave Time --}}
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.leave_time') }}</dt>
                            <dd class="text-sm text-gray-900">
                                {{ $report->meeting_leave_time ? toAcademyTimezone($report->meeting_leave_time)->format('h:i:s A') : __('reports.not_available') }}
                            </dd>
                        </div>

                        {{-- Late By --}}
                        @if($report->is_late && $report->late_minutes)
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.late_by') }}</dt>
                                <dd class="text-sm text-amber-600 font-medium">
                                    {{ $report->late_minutes }} {{ __('reports.minutes_unit') }}
                                </dd>
                            </div>
                        @endif

                        {{-- Attendance Percentage (full width) --}}
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500 mb-1.5">{{ __('reports.attendance_percentage') }}</dt>
                            <dd>
                                @if($report->attendance_percentage !== null)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full {{ $report->attendance_percentage >= 80 ? 'bg-emerald-500' : ($report->attendance_percentage >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                 style="width: {{ min(100, round($report->attendance_percentage)) }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 flex-shrink-0">{{ round($report->attendance_percentage) }}%</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">{{ __('reports.not_available') }}</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Meeting Timeline --}}
            @if(!empty($report->meeting_events) && is_array($report->meeting_events) && count($report->meeting_events) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <i class="ri-history-line text-sky-500"></i>
                            {{ __('reports.meeting_timeline') }}
                        </h2>
                    </div>
                    <div class="px-4 md:px-6 py-4">
                        <div class="relative">
                            <div class="absolute start-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                            <div class="space-y-4">
                                @foreach($report->meeting_events as $event)
                                    @php
                                        $isJoin = ($event['type'] ?? $event['event'] ?? '') === 'join' || ($event['type'] ?? $event['event'] ?? '') === 'joined';
                                        $eventTime = $event['time'] ?? $event['timestamp'] ?? $event['at'] ?? null;
                                    @endphp
                                    <div class="relative flex items-start gap-3 ps-10">
                                        <div class="absolute start-2.5 w-3 h-3 rounded-full border-2 border-white {{ $isJoin ? 'bg-emerald-500' : 'bg-rose-500' }}"></div>
                                        <div>
                                            <span class="text-sm font-medium {{ $isJoin ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $isJoin ? __('reports.join_event') : __('reports.leave_event') }}
                                            </span>
                                            @if($eventTime)
                                                <span class="text-xs text-gray-500 ms-2">
                                                    {{ toAcademyTimezone(\Carbon\Carbon::parse($eventTime))->format('h:i:s A') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Side Column (1/3) --}}
        <div class="space-y-4 md:space-y-6">

            {{-- Evaluation Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-star-line text-amber-500"></i>
                        {{ __('reports.evaluation') }}
                    </h2>
                </div>
                <div class="px-4 md:px-6 py-4 space-y-4">
                    {{-- Performance Level Badge --}}
                    @if($performanceLevel)
                        <div class="text-center p-3 rounded-lg border {{ $performanceClass }}">
                            <p class="text-lg font-bold">{{ $performanceLevelLabel }}</p>
                            @if($performanceScore !== null)
                                <p class="text-2xl font-black mt-1">{{ $performanceScore }}{{ __('reports.out_of_ten') }}</p>
                            @endif
                        </div>
                    @else
                        <div class="text-center p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <p class="text-sm text-gray-400">{{ __('reports.not_available') }}</p>
                        </div>
                    @endif

                    {{-- Type-specific scores --}}
                    @if($isQuran)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">{{ __('reports.memorization_degree') }}</span>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ $report->new_memorization_degree !== null ? $report->new_memorization_degree . __('reports.out_of_ten') : __('reports.not_available') }}
                                </span>
                            </div>
                            @if($report->new_memorization_degree !== null)
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min(100, ($report->new_memorization_degree / 10) * 100) }}%"></div>
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">{{ __('reports.revision_degree') }}</span>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ $report->reservation_degree !== null ? $report->reservation_degree . __('reports.out_of_ten') : __('reports.not_available') }}
                                </span>
                            </div>
                            @if($report->reservation_degree !== null)
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-teal-500" style="width: {{ min(100, ($report->reservation_degree / 10) * 100) }}%"></div>
                                </div>
                            @endif
                        </div>
                    @elseif($isAcademic || $isInteractive)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">{{ __('reports.homework_degree') }}</span>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ $report->homework_degree !== null ? $report->homework_degree . __('reports.out_of_ten') : __('reports.not_available') }}
                                </span>
                            </div>
                            @if($report->homework_degree !== null)
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-violet-500" style="width: {{ min(100, ($report->homework_degree / 10) * 100) }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Evaluation metadata --}}
                    <div class="pt-3 border-t border-gray-100 space-y-3">
                        @if($evaluatedAtFormatted)
                            <div class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-100 rounded-lg">
                                <i class="ri-calendar-check-line text-indigo-500"></i>
                                <div>
                                    <p class="text-xs text-indigo-500">{{ __('reports.evaluated_at') }}</p>
                                    <p class="text-sm font-medium text-indigo-700">{{ $evaluatedAtFormatted }}</p>
                                </div>
                            </div>
                        @endif
                        @if($report->manually_evaluated)
                            <div class="flex items-center gap-2 px-3 py-2 bg-sky-50 border border-sky-100 rounded-lg">
                                <i class="ri-edit-2-line text-sky-500"></i>
                                <span class="text-sm font-medium text-sky-700">{{ __('reports.manually_evaluated') }}</span>
                            </div>
                        @elseif($report->is_calculated)
                            <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg">
                                <i class="ri-robot-2-line text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-600">{{ __('reports.auto_calculated') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Teacher Notes Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-sticky-note-line text-orange-500"></i>
                        {{ __('reports.teacher_notes') }}
                    </h2>
                </div>
                <div class="px-4 md:px-6 py-4">
                    @if($report->notes)
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $report->notes }}</p>
                    @else
                        <p class="text-sm text-gray-400 italic">{{ __('reports.no_notes') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-reports.layouts.base-report>
