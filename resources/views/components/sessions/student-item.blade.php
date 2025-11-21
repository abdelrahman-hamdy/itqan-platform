@props([
    'student',
    'session',
    'showChat' => true,
    'size' => 'sm'
])

@php
    use App\Enums\AttendanceStatus;

    // 🔥 FIX: Use new webhook-based attendance system with enum
    // Handle different session types - some may not have studentReports loaded
    $report = $session->studentReports ? $session->studentReports->where('student_id', $student->id)->first() : null;
    $meetingAttendance = $session->meetingAttendances ? $session->meetingAttendances->where('user_id', $student->id)->first() : null;

    // Determine attendance status:
    // 1. If session ended and report is calculated, use report status (most accurate)
    // 2. If session ongoing, show live status from MeetingAttendance
    // 3. Otherwise, show "not calculated yet"
    if ($report && $report->is_calculated) {
        // Session ended, use calculated report data
        $attendanceStatus = $report->attendance_status; // String value from enum
        $attendancePercentage = $report->attendance_percentage;
        $actualMinutes = $report->actual_attendance_minutes;
        $isCalculated = true;
    } elseif ($meetingAttendance && $meetingAttendance->first_join_time) {
        // Session ongoing or just ended, show live data
        if ($meetingAttendance->last_leave_time) {
            $attendanceStatus = 'in_meeting_left'; // Temporary status for live view
        } else {
            $attendanceStatus = 'in_meeting'; // Temporary status for live view
        }
        $attendancePercentage = null;
        $actualMinutes = $meetingAttendance->total_duration_minutes ?? 0;
        $isCalculated = false;
    } else {
        // No attendance data yet
        $attendanceStatus = $report ? AttendanceStatus::ABSENT->value : 'unknown';
        $attendancePercentage = null;
        $actualMinutes = null;
        $isCalculated = false;
    }

    // Get enum instance for display
    $statusEnum = null;
    if ($isCalculated && $attendanceStatus && $attendanceStatus !== 'unknown') {
        try {
            $statusEnum = AttendanceStatus::from($attendanceStatus);
        } catch (\ValueError $e) {
            $statusEnum = null;
        }
    }

    $homework = $session->homework ? $session->homework->first() : null;

    // Collect all info items
    $infoItems = [];

    // Check session type to determine which fields to display
    $isQuranSession = in_array(get_class($session), ['App\Models\QuranSession']);
    $isAcademicSession = in_array(get_class($session), ['App\Models\AcademicSession', 'App\Models\InteractiveCourseSession']);

    // Quran-specific fields
    if($isQuranSession && $report && $report->new_memorization_degree !== null) {
        $infoItems[] = [
            'icon' => 'ri-book-line text-green-600',
            'label' => 'درجة الحفظ',
            'value' => $report->new_memorization_degree . '/10',
            'badge_class' => 'bg-green-100 text-green-800'
        ];
    }

    if($isQuranSession && $report && $report->reservation_degree !== null) {
        $infoItems[] = [
            'icon' => 'ri-refresh-line text-blue-600',
            'label' => 'درجة المراجعة',
            'value' => $report->reservation_degree . '/10',
            'badge_class' => 'bg-blue-100 text-blue-800'
        ];
    }

    // Academic-specific fields (for AcademicSession and InteractiveCourseSession)
    if($isAcademicSession && $report && $report->homework_completion_degree !== null) {
        $infoItems[] = [
            'icon' => 'ri-file-list-line text-purple-600',
            'label' => 'درجة الواجب',
            'value' => $report->homework_completion_degree . '/10',
            'badge_class' => 'bg-purple-100 text-purple-800'
        ];
    }

    // 🔥 FIX: Show attendance minutes from calculated report or live data
    if($actualMinutes !== null) {
        $infoItems[] = [
            'icon' => 'ri-time-line text-purple-600',
            'label' => $isCalculated ? 'مدة الحضور' : 'مدة الحضور (مباشر)',
            'value' => $actualMinutes . ' دقيقة',
            'badge_class' => $isCalculated ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
        ];
    }

    // Show attendance percentage only if calculated
    if($isCalculated && $attendancePercentage !== null) {
        $infoItems[] = [
            'icon' => 'ri-percent-line text-indigo-600',
            'label' => 'نسبة الحضور',
            'value' => number_format($attendancePercentage, 0) . '%',
            'badge_class' => 'bg-indigo-100 text-indigo-800'
        ];
    }
    
    // Split items into two columns
    $leftColumn = [];
    $rightColumn = [];
    foreach($infoItems as $index => $item) {
        if($index % 2 === 0) {
            $leftColumn[] = $item;
        } else {
            $rightColumn[] = $item;
        }
    }
@endphp

