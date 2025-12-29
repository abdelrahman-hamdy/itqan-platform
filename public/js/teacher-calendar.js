let selectedCircle = null;
let selectedCircleType = null;

document.addEventListener('DOMContentLoaded', function () {
    loadCircles();
    initializeTabSwitching();
    initializeBulkScheduling();
});

// Tab switching functionality
function initializeTabSwitching() {
    const tabs = document.querySelectorAll('.circle-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabType = this.dataset.tab;
            switchTab(tabType);
        });
    });
}

function switchTab(tabType) {
    // Update tab appearance
    document.querySelectorAll('.circle-tab').forEach(tab => {
        tab.classList.remove('border-blue-500', 'text-blue-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });

    const activeTab = document.querySelector(`[data-tab="${tabType}"]`);
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add('border-blue-500', 'text-blue-600');

    // Show/hide content
    document.querySelectorAll('.circle-content').forEach(content => {
        content.style.display = 'none';
    });

    document.getElementById(`${tabType}CirclesContent`).style.display = 'block';

    // Clear selection when switching tabs
    selectedCircle = null;
    selectedCircleType = null;
    document.getElementById('bulkScheduleBtn').disabled = true;
}

// Load circles data
function loadCircles() {
    fetch('/teacher/api/circles')
        .then(response => response.json())
        .then(data => {
            displayGroupCircles(data.groupCircles || []);
            displayIndividualCircles(data.individualCircles || []);
            updateCircleCounts(data);
        })
        .catch(() => {
            // Silent fail - circles list stays empty
        });
}

function displayGroupCircles(circles) {
    const container = document.getElementById('groupCirclesList');
    const empty = document.getElementById('groupCirclesEmpty');

    if (circles.length === 0) {
        container.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    container.style.display = 'grid';
    empty.style.display = 'none';

    container.innerHTML = circles.map(circle => `
        <div class="circle-card border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors" 
             data-circle-id="${circle.id}" data-circle-type="group">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">${circle.name}</h4>
                <span class="px-2 py-1 rounded-full text-xs font-medium ${circle.is_scheduled ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${circle.is_scheduled ? 'مجدولة' : 'غير مجدولة'}
                </span>
            </div>
            
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>${circle.monthly_sessions_count || 8} جلسة شهرياً</span>
                </div>
                
                <div class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>${circle.session_duration_minutes || 60} دقيقة</span>
                </div>
                
                ${circle.schedule_days && circle.schedule_days.length > 0 ? `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>${formatScheduleDays(circle.schedule_days)}</span>
                    </div>
                ` : ''}
                
                ${circle.schedule_time ? `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>${circle.schedule_time}</span>
                    </div>
                ` : ''}
            </div>
        </div>
    `).join('');

    // Add click listeners
    container.querySelectorAll('.circle-card').forEach(card => {
        card.addEventListener('click', function () {
            selectCircle(this);
        });
    });
}

function displayIndividualCircles(circles) {
    const container = document.getElementById('individualCirclesList');
    const empty = document.getElementById('individualCirclesEmpty');

    if (circles.length === 0) {
        container.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    container.style.display = 'grid';
    empty.style.display = 'none';

    container.innerHTML = circles.map(circle => `
        <div class="circle-card border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors" 
             data-circle-id="${circle.id}" data-circle-type="individual">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">${circle.student_name || 'طالب غير محدد'}</h4>
                <span class="px-2 py-1 rounded-full text-xs font-medium ${circle.sessions_scheduled > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${circle.sessions_scheduled > 0 ? 'مجدولة' : 'غير مجدولة'}
                </span>
            </div>
            
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>${circle.sessions_scheduled}/${circle.total_sessions} جلسة</span>
                </div>
                
                ${circle.subscription_start ? `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>${formatDate(circle.subscription_start)} - ${formatDate(circle.subscription_end)}</span>
                    </div>
                ` : ''}
            </div>
        </div>
    `).join('');

    // Add click listeners
    container.querySelectorAll('.circle-card').forEach(card => {
        card.addEventListener('click', function () {
            selectCircle(this);
        });
    });
}

function selectCircle(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.circle-card').forEach(card => {
        card.classList.remove('border-blue-500', 'bg-blue-100');
    });

    // Add selection to clicked card
    cardElement.classList.add('border-blue-500', 'bg-blue-100');

    // Store selection
    selectedCircle = cardElement.dataset.circleId;
    selectedCircleType = cardElement.dataset.circleType;

    // Enable bulk schedule button
    document.getElementById('bulkScheduleBtn').disabled = false;
}

function updateCircleCounts(data) {
    document.getElementById('groupCirclesCount').textContent = data.groupCircles?.length || 0;
    document.getElementById('individualCirclesCount').textContent = data.individualCircles?.length || 0;
}

function formatScheduleDays(days) {
    const dayNames = {
        'saturday': 'السبت',
        'sunday': 'الأحد',
        'monday': 'الاثنين',
        'tuesday': 'الثلاثاء',
        'wednesday': 'الأربعاء',
        'thursday': 'الخميس',
        'friday': 'الجمعة'
    };

    return days.map(day => dayNames[day] || day).join(', ');
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Bulk scheduling functionality
function initializeBulkScheduling() {
    const modal = document.getElementById('bulkSchedulingModal');
    const bulkScheduleBtn = document.getElementById('bulkScheduleBtn');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelScheduling');
    const form = document.getElementById('bulkSchedulingForm');

    bulkScheduleBtn.addEventListener('click', openBulkSchedulingModal);
    closeModal.addEventListener('click', closeBulkSchedulingModal);
    cancelBtn.addEventListener('click', closeBulkSchedulingModal);
    form.addEventListener('submit', handleBulkScheduling);

    // Close modal when clicking outside
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeBulkSchedulingModal();
        }
    });
}

function openBulkSchedulingModal() {
    if (!selectedCircle || !selectedCircleType) return;

    const modal = document.getElementById('bulkSchedulingModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Populate circle info and constraints
    populateModalInfo();
}

function closeBulkSchedulingModal() {
    const modal = document.getElementById('bulkSchedulingModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    // Reset form
    document.getElementById('bulkSchedulingForm').reset();
}

function populateModalInfo() {
    // This would be populated with actual circle data
    const circleInfo = document.getElementById('selectedCircleInfo');
    circleInfo.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium">الحلقة المختارة: ${selectedCircleType === 'group' ? 'جماعية' : 'فردية'}</span>
        </div>
    `;

    // Set maximum days limit based on circle type and monthly sessions
    // This would be calculated based on actual circle data
    const maxDays = selectedCircleType === 'group' ? 5 : 7; // Example logic
    document.getElementById('maxDaysCount').textContent = maxDays;
}

function handleBulkScheduling(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const scheduleDays = formData.getAll('schedule_days[]');
    const scheduleTime = formData.get('schedule_time');

    // Validation
    if (scheduleDays.length === 0) {
        document.getElementById('daysError').classList.remove('hidden');
        return;
    }

    document.getElementById('daysError').classList.add('hidden');

    // Prepare data
    const scheduleData = {
        circle_id: selectedCircle,
        circle_type: selectedCircleType,
        schedule_days: scheduleDays,
        schedule_time: scheduleTime
    };

    // Submit bulk scheduling
    fetch('/teacher/api/bulk-schedule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.getCsrfToken?.() || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify(scheduleData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeBulkSchedulingModal();
                // Refresh calendar and circles
                loadCircles();
                // You might want to refresh the calendar widget here
                location.reload(); // Temporary solution
            } else {
                window.toast?.error('حدث خطأ في جدولة الجلسات');
            }
        })
        .catch(() => {
            window.toast?.error('حدث خطأ في جدولة الجلسات');
        });
}