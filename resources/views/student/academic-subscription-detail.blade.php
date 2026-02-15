<x-layouts.student
    :title="($subscription->subject_name ?? __('student.academic_subscription.title_default')) . ' - ' . config('app.name', __('student.common.platform_default'))"
    :description="__('student.academic_subscription.description_prefix') . ' ' . ($subscription->academicTeacher->full_name ?? __('student.academic_subscription.academic_teacher_default'))">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.academic_subscription.academic_teachers_breadcrumb'), 'route' => route('academic-teachers.index', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']), 'icon' => 'ri-user-star-line'],
            ['label' => $subscription->subject_name ?? __('student.academic_subscription.title_default'), 'truncate' => true],
        ]"
        view-type="student"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header -->
            <x-circle.circle-header :circle="$subscription" type="individual" view-type="student" context="academic" />

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
                        label="{{ __('student.academic_subscription.sessions_tab') }}"
                        icon="ri-calendar-line"
                        :badge="$allSessions->count()"
                    />
                    <x-tabs.tab
                        id="quizzes"
                        label="{{ __('student.academic_subscription.quizzes_tab') }}"
                        icon="ri-file-list-3-line"
                    />
                    @if($teacherProfile)
                    <x-tabs.tab
                        id="reviews"
                        label="{{ __('student.academic_subscription.teacher_reviews_tab') }}"
                        icon="ri-star-line"
                        :badge="$teacherReviews->count()"
                    />
                    @endif
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions" padding="p-0 md:p-8">
                        <x-sessions.sessions-list
                            :sessions="$allSessions"
                            view-type="student"
                            :circle="$subscription"
                            :show-tabs="false"
                            empty-message="{{ __('student.academic_subscription.no_sessions_yet') }}" />
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

// Chat with teacher function (Supervised)
function openChatWithTeacher() {
    @if($subscription->academicTeacher && $subscription->academicTeacher->user && $subscription->academicTeacher->user->hasSupervisor())
        window.location.href = "{{ route('chat.start-supervised', [
            'subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy',
            'teacher' => $subscription->academicTeacher->user->id,
            'student' => auth()->id(),
            'entityType' => 'academic_lesson',
            'entityId' => $subscription->id
        ]) }}";
    @endif
}
</script>

</x-layouts.student>