<div class="border border-gray-200 rounded-lg p-4 transition-colors">
    <!-- Header with Avatar, Name, and Attendance Status -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            <!-- Student Avatar -->
            <x-avatar
                :user="$student"
                :size="$size"
                userType="student"
                :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
            
            <div>
                <h4 class="font-semibold text-gray-900 {{ $size === 'md' ? 'text-lg' : '' }}">{{ $student->name }}</h4>
            </div>
        </div>
        
        <!-- Attendance Status (floated to absolute left/right in RTL) -->
        <div class="flex items-center">
            @if($statusEnum)
                {{-- Calculated status using enum --}}
                <span class="inline-flex items-center px-3 py-1.5 {{ $statusEnum->badgeClass() }} rounded-full text-sm font-semibold">
                    <i class="{{ $statusEnum->icon() }} ml-1"></i>
                    {{ $statusEnum->label() }}
                    @if($attendancePercentage) ({{ number_format($attendancePercentage, 0) }}%)@endif
                </span>
            @elseif($attendanceStatus === 'in_meeting')
                {{-- Live status: Currently in meeting --}}
                <span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                    <i class="ri-check-line ml-1 animate-pulse"></i>
                    في الجلسة الآن
                </span>
            @elseif($attendanceStatus === 'in_meeting_left')
                {{-- Live status: Left the meeting --}}
                <span class="inline-flex items-center px-3 py-1.5 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
                    <i class="ri-logout-box-line ml-1"></i>
                    غادر الجلسة
                </span>
            @elseif((is_object($session->status) ? $session->status->value : $session->status) === 'completed' && !$isCalculated)
                {{-- Session completed but not calculated yet --}}
                <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-sm font-semibold">
                    <i class="ri-loader-4-line animate-spin ml-1"></i>
                    يتم الحساب...
                </span>
            @else
                {{-- Unknown/not joined --}}
                <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-sm font-semibold">
                    <i class="ri-question-line ml-1"></i>
                    لم ينضم
                </span>
            @endif
        </div>
    </div>
    
    <!-- Student Report Data -->
    @if($report && !empty($infoItems))
        <div class="bg-white border border-gray-300 rounded-lg mb-3 p-3">
            <!-- Live Data Indicator -->
            @if(!$isCalculated && $meetingAttendance)
                <div class="mb-3 pb-2 border-b border-blue-200 flex items-center gap-2 text-blue-700 text-xs">
                    <i class="ri-live-line animate-pulse"></i>
                    <span class="font-medium">بيانات مباشرة - سيتم الحساب النهائي بعد انتهاء الجلسة</span>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <!-- Left Column -->
                <div class="space-y-3">
                    @foreach($leftColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                
                <!-- Right Column -->
                <div class="space-y-3">
                    @foreach($rightColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            @if($report->notes)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-start">
                        <i class="ri-sticky-note-line text-amber-600 ml-2 mt-0.5"></i>
                        <div>
                            <span class="text-gray-600 text-xs font-medium">الملاحظات:</span>
                            <p class="text-gray-800 text-sm mt-1">{{ $report->notes }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @elseif(!empty($infoItems))
        <!-- Live data without report -->
        <div class="bg-white border border-blue-200 rounded-lg mb-3 p-3">
            <div class="mb-3 pb-2 border-b border-blue-200 flex items-center gap-2 text-blue-700 text-xs">
                <i class="ri-live-line animate-pulse"></i>
                <span class="font-medium">بيانات مباشرة - سيتم إنشاء التقرير بعد انتهاء الجلسة</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Left Column -->
                <div class="space-y-3">
                    @foreach($leftColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Right Column -->
                <div class="space-y-3">
                    @foreach($rightColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif($report && $report->is_calculated)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
            <div class="flex items-center text-gray-600 text-sm">
                <i class="ri-information-line ml-2"></i>
                <span>تم حساب الحضور - لم يتم إضافة تقييم للطالب بعد</span>
            </div>
        </div>
    @elseif((is_object($session->status) ? $session->status->value : $session->status) === 'completed')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
            <div class="flex items-center text-blue-700 text-sm">
                <i class="ri-loader-4-line animate-spin ml-2"></i>
                <span>جاري حساب الحضور...</span>
            </div>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
            <div class="flex items-center text-gray-600 text-sm">
                <i class="ri-information-line ml-2"></i>
                <span>في انتظار بدء الجلسة</span>
            </div>
        </div>
    @endif
    
    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-2">
        <button class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm" onclick="editStudentReport({{ $student->id }}, {{ $report?->id ?? 'null' }})">
            <i class="ri-edit-line ml-1"></i>
            {{ $report ? 'تعديل التقرير' : 'إنشاء تقرير' }}
        </button>
        
        @if($showChat)
        <button class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm" onclick="messageStudent({{ $student->id }})">
            <i class="ri-message-line ml-1"></i>
            رسالة
        </button>
        @endif
    </div>
</div>
