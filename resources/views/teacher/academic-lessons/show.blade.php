<x-layouts.teacher 
    :title="'الدرس الخاص - ' . $subscription->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الدرس الخاص للطالب: ' . $subscription->student->name">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.academic.lessons.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الدروس الخاصة</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $subscription->student->name ?? 'طالب' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header (using circle header pattern) -->
            <x-circle.individual-header :circle="$subscription" view-type="teacher" context="academic" />

            <!-- Sessions Section with Tabs -->
            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
            @endphp
            
            <x-sessions.sessions-list
                :sessions="$allSessions"
                title="إدارة جلسات الدرس الخاص"
                view-type="teacher"
                :circle="$subscription"
                :show-tabs="false"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Academic Lesson Information -->
            <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="teacher" />

            <!-- Actions and Progress -->
            <div class="space-y-6">
                <x-circle.individual-quick-actions :circle="$subscription" viewType="teacher" type="academic" />
                <x-circle.individual-progress-overview :circle="$subscription" type="academic" />
            </div>
        </div>
    </div>
</div>

<script>
// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for teacher sessions
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Teacher Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>
