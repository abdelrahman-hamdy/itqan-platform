<x-layouts.student 
    :title="($subscription->subject_name ?? 'درس أكاديمي') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الدرس الخاص مع ' . ($subscription->academicTeacher->full_name ?? 'المعلم الأكاديمي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الملف الشخصي</a></li>
            <li>/</li>
            <li><a href="{{ route('student.academic-teachers', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">المعلمون الأكاديميون</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $subscription->subject_name ?? 'درس أكاديمي' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header (using circle header pattern) -->
            <x-circle.individual-header :circle="$subscription" view-type="student" context="academic" />

            <!-- Sessions Section with Tabs -->
            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
            @endphp
            
            <x-sessions.sessions-list
                :sessions="$allSessions"
                title="جلسات الدرس الخاص"
                view-type="student"
                :circle="$subscription"
                :show-tabs="false"
                empty-message="لا توجد جلسات مجدولة بعد" />

            <!-- Quizzes Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    الاختبارات
                </h3>
                <livewire:quizzes-widget :assignable="$subscription" />
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Academic Lesson Information -->
            <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="student" />

            <!-- Progress Summary -->
            @if(isset($progressSummary))
                <x-academic.progress-summary :progressSummary="$progressSummary" />
            @endif

            <!-- Quick Actions -->
            <x-circle.quick-actions
                :circle="$subscription"
                type="individual"
                view-type="student"
                context="academic"
            />

            <!-- Certificate Section -->
            <x-certificate.student-certificate-section :subscription="$subscription" type="academic" />
        </div>
    </div>
</div>

<script>
// Session detail function
function openSessionDetail(sessionId) {
    const baseUrl = "{{ route('student.academic-sessions.show', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => 'PLACEHOLDER']) }}";
    window.location.href = baseUrl.replace('PLACEHOLDER', sessionId);
}

// Chat with teacher function
function openChatWithTeacher() {
    @if($subscription->academicTeacher && $subscription->academicTeacher->user)
        @php
            $teacherUser = $subscription->academicTeacher->user;
            $conv = auth()->user()->getOrCreatePrivateConversation($teacherUser);
        @endphp
        @if($conv)
            window.location.href = "{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'conversation' => $conv->id]) }}";
        @endif
    @endif
}
</script>

</x-layouts.student>
