<x-layouts.teacher 
    :title="'الحلقة الجماعية - ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الجماعية: ' . $circle->name">

<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-circle.group-header :circle="$circle" view-type="teacher" />

            <!-- Enhanced Sessions List -->
            @php
                // Get all sessions for the circle
                $allSessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
            @endphp
            
            <x-sessions.enhanced-sessions-list 
                :sessions="$allSessions" 
                title="إدارة جلسات الحلقة الجماعية"
                view-type="teacher"
                :show-tabs="true"
                :circle="$circle"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$circle" view-type="teacher" />
            
            <!-- Quick Actions -->
            <x-circle.quick-actions :circle="$circle" view-type="teacher" />
            
            <!-- Students List -->
            <x-circle.group-students-list :circle="$circle" view-type="teacher" />
        </div>
    </div>
</div>

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