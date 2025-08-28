<x-layouts.teacher 
    :title="'الحلقة الفردية - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الفردية للطالب: ' . $circle->student->name">

<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-individual-circle.circle-header :circle="$circle" view-type="teacher" />

            <!-- Enhanced Sessions List -->
            <x-sessions.enhanced-sessions-list 
                :sessions="$circle->sessions" 
                title="إدارة جلسات الحلقة الفردية"
                view-type="teacher"
                :show-tabs="true"
                :circle="$circle"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <x-individual-circle.sidebar :circle="$circle" view-type="teacher" />
        </div>
    </div>
</div>

<script>
// Session filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = ['filterAllSessions', 'filterScheduledSessions', 'filterCompletedSessions', 'filterTemplateSessions'];
    const sessionItems = document.querySelectorAll('.session-item');
    
    filterButtons.forEach(buttonId => {
        document.getElementById(buttonId).addEventListener('click', function() {
            // Update button styles
            filterButtons.forEach(id => {
                const btn = document.getElementById(id);
                btn.classList.remove('border-primary-200', 'text-primary-700', 'bg-primary-50');
                btn.classList.add('border-gray-200', 'text-gray-700');
            });
            
            this.classList.add('border-primary-200', 'text-primary-700', 'bg-primary-50');
            this.classList.remove('border-gray-200', 'text-gray-700');
            
            // Filter sessions
            const filterType = buttonId.replace('filter', '').replace('Sessions', '').toLowerCase();
            
            sessionItems.forEach(item => {
                if (filterType === 'all') {
                    item.style.display = 'block';
                } else {
                    const itemType = item.dataset.sessionType;
                    item.style.display = itemType === filterType ? 'block' : 'none';
                }
            });
        });
    });
});

// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for teacher sessions
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Teacher Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>
