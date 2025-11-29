<x-layouts.teacher
    :title="'الحلقة الجماعية - ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الجماعية: ' . $circle->name">

<!-- Flash Messages -->
@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 flex items-center gap-3">
        <i class="ri-checkbox-circle-fill text-green-500 text-xl"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 flex items-center gap-3">
        <i class="ri-error-warning-fill text-red-500 text-xl"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if(session('warning'))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4 flex items-center gap-3">
        <i class="ri-alert-fill text-yellow-500 text-xl"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الحلقات الجماعية</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $circle->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-circle.group-header :circle="$circle" view-type="teacher" />

            <!-- Sessions List -->
            @php
                // Get all sessions for the circle
                $allSessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
            @endphp

            <x-sessions.sessions-list
                :sessions="$allSessions"
                :show-tabs="false" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$circle" view-type="teacher" />

            <!-- Quick Actions -->
            <x-circle.quick-actions :circle="$circle" view-type="teacher" />

            <!-- Students List -->
            <x-circle.group-students-list :circle="$circle" view-type="teacher" />

            <!-- Certificate Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-award-line text-amber-500"></i>
                    الشهادات
                </h3>

                @php
                    // Get total students in this group circle
                    $totalStudents = $circle->students()->count();

                    // Count certificates issued via the pivot table
                    $studentsWithCertificates = $circle->students()
                        ->wherePivot('certificate_issued', true)
                        ->count();
                @endphp

                @if($studentsWithCertificates > 0)
                    <div class="bg-green-50 rounded-lg p-3 mb-4 border border-green-200">
                        <p class="text-sm text-green-800">
                            <i class="ri-checkbox-circle-fill ml-1"></i>
                            تم إصدار {{ $studentsWithCertificates }} من {{ $totalStudents }} شهادة
                        </p>
                    </div>
                @endif

                <p class="text-sm text-gray-600 mb-4">يمكنك إصدار شهادات للطلاب عند إتمام البرنامج أو تحقيق إنجاز معين</p>

                <button type="button"
                        onclick="Livewire.dispatch('openModal', { subscriptionType: 'group_quran', subscriptionId: null, circleId: {{ $circle->id }} })"
                        class="w-full inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                    <i class="ri-award-line ml-2 text-lg"></i>
                    إصدار شهادات للطلاب
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Certificate Modal -->
@livewire('issue-certificate-modal')

<script>
// Student management functions
function viewStudentProgress(studentId) {
    window.location.href = '{{ route("teacher.group-circles.student-progress", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "circle" => $circle->id, "student" => "__STUDENT_ID__"]) }}'.replace('__STUDENT_ID__', studentId);
}

function contactStudent(studentId) {
    const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
    window.location.href = `/${subdomain}/teacher/students/${studentId}/contact`;
}

// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>