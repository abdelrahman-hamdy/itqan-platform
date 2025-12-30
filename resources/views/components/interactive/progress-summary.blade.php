@props(['courseId', 'studentId'])

@php
    // Calculate progress from session reports (replacing InteractiveCourseProgressService)
    $course = \App\Models\InteractiveCourse::find($courseId);
    $sessions = $course ? $course->sessions()->orderBy('scheduled_at')->get() : collect();
    $totalSessions = $sessions->count();

    // Get attendance records
    $attendanceRecords = \App\Models\InteractiveSessionAttendance::whereIn('session_id', $sessions->pluck('id'))
        ->where('student_id', $studentId)
        ->get();

    $sessionsAttended = $attendanceRecords->whereIn('attendance_status', ['attended', 'late'])->count();
    $completedSessions = $sessions->where('status', 'completed')->count();

    // Get session reports for homework data
    $sessionReports = \App\Models\InteractiveSessionReport::whereIn('session_id', $sessions->pluck('id'))
        ->where('student_id', $studentId)
        ->get();

    // Calculate metrics
    $attendanceRate = $totalSessions > 0 ? round(($sessionsAttended / $totalSessions) * 100) : 0;
    $completionPercentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

    // Homework metrics
    $totalHomework = $sessions->where('homework_description', '!=', null)->count();
    $homeworkSubmitted = $sessionReports->whereNotNull('homework_submitted_at')->count();
    $homeworkCompletionRate = $totalHomework > 0 ? round(($homeworkSubmitted / $totalHomework) * 100) : 0;
    $gradedHomework = $sessionReports->whereNotNull('homework_degree')->count();
    $averageGrade = $gradedHomework > 0 ? round($sessionReports->whereNotNull('homework_degree')->avg('homework_degree') * 10) : null;

    $progress = [
        'completion_percentage' => $completionPercentage,
        'attendance_rate' => $attendanceRate,
        'sessions_attended' => $sessionsAttended,
        'completed_sessions' => $completedSessions,
        'total_sessions' => $totalSessions,
        'total_homework' => $totalHomework,
        'homework_submitted' => $homeworkSubmitted,
        'homework_completion_rate' => $homeworkCompletionRate,
        'graded_homework' => $gradedHomework,
        'average_grade' => $averageGrade,
    ];

    // Color helpers
    $progressColor = $completionPercentage >= 75 ? 'green' : ($completionPercentage >= 50 ? 'yellow' : 'red');
    $attendanceColor = $attendanceRate >= 80 ? 'green' : ($attendanceRate >= 60 ? 'yellow' : 'red');
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-lg text-gray-900 mb-6 flex items-center">
        <i class="ri-bar-chart-line text-primary-600 me-2 rtl:me-2 ltr:ms-2"></i>
        {{ __('components.interactive.progress_summary.title') }}
    </h3>

    {{-- Overall Completion Circle --}}
    <div class="mb-6">
        <div class="flex justify-between text-sm mb-3">
            <span class="font-medium text-gray-700">{{ __('components.interactive.progress_summary.overall_completion') }}</span>
            <span class="font-bold text-{{ $progressColor }}-600">{{ $progress['completion_percentage'] }}%</span>
        </div>

        <div class="relative w-32 h-32 mx-auto">
            <svg class="transform -rotate-90 w-32 h-32" viewBox="0 0 100 100">
                {{-- Background Circle --}}
                <circle
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-width="8"
                />
                {{-- Progress Circle --}}
                <circle
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke="{{ $progressColor === 'green' ? '#10b981' : ($progressColor === 'yellow' ? '#f59e0b' : '#ef4444') }}"
                    stroke-width="8"
                    stroke-dasharray="{{ 2 * 3.14159 * 45 }}"
                    stroke-dashoffset="{{ 2 * 3.14159 * 45 * (1 - $progress['completion_percentage'] / 100) }}"
                    stroke-linecap="round"
                    class="transition-all duration-1000"
                />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <span class="text-2xl font-bold text-gray-900">{{ $progress['completion_percentage'] }}%</span>
                    <p class="text-xs text-gray-500 mt-1">{{ __('components.interactive.progress_summary.completed') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Details --}}
    <div class="space-y-3">
        {{-- Sessions Progress --}}
        <div class="p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <i class="ri-calendar-check-line text-blue-600 text-xl me-2 rtl:me-2 ltr:ms-2"></i>
                    <span class="text-sm font-medium text-gray-700">{{ __('components.interactive.progress_summary.completed_sessions') }}</span>
                </div>
                <span class="font-bold text-blue-600">
                    {{ $progress['completed_sessions'] }}/{{ $progress['total_sessions'] }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                    style="width: {{ $progress['completion_percentage'] }}%"
                ></div>
            </div>
        </div>

        {{-- Attendance --}}
        <div class="p-3 bg-{{ $attendanceColor }}-50 rounded-lg border border-{{ $attendanceColor }}-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="ri-user-follow-line text-{{ $attendanceColor }}-600 text-xl me-2 rtl:me-2 ltr:ms-2"></i>
                    <span class="text-sm font-medium text-gray-700">{{ __('components.interactive.progress_summary.attendance_rate') }}</span>
                </div>
                <div class="text-end rtl:text-end ltr:text-start">
                    <span class="font-bold text-{{ $attendanceColor }}-600 block">
                        {{ $progress['attendance_rate'] }}%
                    </span>
                    <span class="text-xs text-gray-600">
                        ({{ $progress['sessions_attended'] }}/{{ $progress['total_sessions'] }})
                    </span>
                </div>
            </div>
        </div>

        {{-- Homework --}}
        @if($progress['total_homework'] > 0)
            <div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="ri-file-text-line text-purple-600 text-xl me-2 rtl:me-2 ltr:ms-2"></i>
                        <span class="text-sm font-medium text-gray-700">{{ __('components.interactive.progress_summary.homework') }}</span>
                    </div>
                    <div class="text-end rtl:text-end ltr:text-start">
                        <span class="font-bold text-purple-600 block">
                            {{ $progress['homework_submitted'] }}/{{ $progress['total_homework'] }}
                        </span>
                        <span class="text-xs text-gray-600">
                            ({{ $progress['homework_completion_rate'] }}%)
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Average Grade --}}
        @if($progress['average_grade'] !== null)
            <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="ri-trophy-line text-green-600 text-xl me-2 rtl:me-2 ltr:ms-2"></i>
                        <span class="text-sm font-medium text-gray-700">{{ __('components.interactive.progress_summary.average_grade') }}</span>
                    </div>
                    <div class="text-end rtl:text-end ltr:text-start">
                        <span class="font-bold text-green-600 text-lg">
                            {{ $progress['average_grade'] }}
                        </span>
                        <span class="text-xs text-gray-600">/100</span>
                    </div>
                </div>
                @if($progress['graded_homework'] > 0)
                    <p class="text-xs text-gray-600 mt-1 text-start rtl:text-start ltr:text-end">
                        {{ str_replace('{count}', $progress['graded_homework'], __('components.interactive.progress_summary.from_graded')) }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Motivational Message --}}
    <div class="mt-4 pt-4 border-t border-gray-200">
        @if($progress['completion_percentage'] >= 80)
            <div class="flex items-center text-green-600">
                <i class="ri-emotion-happy-line text-2xl me-2 rtl:me-2 ltr:ms-2"></i>
                <span class="text-sm font-medium">{{ __('components.interactive.progress_summary.message_excellent') }}</span>
            </div>
        @elseif($progress['completion_percentage'] >= 50)
            <div class="flex items-center text-yellow-600">
                <i class="ri-emotion-normal-line text-2xl me-2 rtl:me-2 ltr:ms-2"></i>
                <span class="text-sm font-medium">{{ __('components.interactive.progress_summary.message_good') }}</span>
            </div>
        @else
            <div class="flex items-center text-blue-600">
                <i class="ri-emotion-line text-2xl me-2 rtl:me-2 ltr:ms-2"></i>
                <span class="text-sm font-medium">{{ __('components.interactive.progress_summary.message_start') }}</span>
            </div>
        @endif
    </div>
</div>
