@props([
    'sessionType' => 'academic' // 'academic', 'quran', or 'interactive'
])

<!-- Student Report Edit Modal -->
<div id="reportEditModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="ri-edit-line text-primary ml-2"></i>
                <span id="modalTitle">تعديل تقرير الطالب</span>
            </h3>
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="reportEditForm" class="mt-6 space-y-6">
            @csrf
            <input type="hidden" id="report_id" name="report_id">
            <input type="hidden" id="session_id" name="session_id">
            <input type="hidden" id="student_id" name="student_id">
            <input type="hidden" id="report_type" name="report_type" value="{{ $sessionType }}">

            <!-- Student Info Display (Quran modal style with unified x-avatar component) -->
            <div id="studentInfoDisplay" class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <!-- Student Avatar Container (will be populated with cloned x-avatar) -->
                    <div id="modal_avatar_container" class="flex-shrink-0">
                        <!-- Pre-rendered avatars will be cloned here -->
                    </div>
                    <!-- Student Details -->
                    <div>
                        <h4 id="student_name" class="font-semibold text-gray-900">-</h4>
                        <p id="student_info_extra" class="text-sm text-gray-600">-</p>
                    </div>
                </div>
            </div>

            <!-- Attendance Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-check-line ml-1"></i>
                    حالة الحضور
                </label>

                <!-- Auto-calculated attendance info display -->
                <div id="auto_attendance_info" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <div class="flex items-center gap-2 text-blue-700 text-sm mb-2">
                        <i class="ri-information-line"></i>
                        <span class="font-medium">معلومات الحضور التلقائي:</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-600">الحالة:</span>
                            <span id="auto_status_display" class="font-medium text-gray-900"></span>
                        </div>
                        <div>
                            <span class="text-gray-600">النسبة:</span>
                            <span id="auto_percentage_display" class="font-medium text-gray-900"></span>
                        </div>
                        <div>
                            <span class="text-gray-600">المدة:</span>
                            <span id="auto_duration_display" class="font-medium text-gray-900"></span>
                        </div>
                    </div>
                </div>

                <select id="attendance_status" name="attendance_status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                    <option value="">إبقاء الحالة المحسوبة تلقائياً</option>
                    @php
                        use App\Enums\AttendanceStatus;
                        $statusOptions = AttendanceStatus::options();
                    @endphp
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">اتركها فارغة للاحتفاظ بالحالة المحسوبة تلقائياً من نظام الحضور الآلي</p>
            </div>

            <!-- Quran-specific fields -->
            <div id="quran_fields" class="space-y-4" style="display: none;">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ri-book-line ml-1"></i>
                            درجة الحفظ الجديد (0-10)
                        </label>
                        <input type="number" id="new_memorization_degree" name="new_memorization_degree"
                               min="0" max="10" step="0.5"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"
                               placeholder="0.0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ri-refresh-line ml-1"></i>
                            درجة المراجعة (0-10)
                        </label>
                        <input type="number" id="reservation_degree" name="reservation_degree"
                               min="0" max="10" step="0.5"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"
                               placeholder="0.0">
                    </div>
                </div>
            </div>

            <!-- Academic-specific fields - Simplified to only homework_degree -->
            <div id="academic_fields" class="space-y-4" style="display: none;">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-file-list-line ml-1"></i>
                        درجة الواجب (0-10)
                    </label>
                    <input type="number" id="homework_degree" name="homework_degree"
                           min="0" max="10" step="0.5"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"
                           placeholder="0.0">
                    <p class="text-xs text-gray-500 mt-1">تقييم جودة وإنجاز الواجب المنزلي</p>
                </div>
            </div>

            <!-- Interactive-specific fields - Unified with Academic (only homework_degree) -->
            <div id="interactive_fields" class="space-y-4" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="ri-file-list-line ml-1"></i>
                            درجة الواجب (0-10)
                        </label>
                        <input type="number" id="interactive_homework_degree" name="homework_degree"
                               min="0" max="10" step="0.5"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"
                               placeholder="0.0">
                        <p class="text-xs text-gray-500 mt-1">تقييم جودة وإنجاز الواجب المنزلي</p>
                    </div>
                </div>
            </div>

            <!-- Notes (Common for all types) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-sticky-note-line ml-1"></i>
                    ملاحظات المعلم
                </label>
                <textarea id="notes" name="notes" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"
                          placeholder="أضف ملاحظاتك على أداء الطالب..."></textarea>
            </div>

            <!-- Success/Error Messages -->
            <div id="modal_message" class="hidden"></div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeReportModal()"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-close-line ml-1"></i>
                    إلغاء
                </button>
                <button type="submit" id="save_report_btn"
                        class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
                    <i class="ri-save-line ml-1"></i>
                    حفظ التقرير
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Global functions for modal management
function openReportModal(sessionId, studentId, studentName, reportData = null, reportType = '{{ $sessionType }}', studentData = null) {
    const modal = document.getElementById('reportEditModal');
    const form = document.getElementById('reportEditForm');
    const modalTitle = document.getElementById('modalTitle');

    // Reset form
    form.reset();
    document.getElementById('modal_message').classList.add('hidden');

    // Set session and student data
    document.getElementById('session_id').value = sessionId;
    document.getElementById('student_id').value = studentId;
    document.getElementById('student_name').textContent = studentName;
    document.getElementById('report_type').value = reportType;

    // Update student avatar using pre-rendered x-avatar component
    const modalAvatarContainer = document.getElementById('modal_avatar_container');
    const studentInfoExtra = document.getElementById('student_info_extra');

    // Clear previous avatar
    modalAvatarContainer.innerHTML = '';

    // Clone pre-rendered avatar from hidden container (rendered by session detail page)
    const preRenderedAvatar = document.getElementById('prerendered_avatar_' + studentId);
    if (preRenderedAvatar) {
        const avatarClone = preRenderedAvatar.cloneNode(true);
        avatarClone.id = ''; // Remove ID from clone
        avatarClone.classList.remove('hidden');
        modalAvatarContainer.appendChild(avatarClone);
    }

    // Set extra student info (attendance info style from Quran modal)
    if (reportData && reportData.attendance_status) {
        const statusMap = {
            'attended': 'حاضر',
            'late': 'متأخر',
            'leaved': 'غادر مبكراً',
            'absent': 'غائب'
        };
        let infoText = 'الحضور: ' + (statusMap[reportData.attendance_status] || reportData.attendance_status);
        if (reportData.attendance_percentage !== null && reportData.attendance_percentage !== undefined) {
            infoText += ' (' + Math.round(reportData.attendance_percentage) + '%)';
        }
        studentInfoExtra.textContent = infoText;
    } else {
        studentInfoExtra.textContent = 'بدون معلومات حضور';
    }

    // Show/hide fields based on report type
    const quranFields = document.getElementById('quran_fields');
    const academicFields = document.getElementById('academic_fields');
    const interactiveFields = document.getElementById('interactive_fields');

    // Hide all type-specific fields first
    quranFields.style.display = 'none';
    academicFields.style.display = 'none';
    interactiveFields.style.display = 'none';

    if (reportType === 'quran') {
        quranFields.style.display = 'block';
        modalTitle.textContent = 'تعديل تقرير حلقة القرآن';
    } else if (reportType === 'interactive') {
        interactiveFields.style.display = 'block';
        modalTitle.textContent = 'تعديل تقرير الكورس التفاعلي';
    } else {
        academicFields.style.display = 'block';
        modalTitle.textContent = 'تعديل التقرير الأكاديمي';
    }

    // If editing existing report, populate data
    if (reportData) {
        document.getElementById('report_id').value = reportData.id || '';

        // Set attendance status - only if manually evaluated, otherwise keep empty (auto-calculated)
        if (reportData.manually_evaluated) {
            document.getElementById('attendance_status').value = reportData.attendance_status || '';
        } else {
            document.getElementById('attendance_status').value = ''; // Keep auto-calculated
        }

        // Display auto-calculated attendance info if available
        if (reportData.attendance_status && reportData.attendance_percentage !== undefined) {
            const autoInfoDiv = document.getElementById('auto_attendance_info');
            const statusMap = {
                'attended': 'حاضر',
                'late': 'متأخر',
                'leaved': 'غادر مبكراً',
                'absent': 'غائب'
            };

            document.getElementById('auto_status_display').textContent = statusMap[reportData.attendance_status] || reportData.attendance_status;
            document.getElementById('auto_percentage_display').textContent = Math.round(reportData.attendance_percentage) + '%';
            document.getElementById('auto_duration_display').textContent = (reportData.actual_attendance_minutes || 0) + ' دقيقة';
            autoInfoDiv.classList.remove('hidden');
        }

        document.getElementById('notes').value = reportData.notes || '';

        // Quran fields
        if (reportData.new_memorization_degree !== null && reportData.new_memorization_degree !== undefined) {
            document.getElementById('new_memorization_degree').value = reportData.new_memorization_degree;
        }
        if (reportData.reservation_degree !== null && reportData.reservation_degree !== undefined) {
            document.getElementById('reservation_degree').value = reportData.reservation_degree;
        }

        // Academic fields - Simplified to homework_degree only
        if (reportData.homework_degree !== null && reportData.homework_degree !== undefined) {
            document.getElementById('homework_degree').value = reportData.homework_degree;
            // Also populate interactive field if exists
            if (document.getElementById('interactive_homework_degree')) {
                document.getElementById('interactive_homework_degree').value = reportData.homework_degree;
            }
        }
    } else {
        // Hide auto-attendance info for new reports
        document.getElementById('auto_attendance_info').classList.add('hidden');
    }

    // Show modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportEditModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReportModal();
    }
});

