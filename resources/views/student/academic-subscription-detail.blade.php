<x-layouts.student 
    :title="($subscription->subject_name ?? 'درس أكاديمي') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الدرس الخاص مع ' . ($subscription->academicTeacher->full_name ?? 'المعلم الأكاديمي')">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.profile', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الملف الشخصي</a></li>
            <li>/</li>
            <li><a href="{{ route('academic-teachers.index', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">المعلمون الأكاديميون</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $subscription->subject_name ?? 'درس أكاديمي' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header (using circle header pattern) -->
            <x-circle.individual-header :circle="$subscription" view-type="student" context="academic" />

            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
                $teacherProfile = $subscription->academicTeacher; // academicTeacher IS already the AcademicTeacherProfile
                $teacherReviews = $teacherProfile ? $teacherProfile->approvedReviews()->with('student')->latest()->get() : collect();
            @endphp

            <!-- Tabs Component -->
            <x-tabs id="academic-subscription-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab
                        id="sessions"
                        label="الجلسات"
                        icon="ri-calendar-line"
                        :badge="$allSessions->count()"
                    />
                    <x-tabs.tab
                        id="quizzes"
                        label="الاختبارات"
                        icon="ri-file-list-3-line"
                    />
                    @if($teacherProfile)
                    <x-tabs.tab
                        id="reviews"
                        label="تقييمات المعلم"
                        icon="ri-star-line"
                        :badge="$teacherReviews->count()"
                    />
                    @endif
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list
                            :sessions="$allSessions"
                            view-type="student"
                            :circle="$subscription"
                            :show-tabs="false"
                            empty-message="لا توجد جلسات مجدولة بعد" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:quizzes-widget :assignable="$subscription" />
                    </x-tabs.panel>

                    @if($teacherProfile)
                    <x-tabs.panel id="reviews">
                        <x-reviews.section
                            :reviewable-type="\App\Models\AcademicTeacherProfile::class"
                            :reviewable-id="$teacherProfile->id"
                            review-type="teacher"
                            :reviews="$teacherReviews"
                            :rating="$teacherProfile->rating ?? 0"
                            :total-reviews="$teacherProfile->total_reviews ?? 0"
                            :show-summary="$teacherReviews->count() > 0"
                            :show-breakdown="true"
                            :show-review-form="true"
                        />
                    </x-tabs.panel>
                    @endif
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-6">
                <!-- Academic Lesson Information -->
                <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="student" />

                <!-- Subscription Details -->
                <x-circle.subscription-details
                    :subscription="$subscription"
                    view-type="student"
                />

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
