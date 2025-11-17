<x-layouts.teacher 
    :title="'الحلقة الفردية - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الفردية للطالب: ' . $circle->student->name">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الحلقات الفردية</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $circle->student->name ?? 'طالب' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-circle.individual-header :circle="$circle" view-type="teacher" />

            <!-- Quran Sessions Section -->
            <x-sessions.sessions-list
                :sessions="$circle->sessions"
                title="إدارة جلسات الحلقة الفردية"
                view-type="teacher"
                :circle="$circle"
                :show-tabs="false"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$circle" view-type="teacher" context="individual" />
            
            <!-- Quick Actions -->
            <x-circle.individual-quick-actions :circle="$circle" view-type="teacher" />
            
            <!-- Progress Overview -->
            <x-circle.individual-progress-overview :circle="$circle" view-type="teacher" />
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
