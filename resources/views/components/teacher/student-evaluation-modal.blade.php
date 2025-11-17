@props(['session'])

<!-- Student Evaluation Modal -->
<div id="studentEvaluationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 id="evaluationModalTitle" class="text-xl font-bold text-gray-900">تقييم الطالب</h3>
                <button id="closeEvaluationModalBtn" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <form id="studentEvaluationForm" class="space-y-6">
                @csrf
                <input type="hidden" id="evalStudentId" name="student_id">
                <input type="hidden" id="evalReportId" name="report_id">

                <!-- Student Info Display -->
                <div id="studentInfoDisplay" class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div id="studentAvatarDisplay"></div>
                        <div>
                            <h4 id="studentNameDisplay" class="font-semibold text-gray-900"></h4>
                            <p id="attendanceInfoDisplay" class="text-sm text-gray-600"></p>
                        </div>
                    </div>
                </div>

                <!-- Performance Degrees -->
                <div id="homeworkDegreeFields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="newMemorizationDegreeField" class="hidden">
                        <label for="newMemorizationDegree" class="block text-sm font-medium text-gray-700 mb-2">
                            درجة الحفظ الجديد (0-10)
                        </label>
                        <input type="number"
                               id="newMemorizationDegree"
                               name="new_memorization_degree"
                               min="0"
                               max="10"
                               step="0.5"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div id="reviewDegreeField" class="hidden">
                        <label for="reservationDegree" class="block text-sm font-medium text-gray-700 mb-2">
                            درجة المراجعة (0-10)
                        </label>
                        <input type="number"
                               id="reservationDegree"
                               name="reservation_degree"
                               min="0"
                               max="10"
                               step="0.5"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Attendance Status Override -->
                <div>
                    <label for="attendanceStatus" class="block text-sm font-medium text-gray-700 mb-2">
                        حالة الحضور (يدوي)
                    </label>
                    <select id="attendanceStatus"
                            name="attendance_status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">إبقاء الحالة المحسوبة تلقائياً</option>
                        @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">اتركها فارغة للاحتفاظ بالحالة المحسوبة تلقائياً</p>
                </div>

                <!-- Notes -->
                <div>
                    <label for="evaluationNotes" class="block text-sm font-medium text-gray-700 mb-2">
                        ملاحظات التقييم
                    </label>
                    <textarea id="evaluationNotes"
                              name="notes"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="أضف ملاحظاتك حول أداء الطالب..."></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 space-x-reverse pt-6 border-t border-gray-200">
                    <button type="button" id="cancelEvaluationBtn"
                            class="px-6 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors font-medium">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                        حفظ التقييم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Student management functions
    function changeStudentStatus(studentId) {
        showNotification('تغيير حالة الطالب قيد التطوير', 'info');
        console.log('Change status for student:', studentId);
    }

    // Student evaluation modal functions
    function editStudentReport(studentId, reportId) {
        const modal = document.getElementById('studentEvaluationModal');
        const form = document.getElementById('studentEvaluationForm');

        // Set hidden fields
        document.getElementById('evalStudentId').value = studentId;
        document.getElementById('evalReportId').value = reportId || '';

        // Reset form
        form.reset();
        document.getElementById('evalStudentId').value = studentId;
        document.getElementById('evalReportId').value = reportId || '';

        // Load current data if editing existing report
        if (reportId && reportId !== 'null') {
            loadExistingReportData(reportId);
        } else {
            // Load student basic info for new report
            loadStudentBasicInfo(studentId);
        }

        // Show modal
        modal.classList.remove('hidden');
    }

    function loadExistingReportData(reportId) {
        fetch(`{{ url('/') }}/teacher/student-reports/${reportId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const report = data.report;

                    // Store report data globally for attendance status listener
                    window.currentReportData = report;

                    // Fill form fields
                    document.getElementById('newMemorizationDegree').value = report.new_memorization_degree || '';
                    document.getElementById('reservationDegree').value = report.reservation_degree || '';
                    document.getElementById('evaluationNotes').value = report.notes || '';

                    // Set attendance status if manually set
                    if (report.manually_evaluated) {
                        document.getElementById('attendanceStatus').value = report.attendance_status || '';
                    } else {
                        document.getElementById('attendanceStatus').value = '';
                    }

                    // Update student info display
                    updateStudentInfoDisplay(report.student, report);

                    // Update auto-calculated attendance info
                    updateAutoAttendanceInfo(report);

                    // Load homework fields for existing report
                    loadHomeworkFields();
                }
            })
            .catch(error => {
                console.error('Error loading report:', error);
                showNotification('خطأ في تحميل بيانات التقرير', 'error');
            });
    }

    function loadStudentBasicInfo(studentId) {
        fetch(`{{ url('/') }}/teacher/students/${studentId}/basic-info`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStudentInfoDisplay(data.student, null);
                    // Load homework data to show conditional fields
                    loadHomeworkFields();
                }
            })
            .catch(error => {
                console.error('Error loading student info:', error);
            });
    }

    function loadHomeworkFields() {
        fetch(`{{ url('/') }}/teacher/sessions/{{ $session->id }}/homework`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.homework) {
                    const homework = data.homework;

                    // Show/hide fields based on homework configuration
                    const newMemField = document.getElementById('newMemorizationDegreeField');
                    const reviewField = document.getElementById('reviewDegreeField');

                    if (homework.has_new_memorization) {
                        newMemField.classList.remove('hidden');
                    } else {
                        newMemField.classList.add('hidden');
                    }

                    if (homework.has_review) {
                        reviewField.classList.remove('hidden');
                    } else {
                        reviewField.classList.add('hidden');
                    }
                } else {
                    // No homework, hide all degree fields
                    document.getElementById('newMemorizationDegreeField').classList.add('hidden');
                    document.getElementById('reviewDegreeField').classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error loading homework fields:', error);
                // On error, hide fields
                document.getElementById('newMemorizationDegreeField').classList.add('hidden');
                document.getElementById('reviewDegreeField').classList.add('hidden');
            });
    }

    function updateStudentInfoDisplay(student, report = null) {
        document.getElementById('studentNameDisplay').textContent = student.name;

        // Update avatar
        const avatarDiv = document.getElementById('studentAvatarDisplay');
        avatarDiv.innerHTML = `
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                ${student.name.charAt(0)}
            </div>
        `;

        // Update attendance info
        let attendanceText = 'بدون معلومات حضور';
        if (report) {
            attendanceText = `الحضور: ${getAttendanceStatusArabic(report.attendance_status)}`;
            if (report.attendance_percentage) {
                attendanceText += ` (${Math.round(report.attendance_percentage)}%)`;
            }
        }
        document.getElementById('attendanceInfoDisplay').textContent = attendanceText;
    }

    function updateAutoAttendanceInfo(report) {
        const autoEnterTime = document.getElementById('autoEnterTime');
        const autoLeaveTime = document.getElementById('autoLeaveTime');
        const autoAttendanceMinutes = document.getElementById('autoAttendanceMinutes');
        const autoAttendancePercentage = document.getElementById('autoAttendancePercentage');

        if (autoEnterTime) {
            autoEnterTime.textContent = report.meeting_enter_time ? new Date(report.meeting_enter_time).toLocaleTimeString('ar-SA') : '-';
        }
        if (autoLeaveTime) {
            autoLeaveTime.textContent = report.meeting_leave_time ? new Date(report.meeting_leave_time).toLocaleTimeString('ar-SA') : '-';
        }
        if (autoAttendanceMinutes) {
            autoAttendanceMinutes.textContent = report.actual_attendance_minutes || '-';
        }
        if (autoAttendancePercentage) {
            autoAttendancePercentage.textContent = report.attendance_percentage ? Math.round(report.attendance_percentage) : '-';
        }
    }

    function getAttendanceStatusArabic(status) {
        const statusMap = {
            'attended': 'حاضر',
            'late': 'متأخر',
            'leaved': 'غادر مبكراً',
            'absent': 'غائب'
        };
        return statusMap[status] || 'غير محدد';
    }

    // Modal event handlers
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('studentEvaluationModal');
        const closeBtn = document.getElementById('closeEvaluationModalBtn');
        const cancelBtn = document.getElementById('cancelEvaluationBtn');
        const form = document.getElementById('studentEvaluationForm');

        // Close modal events
        closeBtn?.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        cancelBtn?.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        // Close on backdrop click
        modal?.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        // Attendance status change listener
        const attendanceStatusSelect = document.getElementById('attendanceStatus');
        attendanceStatusSelect?.addEventListener('change', function(e) {
            const selectedStatus = e.target.value;
            const attendanceInfoDisplay = document.getElementById('attendanceInfoDisplay');

            if (attendanceInfoDisplay) {
                if (selectedStatus) {
                    attendanceInfoDisplay.textContent = `الحضور: ${getAttendanceStatusArabic(selectedStatus)} (يدوي)`;
                } else {
                    const currentStudentId = document.getElementById('studentId')?.value;
                    if (window.currentReportData && window.currentReportData.attendance_status) {
                        attendanceInfoDisplay.textContent = `الحضور: ${getAttendanceStatusArabic(window.currentReportData.attendance_status)}`;
                        if (window.currentReportData.attendance_percentage) {
                            attendanceInfoDisplay.textContent += ` (${Math.round(window.currentReportData.attendance_percentage)}%)`;
                        }
                    } else {
                        attendanceInfoDisplay.textContent = 'الحضور: بدون معلومات حضور';
                    }
                }
            }
        });

        // Form submission
        form?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch(`{{ url('/') }}/teacher/student-reports/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        ...data,
                        session_id: {{ $session->id }}
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('تم حفظ التقييم بنجاح', 'success');
                    modal.classList.add('hidden');

                    // Reload page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(result.message || 'خطأ في حفظ التقييم', 'error');
                }
            } catch (error) {
                console.error('Error saving evaluation:', error);
                showNotification('خطأ في حفظ التقييم', 'error');
            }
        });
    });

    function viewStudentReport(studentId) {
        showNotification('تقرير الطالب قيد التطوير', 'info');
        console.log('View report for student:', studentId);
    }

    function messageStudent(studentId) {
        const subdomain = '{{ request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy" }}';
        const chatUrl = '/chat?user=' + studentId;
        window.location.href = chatUrl;
    }

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;

        const colors = {
            info: 'bg-blue-500 text-white',
            success: 'bg-green-500 text-white',
            warning: 'bg-yellow-500 text-white',
            error: 'bg-red-500 text-white'
        };

        notification.className += ` ${colors[type] || colors.info}`;
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="ri-information-line"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white opacity-70 hover:opacity-100">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
</script>