// Close modal when clicking outside
document.getElementById('reportEditModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeReportModal();
    }
});

// Form submission handler
document.getElementById('reportEditForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const submitButton = document.getElementById('save_report_btn');
    const messageDiv = document.getElementById('modal_message');
    const originalText = submitButton.innerHTML;

    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-1"></i>جارٍ الحفظ...';
    messageDiv.classList.add('hidden');

    // Determine the endpoint based on report type and existence
    const reportId = data.report_id;
    const reportType = data.report_type;
    let url, method;

    // Use role-specific routes to avoid middleware conflicts
    // Quran teachers use quran-reports, academic teachers use academic-reports (also for interactive)
    const routePrefix = reportType === 'quran' ? 'quran-reports' : 'academic-reports';

    if (reportId) {
        // Update existing report - use relative URL that works with subdomain routing
        url = `/teacher/${routePrefix}/${reportType}/${reportId}`;
        method = 'PUT';
    } else {
        // Create new report
        url = `/teacher/${routePrefix}/${reportType}`;
        method = 'POST';
    }

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            // If response is not OK, try to get error message
            return response.json().catch(() => {
                // If JSON parsing fails, throw with status
                throw new Error(`خطأ ${response.status}: ${response.statusText}`);
            }).then(errorData => {
                throw new Error(errorData.message || `خطأ ${response.status}`);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            // Show success message
            messageDiv.className = 'bg-green-50 border border-green-200 rounded-lg p-4';
            messageDiv.innerHTML = `
                <div class="flex items-center gap-2 text-green-800">
                    <i class="ri-check-line text-green-600"></i>
                    <span>تم حفظ التقرير بنجاح</span>
                </div>
            `;
            messageDiv.classList.remove('hidden');

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Show error message
            messageDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4';
            messageDiv.innerHTML = `
                <div class="flex items-center gap-2 text-red-800">
                    <i class="ri-error-warning-line text-red-600"></i>
                    <span>${result.message || 'حدث خطأ أثناء حفظ التقرير'}</span>
                </div>
            `;
            messageDiv.classList.remove('hidden');

            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);

        // Show error message with details
        messageDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4';
        messageDiv.innerHTML = `
            <div class="flex items-center gap-2 text-red-800">
                <i class="ri-error-warning-line text-red-600"></i>
                <span>${error.message || 'حدث خطأ أثناء حفظ التقرير'}</span>
            </div>
        `;
        messageDiv.classList.remove('hidden');

        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});
</script>
