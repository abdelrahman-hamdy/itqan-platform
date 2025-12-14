@props([
    'session',
    'viewType' => 'student' // student, teacher
])

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li>
                    <a href="{{ route($viewType . '.profile', ['subdomain' => request()->route('subdomain')]) }}"
                       class="hover:text-primary">
                        {{ $viewType === 'student' ? 'ملفي الشخصي' : 'ملفي الشخصي' }}
                    </a>
                </li>
                <li>/</li>
                @if($session->circle_id && $session->circle)
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('quran-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">حلقات القرآن</a>
                        @else
                            <a href="{{ route('teacher.group-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">الحلقات الجماعية</a>
                        @endif
                    </li>
                    <li>/</li>
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.circles.show', ['subdomain' => request()->route('subdomain'), 'circleId' => $session->circle->id]) }}"
                               class="hover:text-primary">{{ $session->circle->name ?? 'الحلقة' }}</a>
                        @else
                            <a href="{{ route('teacher.group-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->circle->id]) }}"
                               class="hover:text-primary">{{ $session->circle->name ?? 'الحلقة' }}</a>
                        @endif
                    </li>
                @elseif($session->individual_circle_id && $session->individualCircle)
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('quran-teachers.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">معلمي القرآن</a>
                        @else
                            <a href="{{ route('teacher.individual-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">الحلقات الفردية</a>
                        @endif
                    </li>
                    <li>/</li>
                    <li>
                        <a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->individualCircle->id]) }}"
                           class="hover:text-primary">{{ $session->individualCircle->subscription->package->name ?? 'الحلقة الفردية' }}</a>
                    </li>
                @else
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">الملف الشخصي</a>
                        @else
                            <a href="{{ route('teacher.profile', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">
                                {{ $session->session_type === 'trial' ? 'الجلسات التجريبية' : 'جدول الجلسات' }}
                            </a>
                        @endif
                    </li>
                @endif
                <li>/</li>
                <li class="text-gray-900">{{ $session->title ?? 'تفاصيل الجلسة' }}</li>
            </ol>
        </nav>

        <div class="space-y-6">
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
                        <i class="ri-file-text-line text-primary ml-2"></i>
                        محتوى الجلسة
                    </h3>

                    <form id="sessionContentForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-2">
                                محتوى الدرس
                            </label>
                            <textarea
                                id="lesson_content"
                                name="lesson_content"
                                rows="4"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="ما هي المواضيع التي تم تغطيتها في هذه الجلسة؟">{{ $session->lesson_content ?? '' }}</textarea>
                        </div>

                        <p class="text-sm text-gray-500">
                            <i class="ri-information-line ml-1"></i>
                            لإضافة ملاحظات على أداء الطالب، استخدم تقرير الجلسة المنفصل
                        </p>

                        <button
                            type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="ri-save-line ml-2"></i>
                            حفظ محتوى الدرس
                        </button>
                    </form>
                </div>
            @elseif($session->lesson_content)
                {{-- Student: Display Only --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-file-text-line text-primary-600 ml-2"></i>
                        محتوى الجلسة
                    </h2>

                    <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-4">
                        {!! nl2br(e($session->lesson_content)) !!}
                    </div>
                </div>
            @endif

            <!-- Trial Session Information (Student Only) -->
            @if($viewType === 'student' && $session->session_type === 'trial' && $session->trialRequest)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-sm border border-green-200 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-gift text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-green-900 mb-2">
                                <i class="fas fa-star text-yellow-500 ml-1"></i>
                                جلسة تجريبية مجانية
                            </h3>
                            <p class="text-green-800 mb-3">
                                هذه جلسة تجريبية مجانية مدتها 30 دقيقة للتعرف على المعلم وتقييم مستواك في القرآن الكريم.
                            </p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-green-900">المستوى المُدخل:</span>
                                    <span class="text-green-700">{{ $session->trialRequest->level_label }}</span>
                                </div>
                                @if($session->trialRequest->learning_goals && count($session->trialRequest->learning_goals) > 0)
                                <div>
                                    <span class="font-medium text-green-900">الأهداف:</span>
                                    <span class="text-green-700">
                                        @php
                                            $goals = [
                                                'reading' => 'القراءة',
                                                'tajweed' => 'التجويد',
                                                'memorization' => 'الحفظ',
                                                'improvement' => 'التحسين'
                                            ];
                                            $goalLabels = collect($session->trialRequest->learning_goals)->map(fn($g) => $goals[$g] ?? $g);
                                        @endphp
                                        {{ $goalLabels->join('، ') }}
                                    </span>
                                </div>
                                @endif
                            </div>
                            @if($session->trialRequest->notes)
                            <div class="mt-3 p-3 bg-white/50 rounded-lg">
                                <span class="font-medium text-green-900 block mb-1">ملاحظاتك:</span>
                                <p class="text-green-700 text-sm">{{ $session->trialRequest->notes }}</p>
                            </div>
                            @endif
                        </div>
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
                        طلاب الجلسة ({{ $session->students->count() }})
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">الطالب</h3>

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
    <script>
    // Students info for the unified modal
    const studentsInfo = {
        @if($session->session_type === 'group' && $session->students && $session->students->count() > 0)
            @foreach($session->students as $student)
                {{ $student->id }}: {
                    name: '{{ $student->name ?? "طالب" }}',
                    avatar: '{{ $student->avatar ?? "" }}',
                    email: '{{ $student->email ?? "" }}',
                    gender: '{{ $student->gender ?? "male" }}'
                },
            @endforeach
        @elseif($session->session_type === 'individual' && $session->student)
            {{ $session->student->id }}: {
                name: '{{ $session->student->name ?? "طالب" }}',
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
        return studentsInfo[studentId]?.name || 'الطالب';
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

    // Message Student Function
    function messageStudent(studentId) {
        const subdomain = '{{ request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy" }}';
        const chatUrl = '/chat?user=' + studentId;
        window.location.href = chatUrl;
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
                submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ الحفظ...';

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
                        // Show success notification
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                        notification.innerHTML = '<i class="ri-check-line"></i><span>تم حفظ محتوى الدرس بنجاح</span>';
                        document.body.appendChild(notification);

                        setTimeout(() => {
                            notification.remove();
                        }, 3000);
                    } else {
                        alert(data.message || 'حدث خطأ أثناء الحفظ');
                    }
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء حفظ محتوى الدرس');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                });
            });
        }
    });
    </script>
@endif
