<x-layouts.student
    :title="($individualCircle->subscription->package->name ?? 'حلقة فردية') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الحلقة الفردية للقرآن الكريم'">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => 'معلمو القرآن', 'route' => route('quran-teachers.index', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']), 'icon' => 'ri-book-read-line'],
            ['label' => $individualCircle->subscription->package->name ?? 'حلقة فردية', 'truncate' => true],
        ]"
        view-type="student"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$individualCircle" type="individual" view-type="student" context="quran" />

            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
                $teacherProfile = $individualCircle->quranTeacher?->quranTeacherProfile;
                $teacherReviews = $teacherProfile ? $teacherProfile->approvedReviews()->with('student')->latest()->get() : collect();
            @endphp

            <!-- Tabs Component -->
            <x-tabs id="individual-circle-tabs" default-tab="sessions" variant="default" color="primary">
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
                            :circle="$individualCircle"
                            :show-tabs="false"
                            empty-message="لا توجد جلسات مجدولة بعد" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:quizzes-widget :assignable="$individualCircle" />
                    </x-tabs.panel>

                    @if($teacherProfile)
                    <x-tabs.panel id="reviews">
                        <x-reviews.section
                            :reviewable-type="\App\Models\QuranTeacherProfile::class"
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
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$individualCircle" view-type="student" context="individual" />

            <!-- Quick Actions -->
            <x-circle.quick-actions :circle="$individualCircle" type="individual" view-type="student" context="quran" />

            <!-- Certificate Section -->
            <x-certificate.student-certificate-section :subscription="$individualCircle->subscription" type="quran" />

            <!-- Subscription Details -->
            <x-circle.subscription-details :subscription="$individualCircle->subscription" view-type="student" />
        </div>
    </div>
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);

        window.location.href = finalUrl;
    @else
    @endif
}

// Add any additional student-specific functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips, progress bars, etc.
});
</script>
</x-slot>

</x-layouts.student>