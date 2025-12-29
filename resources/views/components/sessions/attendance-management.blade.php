@props([
    'session',
    'students',
    'viewType' => 'teacher'
])

@php
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
@endphp

<!-- Attendance Management Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">إدارة حضور الطلاب</h3>
        @if($session->status === SessionStatus::ONGOING || $session->status === SessionStatus::COMPLETED)
            <div class="flex items-center space-x-2 space-x-reverse">
                <button id="markAllPresentBtn" 
                        class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                    <i class="ri-check-double-line ml-1"></i>
                    حاضر الكل
                </button>
                <button id="markAllAbsentBtn" 
                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors">
                    <i class="ri-close-circle-line ml-1"></i>
                    غائب الكل
                </button>
            </div>
        @endif
    </div>
    
    @if($students && $students->count() > 0)
        <div class="space-y-4">
            @foreach($students as $student)
                @php
                    $attendance = $session->attendances->where('student_id', $student->id)->first();
                    $attendanceStatus = $attendance ? $attendance->attendance_status : AttendanceStatus::ABSENT->value;
                @endphp
                
                <div class="border border-gray-200 rounded-lg p-4 student-attendance-row" data-student-id="{{ $student->id }}">
                    <div class="flex items-center justify-between">
                        <!-- Student Info -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <x-avatar
                                :user="$student"
                                size="md"
                                userType="student"
                                :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $student->name }}</h4>
                                <p class="text-sm text-gray-600">{{ $student->email ?? 'لا يوجد بريد إلكتروني' }}</p>
                            </div>
                        </div>
                        
                        <!-- Attendance Status -->
                        <div class="flex items-center space-x-4 space-x-reverse">
                            @if($session->status === SessionStatus::ONGOING || $session->status === SessionStatus::COMPLETED)
                                <!-- Attendance Options -->
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <label class="flex items-center">
                                        <input type="radio" name="attendance_{{ $student->id }}" value="{{ AttendanceStatus::ATTENDED->value }}"
                                               class="text-green-600 focus:ring-green-500 attendance-radio"
                                               {{ $attendanceStatus === AttendanceStatus::ATTENDED->value ? 'checked' : '' }}
                                               data-student-id="{{ $student->id }}">
                                        <span class="mr-2 text-sm font-medium text-green-600">حاضر</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input type="radio" name="attendance_{{ $student->id }}" value="{{ AttendanceStatus::LATE->value }}"
                                               class="text-yellow-600 focus:ring-yellow-500 attendance-radio"
                                               {{ $attendanceStatus === AttendanceStatus::LATE->value ? 'checked' : '' }}
                                               data-student-id="{{ $student->id }}">
                                        <span class="mr-2 text-sm font-medium text-yellow-600">متأخر</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input type="radio" name="attendance_{{ $student->id }}" value="{{ AttendanceStatus::ABSENT->value }}"
                                               class="text-red-600 focus:ring-red-500 attendance-radio"
                                               {{ $attendanceStatus === AttendanceStatus::ABSENT->value ? 'checked' : '' }}
                                               data-student-id="{{ $student->id }}">
                                        <span class="mr-2 text-sm font-medium text-red-600">غائب</span>
                                    </label>
                                </div>
                            @else
                                <!-- Display Status for Scheduled Sessions -->
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ $attendanceStatus === AttendanceStatus::ATTENDED->value ? 'bg-green-100 text-green-800' :
                                       ($attendanceStatus === AttendanceStatus::LATE->value ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $attendanceStatus === AttendanceStatus::ATTENDED->value ? 'حاضر' : ($attendanceStatus === AttendanceStatus::LATE->value ? 'متأخر' : 'غير محدد') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Expanded Details (shown for ongoing/completed sessions) -->
                    @if(($session->status === SessionStatus::ONGOING->value || $session->status === SessionStatus::COMPLETED->value) && $attendanceStatus !== AttendanceStatus::ABSENT->value)
                        <div class="mt-4 pt-4 border-t border-gray-200 attendance-details" style="{{ $attendanceStatus === AttendanceStatus::ABSENT->value ? 'display: none;' : '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Join/Leave Times -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">وقت الدخول</label>
                                    <input type="time" name="join_time_{{ $student->id }}" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           value="{{ $attendance && $attendance->join_time ? $attendance->join_time->format('H:i') : '' }}">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">وقت الخروج</label>
                                    <input type="time" name="leave_time_{{ $student->id }}" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           value="{{ $attendance && $attendance->leave_time ? $attendance->leave_time->format('H:i') : '' }}">
                                </div>
                                
                                <!-- Participation Score -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">درجة المشاركة (0-10)</label>
                                    <input type="number" name="participation_score_{{ $student->id }}" min="0" max="10" step="0.1"
                                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           value="{{ $attendance ? $attendance->participation_score : '' }}">
                                </div>
                                
                                <!-- Homework Completion -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">إكمال الواجب</label>
                                    <select name="homework_completion_{{ $student->id }}" 
                                            class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="">غير محدد</option>
                                        <option value="1" {{ $attendance && $attendance->homework_completion ? 'selected' : '' }}>مكتمل</option>
                                        <option value="0" {{ $attendance && !$attendance->homework_completion ? 'selected' : '' }}>غير مكتمل</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Additional Quranic Details -->
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">الآيات المراجعة</label>
                                <input type="number" name="verses_reviewed_{{ $student->id }}" min="0"
                                       class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                       value="{{ $attendance ? $attendance->verses_reviewed : '' }}">
                            </div>

                            <!-- Notes -->
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">ملاحظات خاصة بالطالب</label>
                                <textarea name="notes_{{ $student->id }}" rows="2" 
                                          class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                          placeholder="ملاحظات حول أداء الطالب في هذه الجلسة">{{ $attendance ? $attendance->notes : '' }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($session->status === SessionStatus::ONGOING || $session->status === SessionStatus::COMPLETED)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span id="attendanceStats">
                            حاضر: <span class="font-medium text-green-600">{{ $session->attendances->where('attendance_status', AttendanceStatus::ATTENDED->value)->count() }}</span> |
                            متأخر: <span class="font-medium text-yellow-600">{{ $session->attendances->where('attendance_status', AttendanceStatus::LATE->value)->count() }}</span> |
                            غائب: <span class="font-medium text-red-600">{{ $session->attendances->where('attendance_status', AttendanceStatus::ABSENT->value)->count() }}</span>
                        </span>
                    </div>
                    
                    <button id="saveAttendanceBtn" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="ri-save-line ml-1"></i>
                        حفظ بيانات الحضور
                    </button>
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-group-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">لا يوجد طلاب مسجلين</h3>
            <p class="text-gray-600">لم يتم تسجيل أي طلاب في هذه الجلسة.</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const markAllPresentBtn = document.getElementById('markAllPresentBtn');
    const markAllAbsentBtn = document.getElementById('markAllAbsentBtn');
    const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');
    const attendanceRadios = document.querySelectorAll('.attendance-radio');
    
    // Mark all present
    markAllPresentBtn?.addEventListener('click', function() {
        document.querySelectorAll('input[type="radio"][value="attended"]').forEach(radio => {
            radio.checked = true;
            toggleAttendanceDetails(radio);
        });
        updateAttendanceStats();
    });
    
    // Mark all absent
    markAllAbsentBtn?.addEventListener('click', function() {
        document.querySelectorAll('input[type="radio"][value="absent"]').forEach(radio => {
            radio.checked = true;
            toggleAttendanceDetails(radio);
        });
        updateAttendanceStats();
    });
    
    // Handle individual attendance changes
    attendanceRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            toggleAttendanceDetails(this);
            updateAttendanceStats();
        });
    });
    
    // Toggle attendance details based on status
    function toggleAttendanceDetails(radio) {
        const studentId = radio.dataset.studentId;
        const detailsDiv = document.querySelector(`[data-student-id="${studentId}"] .attendance-details`);
        
        if (detailsDiv) {
            if (radio.value === 'absent') {
                detailsDiv.style.display = 'none';
            } else {
                detailsDiv.style.display = 'block';
            }
        }
    }
    
    // Update attendance statistics
    function updateAttendanceStats() {
        const presentCount = document.querySelectorAll('input[type="radio"][value="attended"]:checked').length;
        const lateCount = document.querySelectorAll('input[type="radio"][value="late"]:checked').length;
        const absentCount = document.querySelectorAll('input[type="radio"][value="absent"]:checked').length;
        
        const statsElement = document.getElementById('attendanceStats');
        if (statsElement) {
            statsElement.innerHTML = `
                حاضر: <span class="font-medium text-green-600">${presentCount}</span> |
                متأخر: <span class="font-medium text-yellow-600">${lateCount}</span> |
                غائب: <span class="font-medium text-red-600">${absentCount}</span>
            `;
        }
    }
    
    // Save attendance data
    saveAttendanceBtn?.addEventListener('click', async function() {
        const attendanceData = [];
        
        // Collect attendance data for all students
        document.querySelectorAll('.student-attendance-row').forEach(row => {
            const studentId = row.dataset.studentId;
            const attendanceStatus = row.querySelector(`input[name="attendance_${studentId}"]:checked`)?.value || 'absent';
            
            const data = {
                student_id: studentId,
                attendance_status: attendanceStatus,
                join_time: row.querySelector(`input[name="join_time_${studentId}"]`)?.value || null,
                leave_time: row.querySelector(`input[name="leave_time_${studentId}"]`)?.value || null,
                participation_score: row.querySelector(`input[name="participation_score_${studentId}"]`)?.value || null,
                homework_completion: row.querySelector(`select[name="homework_completion_${studentId}"]`)?.value || null,
                verses_reviewed: row.querySelector(`input[name="verses_reviewed_${studentId}"]`)?.value || null,
                notes: row.querySelector(`textarea[name="notes_${studentId}"]`)?.value || null
            };
            
            attendanceData.push(data);
        });
        
        // Note: Backend routes not yet implemented
        showNotification('ميزة حفظ بيانات الحضور قيد التطوير', 'info');
    });
    
    // Initial setup
    updateAttendanceStats();
});

function showNotification(message, type = 'info', duration = 5000) {
    // Use unified toast system
    if (window.toast) {
        window.toast.show({ type: type, message: message, duration: duration });
    } else {
    }
}
</script>
@endpush 