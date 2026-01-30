@props([
    'session',
    'viewType' => 'student' // student, teacher
])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $breadcrumbItems = [];

    // Build session-type specific breadcrumb items
    if($session->circle_id && $session->circle) {
        // Group Circle Session
        if($viewType === 'student') {
            $breadcrumbItems[] = ['label' => __('components.sessions.breadcrumbs.quran_circles'), 'route' => route('quran-circles.index', ['subdomain' => $subdomain]), 'icon' => 'ri-book-read-line'];
            $breadcrumbItems[] = ['label' => $session->circle->name ?? __('components.sessions.breadcrumbs.circle'), 'route' => route('student.circles.show', ['subdomain' => $subdomain, 'circleId' => $session->circle->id]), 'truncate' => true];
        } else {
            $breadcrumbItems[] = ['label' => __('components.sessions.breadcrumbs.group_circles'), 'route' => route('teacher.group-circles.index', ['subdomain' => $subdomain])];
            $breadcrumbItems[] = ['label' => $session->circle->name ?? __('components.sessions.breadcrumbs.circle'), 'route' => route('teacher.group-circles.show', ['subdomain' => $subdomain, 'circle' => $session->circle->id]), 'truncate' => true];
        }
    } elseif($session->individual_circle_id && $session->individualCircle) {
        // Individual Circle Session
        if($viewType === 'student') {
            $breadcrumbItems[] = ['label' => __('components.sessions.breadcrumbs.quran_teachers'), 'route' => route('quran-teachers.index', ['subdomain' => $subdomain]), 'icon' => 'ri-user-star-line'];
            $breadcrumbItems[] = ['label' => $session->individualCircle->name ?? __('components.sessions.breadcrumbs.individual_circle'), 'route' => route('individual-circles.show', ['subdomain' => $subdomain, 'circle' => $session->individualCircle->id]), 'truncate' => true];
        } else {
            $breadcrumbItems[] = ['label' => __('components.sessions.breadcrumbs.individual_circles'), 'route' => route('teacher.individual-circles.index', ['subdomain' => $subdomain])];
            $breadcrumbItems[] = ['label' => $session->individualCircle->name ?? __('components.sessions.breadcrumbs.individual_circle'), 'route' => route('individual-circles.show', ['subdomain' => $subdomain, 'circle' => $session->individualCircle->id]), 'truncate' => true];
        }
    } else {
        // Fallback - trial or other session
        if($viewType === 'teacher') {
            $breadcrumbItems[] = ['label' => $session->session_type === 'trial' ? __('components.sessions.breadcrumbs.trial_sessions') : __('components.sessions.breadcrumbs.session_schedule'), 'route' => route('teacher.profile', ['subdomain' => $subdomain])];
        }
    }

    // Add session title as the last item
    $breadcrumbItems[] = ['label' => $session->title ?? __('components.sessions.breadcrumbs.session_details'), 'truncate' => true];
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb :items="$breadcrumbItems" :view-type="$viewType" />

    <div class="space-y-4 md:space-y-6">
            <!-- Session Header -->
            <x-sessions.session-header :session="$session" :view-type="$viewType" />

            <!-- Enhanced LiveKit Meeting Interface -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <x-meetings.livekit-interface
                    :session="$session"
                    :user-type="$viewType === 'student' ? 'student' : 'quran_teacher'"
                />
            </div>

            {{-- Session Content Section --}}
            @if($viewType === 'teacher')
                {{-- Teacher: Editable Form --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-file-text-line text-primary ms-2"></i>
                        {{ __('components.sessions.session_detail.session_content') }}
                    </h3>

                    <form id="sessionContentForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('components.sessions.session_detail.lesson_content') }}
                            </label>
                            <textarea
                                id="lesson_content"
                                name="lesson_content"
                                rows="4"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="{{ __('components.sessions.session_detail.lesson_content_placeholder') }}">{{ $session->lesson_content ?? '' }}</textarea>
                        </div>

                        <p class="text-sm text-gray-500">
                            <i class="ri-information-line ms-1"></i>
                            {{ __('components.sessions.session_detail.evaluation_note') }}
                        </p>

                        <button
                            type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="ri-save-line ms-2"></i>
                            {{ __('components.sessions.session_detail.save_lesson_content') }}
                        </button>
                    </form>
                </div>
            @elseif($session->lesson_content)
                {{-- Student: Display Only --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-file-text-line text-primary-600 ms-2"></i>
                        {{ __('components.sessions.session_detail.session_content') }}
                    </h2>

                    <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-4">
                        {!! nl2br(e($session->lesson_content)) !!}
                    </div>
                </div>
            @endif

            <!-- Homework Management (Teacher) or Homework Display (Student) -->
            @if($viewType === 'teacher')
                <x-sessions.homework-management
                    :session="$session"
                    view-type="teacher" />
            @elseif($session->homework && $session->homework->count() > 0)
                <x-sessions.homework-display
                    :session="$session"
                    :homework="$session->homework"
                    view-type="student" />
            @endif

            <!-- Students Section (Teacher Only) -->
            @if($viewType === 'teacher')
                @if($session->session_type === 'group' && $session->students && $session->students->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('components.sessions.session_detail.session_students') }} ({{ $session->students->count() }})
                    </h3>

                    <div class="space-y-4">
                        @foreach($session->students as $student)
                            <x-sessions.student-item
                                :student="$student"
                                :session="$session"
                                :show-chat="true"
                                size="sm"
                            />
                        @endforeach
                    </div>
                </div>
                @elseif($session->session_type === 'individual' && $session->student)
                <!-- Individual Student Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('components.sessions.session_detail.student_label') }}</h3>

                    <x-sessions.student-item
                        :student="$session->student"
                        :session="$session"
                        :show-chat="true"
                        size="md"
                    />
                </div>
                @endif
            @endif

    </div>
</div>

<!-- Unified Report Edit Modal (Teacher Only) -->
@if($viewType === 'teacher')
    <x-modals.student-report-edit session-type="quran" />

    <!-- Pre-rendered avatars for modal (using unified x-avatar component) -->
    <div id="prerendered_avatars_container" class="hidden">
        @if($session->session_type === 'group' && $session->students && $session->students->count() > 0)
            @foreach($session->students as $student)
                <div id="prerendered_avatar_{{ $student->id }}" class="hidden">
                    <x-avatar :user="$student" size="sm" user-type="student" />
                </div>
            @endforeach
        @elseif($session->session_type === 'individual' && $session->student)
            <div id="prerendered_avatar_{{ $session->student->id }}" class="hidden">
                <x-avatar :user="$session->student" size="sm" user-type="student" />
            </div>
        @endif
    </div>

    <!-- Session Content Form Script & Report Modal Functions -->
    @php
        // Get chat context for supervised chat routing
        $chatTeacher = $session->quranTeacher ?? null;
        $chatEntityType = $session->circle_id ? 'quran_circle' : 'quran_individual';
        $chatEntityId = $session->circle_id ?? $session->individual_circle_id;
        $teacherHasSupervisor = $chatTeacher && $chatTeacher->hasSupervisor();
    @endphp
    <script>
    // Chat configuration for supervised messaging
    const chatConfig = {
        subdomain: '{{ $subdomain }}',
        teacherId: {{ $chatTeacher?->id ?? 'null' }},
        entityType: '{{ $chatEntityType }}',
        entityId: {{ $chatEntityId ?? 'null' }},
        teacherHasSupervisor: {{ $teacherHasSupervisor ? 'true' : 'false' }}
    };

    // Translations for JavaScript
    const jsTranslations = {
        studentDefault: '{{ __("components.sessions.js.student_default") }}',
        statusAttended: '{{ __("components.sessions.js.status_attended") }}',
        statusLate: '{{ __("components.sessions.js.status_late") }}',
        statusLeft: '{{ __("components.sessions.js.status_left") }}',
        statusAbsent: '{{ __("components.sessions.js.status_absent") }}',
        memorizationDegree: '{{ __("components.sessions.js.memorization_degree") }}',
        reviewDegree: '{{ __("components.sessions.js.review_degree") }}',
        homeworkDegree: '{{ __("components.sessions.js.homework_degree") }}',
        attendanceDuration: '{{ __("components.sessions.js.attendance_duration") }}',
        attendancePercentage: '{{ __("components.sessions.js.attendance_percentage") }}',
        minutes: '{{ __("components.sessions.js.minutes") }}',
        notesLabel: '{{ __("components.sessions.js.notes_label") }}',
        editReport: '{{ __("components.sessions.js.edit_report") }}',
        reportUpdated: '{{ __("components.sessions.js.report_updated") }}',
        lessonSaved: '{{ __("components.sessions.js.lesson_saved") }}',
        saving: '{{ __("components.sessions.js.saving") }}',
        saveError: '{{ __("components.sessions.js.save_error") }}'
    };

    // Students info for the unified modal
    const studentsInfo = {
        @if($session->session_type === 'group' && $session->students && $session->students->count() > 0)
            @foreach($session->students as $student)
                {{ $student->id }}: {
                    name: '{{ $student->name ?? "" }}' || jsTranslations.studentDefault,
                    avatar: '{{ $student->avatar ?? "" }}',
                    email: '{{ $student->email ?? "" }}',
                    gender: '{{ $student->gender ?? "male" }}'
                },
            @endforeach
        @elseif($session->session_type === 'individual' && $session->student)
            {{ $session->student->id }}: {
                name: '{{ $session->student->name ?? "" }}' || jsTranslations.studentDefault,
                avatar: '{{ $session->student->avatar ?? "" }}',
                email: '{{ $session->student->email ?? "" }}',
                gender: '{{ $session->student->gender ?? "male" }}'
            },
        @endif
    };

    // Get report data for modal
    function getReportData(studentId) {
        @php
            $reports = $session->studentReports ?? collect();
        @endphp

        const reports = {
            @foreach($reports as $report)
                {{ $report->student_id }}: {
                    id: {{ $report->id ?? 'null' }},
                    attendance_status: '{{ $report->attendance_status ?? '' }}',
                    manually_evaluated: {{ $report->manually_evaluated ? 'true' : 'false' }},
                    attendance_percentage: {{ $report->attendance_percentage ?? 'null' }},
                    actual_attendance_minutes: {{ $report->actual_attendance_minutes ?? 'null' }},
                    new_memorization_degree: {{ $report->new_memorization_degree ?? 'null' }},
                    reservation_degree: {{ $report->reservation_degree ?? 'null' }},
                    notes: `{{ addslashes($report->notes ?? '') }}`
                },
            @endforeach
        };

        return reports[studentId] || null;
    }

    function getStudentName(studentId) {
        return studentsInfo[studentId]?.name || jsTranslations.studentDefault;
    }

    function getStudentData(studentId) {
        return studentsInfo[studentId] || null;
    }

    // Edit Student Report Function - Uses unified modal
    function editStudentReport(studentId, reportId) {
        const reportData = getReportData(studentId);
        const studentName = getStudentName(studentId);
        const studentData = getStudentData(studentId);

        openReportModal(
            {{ $session->id }},
            studentId,
            studentName,
            reportData,
            'quran',
            studentData
        );
    }

    // Message Student Function - Uses supervised group chat
    function messageStudent(studentId) {
        if (!chatConfig.teacherHasSupervisor) {
            window.toast?.error('{{ __("chat.teacher_no_supervisor") }}');
            return;
        }

        if (!chatConfig.teacherId || !chatConfig.entityId) {
            window.toast?.error('{{ __("chat.chat_unavailable") }}');
            return;
        }

        // Build supervised chat URL
        const chatUrl = `/chat/start-supervised/${chatConfig.teacherId}/${studentId}/${chatConfig.entityType}/${chatConfig.entityId}`;
        window.location.href = chatUrl;
    }

    // Update student card display after report save (to avoid page reload and preserve LiveKit connection)
    function updateStudentCardDisplay(studentId, reportData) {
        // Update attendance badge
        const attendanceContainer = document.getElementById('student-attendance-' + studentId);
        if (attendanceContainer && reportData.attendance_status) {
            const statusMap = {
                'attended': { label: jsTranslations.statusAttended, class: 'bg-green-100 text-green-800', icon: 'ri-check-line' },
                'late': { label: jsTranslations.statusLate, class: 'bg-yellow-100 text-yellow-800', icon: 'ri-time-line' },
                'left': { label: jsTranslations.statusLeft, class: 'bg-orange-100 text-orange-800', icon: 'ri-logout-box-line' },
                'absent': { label: jsTranslations.statusAbsent, class: 'bg-red-100 text-red-800', icon: 'ri-close-line' }
            };
            const status = statusMap[reportData.attendance_status] || statusMap['attended'];
            const percentage = reportData.attendance_percentage ? ` (${Math.round(reportData.attendance_percentage)}%)` : '';

            attendanceContainer.innerHTML = `
                <span class="inline-flex items-center px-3 py-1.5 ${status.class} rounded-full text-sm font-semibold">
                    <i class="${status.icon} ms-1"></i>
                    ${status.label}${percentage}
                </span>
            `;
        }

        // Update report data section
        const reportDataContainer = document.getElementById('student-report-data-' + studentId);
        if (reportDataContainer) {
            let infoItems = [];

            // Quran degrees
            if (reportData.new_memorization_degree !== null && reportData.new_memorization_degree !== undefined) {
                infoItems.push({
                    icon: 'ri-book-line text-green-600',
                    label: jsTranslations.memorizationDegree,
                    value: reportData.new_memorization_degree + '/10',
                    class: 'bg-green-100 text-green-800'
                });
            }
            if (reportData.reservation_degree !== null && reportData.reservation_degree !== undefined) {
                infoItems.push({
                    icon: 'ri-refresh-line text-blue-600',
                    label: jsTranslations.reviewDegree,
                    value: reportData.reservation_degree + '/10',
                    class: 'bg-blue-100 text-blue-800'
                });
            }
            // Academic/Interactive homework degree
            if (reportData.homework_degree !== null && reportData.homework_degree !== undefined) {
                infoItems.push({
                    icon: 'ri-file-list-line text-purple-600',
                    label: jsTranslations.homeworkDegree,
                    value: reportData.homework_degree + '/10',
                    class: 'bg-purple-100 text-purple-800'
                });
            }
            // Attendance minutes
            if (reportData.actual_attendance_minutes !== null && reportData.actual_attendance_minutes !== undefined) {
                infoItems.push({
                    icon: 'ri-time-line text-purple-600',
                    label: jsTranslations.attendanceDuration,
                    value: reportData.actual_attendance_minutes + ' ' + jsTranslations.minutes,
                    class: 'bg-purple-100 text-purple-800'
                });
            }
            // Attendance percentage
            if (reportData.attendance_percentage !== null && reportData.attendance_percentage !== undefined) {
                infoItems.push({
                    icon: 'ri-percent-line text-indigo-600',
                    label: jsTranslations.attendancePercentage,
                    value: Math.round(reportData.attendance_percentage) + '%',
                    class: 'bg-indigo-100 text-indigo-800'
                });
            }

            if (infoItems.length > 0) {
                // Split into two columns
                const leftColumn = infoItems.filter((_, i) => i % 2 === 0);
                const rightColumn = infoItems.filter((_, i) => i % 2 === 1);

                const generateColumn = (items) => items.map(item => `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="${item.icon} ms-2"></i>
                            <span class="text-gray-900 text-sm">${item.label}</span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${item.class}">
                            ${item.value}
                        </span>
                    </div>
                `).join('');

                let notesHtml = '';
                if (reportData.notes) {
                    notesHtml = `
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-start">
                                <i class="ri-sticky-note-line text-amber-600 ms-2 mt-0.5"></i>
                                <div>
                                    <span class="text-gray-600 text-xs font-medium">${jsTranslations.notesLabel}</span>
                                    <p class="text-gray-800 text-sm mt-1">${reportData.notes}</p>
                                </div>
                            </div>
                        </div>
                    `;
                }

                reportDataContainer.innerHTML = `
                    <div class="bg-white border border-gray-300 rounded-lg mb-3 p-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-3">${generateColumn(leftColumn)}</div>
                            <div class="space-y-3">${generateColumn(rightColumn)}</div>
                        </div>
                        ${notesHtml}
                    </div>
                `;
            } else if (reportData.notes) {
                reportDataContainer.innerHTML = `
                    <div class="bg-white border border-gray-300 rounded-lg mb-3 p-3">
                        <div class="flex items-start">
                            <i class="ri-sticky-note-line text-amber-600 ms-2 mt-0.5"></i>
                            <div>
                                <span class="text-gray-600 text-xs font-medium">${jsTranslations.notesLabel}</span>
                                <p class="text-gray-800 text-sm mt-1">${reportData.notes}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Update the edit button text and onclick
        const editBtnText = document.getElementById('student-edit-btn-text-' + studentId);
        const editBtn = document.getElementById('student-edit-btn-' + studentId);
        if (editBtnText) {
            editBtnText.textContent = jsTranslations.editReport;
        }
        if (editBtn && reportData.id) {
            editBtn.setAttribute('onclick', `editStudentReport(${studentId}, ${reportData.id})`);
        }

        // Update the student card data attribute
        const studentCard = document.getElementById('student-card-' + studentId);
        if (studentCard && reportData.id) {
            studentCard.setAttribute('data-report-id', reportData.id);
        }

        // Show a toast notification
        showReportUpdateNotification();
    }

    // Show toast notification for report update
    function showReportUpdateNotification() {
        if (window.toast) {
            window.toast.success(jsTranslations.reportUpdated);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const sessionContentForm = document.getElementById('sessionContentForm');
        if (sessionContentForm) {
            sessionContentForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;

                // Show loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>' + jsTranslations.saving;

                fetch('{{ route("teacher.sessions.update-notes", ["subdomain" => request()->route("subdomain"), "sessionId" => $session->id]) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success notification using unified toast
                        if (window.toast) {
                            window.toast.success(jsTranslations.lessonSaved);
                        }
                    } else {
                        window.toast?.error(data.message || jsTranslations.saveError);
                    }
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                })
                .catch(error => {
                    window.toast?.error(jsTranslations.saveError);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                });
            });
        }
    });
    </script>
@endif
