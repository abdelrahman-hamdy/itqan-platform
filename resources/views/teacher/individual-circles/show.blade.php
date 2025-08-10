<x-layouts.teacher 
    :title="'الحلقة الفردية - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الفردية للطالب: ' . $circle->student->name">

<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-individual-circle.circle-header :circle="$circle" view-type="teacher" />

            <!-- Sessions List -->
            <x-individual-circle.sessions-list :circle="$circle" :sessions="$circle->sessions" view-type="teacher" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <x-individual-circle.sidebar :circle="$circle" view-type="teacher" />
        </div>
    </div>
</div>

<!-- Session Scheduling Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">جدولة جلسة جديدة</h3>
                <button type="button" onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <form id="scheduleForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اختر الجلسة</label>
                        <select id="templateSessionSelect" name="template_session_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">اختر جلسة...</option>
                            @foreach($circle->templateSessions->where('is_scheduled', false) as $template)
                                <option value="{{ $template->id }}">{{ $template->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">التاريخ والوقت</label>
                        <input type="datetime-local" id="scheduledAt" name="scheduled_at" required 
                               min="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الجلسة (اختياري)</label>
                        <input type="text" id="sessionTitle" name="title" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mt-6 flex items-center justify-end space-x-2 space-x-reverse">
                    <button type="button" onclick="closeScheduleModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        إلغاء
                    </button>
                    <button type="submit" id="scheduleSubmitBtn"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700">
                        جدولة الجلسة
                    </button>
                </div>
            </form>
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

// Modal functions
function openScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
    document.getElementById('scheduleForm').reset();
}

// Session detail function
function openSessionDetail(sessionId) {
    const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
    window.location.href = `/${subdomain}/teacher/sessions/${sessionId}`;
}

// Schedule form submission
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('scheduleSubmitBtn');
    const formData = new FormData(this);
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'جاري الجدولة...';
    
    fetch(`{{ route('teacher.individual-circles.schedule-session', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('تم جدولة الجلسة بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في جدولة الجلسة');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'جدولة الجلسة';
    });
});
</script>

</x-layouts.teacher>
