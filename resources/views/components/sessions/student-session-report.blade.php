@props([
    'report',
    'sessionType' => 'quran', // quran, academic, interactive
])

@php
    $t = 'components.sessions.student_report';

    $hasGrades = false;
    if ($report) {
        if ($sessionType === 'quran') {
            $hasGrades = $report->new_memorization_degree !== null || $report->reservation_degree !== null;
        } else {
            $hasGrades = $report->homework_degree !== null;
        }
    }

    // Resolve the attendance status as an enum instance (model may cast it already)
    $statusEnum = $report?->attendance_status instanceof \App\Enums\AttendanceStatus
        ? $report->attendance_status
        : ($report?->attendance_status ? \App\Enums\AttendanceStatus::tryFrom($report->attendance_status) : null);

    // Degree → badge color (3-level scale: ≥8 green, ≥6 yellow, <6 red)
    $degreeClass = fn (?float $degree) => match (true) {
        $degree === null => '',
        $degree >= 8    => 'bg-green-100 text-green-800',
        $degree >= 6    => 'bg-yellow-100 text-yellow-800',
        default         => 'bg-red-100 text-red-800',
    };

    $showAttendanceStats = $report && (($report->actual_attendance_minutes ?? 0) > 0 || ($report->attendance_percentage ?? 0) > 0);
@endphp

<div class="bg-white rounded-xl shadow-sm border border-emerald-100 p-6">
    {{-- Header --}}
    <h2 class="text-xl font-bold text-gray-900 mb-5 flex items-center gap-2">
        <span class="flex items-center justify-center w-9 h-9 bg-emerald-100 rounded-lg flex-shrink-0">
            <i class="ri-file-list-3-line text-emerald-600"></i>
        </span>
        {{ __("$t.title") }}
    </h2>

    {{-- Empty state: no report --}}
    @if (! $report)
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="ri-time-line text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 font-medium">{{ __("$t.no_report") }}</p>
        </div>
    @else
        <div class="space-y-5">

            {{-- Attendance status row --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-600">{{ __("$t.attendance_status") }}:</span>

                @if ($statusEnum)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 {{ $statusEnum->badgeClass() }} rounded-full text-sm font-semibold">
                        <i class="{{ $statusEnum->icon() }}"></i>
                        {{ $statusEnum->label() }}
                    </span>
                @endif

                {{-- Pending evaluation tag --}}
                @if (! $report->evaluated_at && ! $hasGrades)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                        <i class="ri-hourglass-line"></i>
                        {{ __("$t.pending_evaluation") }}
                    </span>
                @endif
            </div>

            {{-- Attendance stats --}}
            @if ($showAttendanceStats)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @if (($report->actual_attendance_minutes ?? 0) > 0)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="ri-time-line text-purple-500"></i>
                                <span class="text-sm text-gray-700">{{ __("$t.attendance_duration") }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ $report->actual_attendance_minutes }} {{ __("$t.minutes") }}
                            </span>
                        </div>
                    @endif
                    @if (($report->attendance_percentage ?? 0) > 0)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i class="ri-percent-line text-indigo-500"></i>
                                <span class="text-sm text-gray-700">{{ __("$t.attendance_percentage") }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ round($report->attendance_percentage) }}%
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Grades section --}}
            @if ($hasGrades)
                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __("$t.grades_section") }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if ($sessionType === 'quran')
                            @if ($report->new_memorization_degree !== null)
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-book-open-line text-emerald-500"></i>
                                        <span class="text-sm text-gray-700">{{ __("$t.memorization_degree") }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $degreeClass($report->new_memorization_degree) }}">
                                        {{ $report->new_memorization_degree }}{{ __("$t.out_of_ten") }}
                                    </span>
                                </div>
                            @endif
                            @if ($report->reservation_degree !== null)
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-refresh-line text-blue-500"></i>
                                        <span class="text-sm text-gray-700">{{ __("$t.review_degree") }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $degreeClass($report->reservation_degree) }}">
                                        {{ $report->reservation_degree }}{{ __("$t.out_of_ten") }}
                                    </span>
                                </div>
                            @endif
                        @else
                            {{-- Academic / Interactive --}}
                            @if ($report->homework_degree !== null)
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-file-list-line text-purple-500"></i>
                                        <span class="text-sm text-gray-700">{{ __("$t.homework_degree") }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $degreeClass($report->homework_degree) }}">
                                        {{ $report->homework_degree }}{{ __("$t.out_of_ten") }}
                                    </span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            {{-- Teacher notes --}}
            @if ($report->notes)
                <div class="border-t border-gray-100 pt-4">
                    <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                        <div class="flex items-start gap-3">
                            <i class="ri-sticky-note-line text-amber-600 mt-0.5 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-amber-800 mb-1">{{ __("$t.teacher_notes") }}</h4>
                                <p class="text-sm text-amber-900 leading-relaxed">{{ $report->notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Evaluated at timestamp --}}
            @if ($report->evaluated_at)
                <div class="flex justify-end pt-1">
                    <span class="text-xs text-gray-400">
                        <i class="ri-check-double-line text-emerald-500 me-1"></i>
                        {{ __("$t.evaluated_at") }}: {{ toAcademyTimezone($report->evaluated_at)->format('Y-m-d H:i') }}
                    </span>
                </div>
            @endif

        </div>
    @endif
</div>
