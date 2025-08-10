<!-- Session Scheduling Modal -->
<div id="sessionScheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="scheduleSessionForm" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full mt-3 text-center sm:mt-0 sm:text-right">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            <i class="ri-calendar-line text-blue-600 ml-2"></i>
                            جدولة جلسة جديدة
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Template Session Selection -->
                            <div>
                                <label for="template_session_id" class="block text-sm font-medium text-gray-700 mb-2">اختر الجلسة</label>
                                <select name="template_session_id" id="template_session_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">اختر جلسة من القوالب المتاحة...</option>
                                </select>
                            </div>

                            <!-- Schedule Date & Time -->
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">موعد الجلسة</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>

                            <!-- Session Title (Optional Override) -->
                            <div>
                                <label for="session_title" class="block text-sm font-medium text-gray-700 mb-2">عنوان الجلسة (اختياري)</label>
                                <input type="text" name="title" id="session_title" maxlength="255"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="عنوان مخصص للجلسة...">
                            </div>

                            <!-- Session Description (Optional Override) -->
                            <div>
                                <label for="session_description" class="block text-sm font-medium text-gray-700 mb-2">وصف الجلسة (اختياري)</label>
                                <textarea name="description" id="session_description" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="وصف مخصص لهذه الجلسة..."></textarea>
                            </div>

                            <!-- Lesson Objectives (Optional) -->
                            <div>
                                <label for="lesson_objectives" class="block text-sm font-medium text-gray-700 mb-2">أهداف الجلسة (اختياري)</label>
                                <div id="objectivesContainer">
                                    <div class="flex items-center space-x-2 space-x-reverse mb-2">
                                        <input type="text" name="lesson_objectives[]" 
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                            placeholder="هدف من أهداف الجلسة...">
                                        <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 remove-objective" style="display:none;">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="addObjective" class="text-sm text-primary-600 hover:text-primary-700">
                                    <i class="ri-add-line ml-1"></i>
                                    إضافة هدف آخر
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="ri-calendar-check-line ml-2"></i>
                        جدولة الجلسة
                    </button>
                    <button type="button" id="cancelSchedule"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('sessionScheduleModal');
    const form = document.getElementById('scheduleSessionForm');
    const templateSelect = document.getElementById('template_session_id');
    const cancelBtn = document.getElementById('cancelSchedule');
    const addObjectiveBtn = document.getElementById('addObjective');
    const objectivesContainer = document.getElementById('objectivesContainer');

    // Open modal function (called from parent page)
    window.openScheduleModal = function() {
        // Load template sessions
        loadTemplateSessions();
        
        // Set minimum datetime to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('scheduled_at').min = now.toISOString().slice(0, 16);
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    // Close modal function
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        form.reset();
        
        // Reset objectives to single input
        const objectives = objectivesContainer.querySelectorAll('.flex');
        objectives.forEach((obj, index) => {
            if (index > 0) obj.remove();
        });
        objectives[0].querySelector('.remove-objective').style.display = 'none';
    }

    // Load template sessions
    function loadTemplateSessions() {
        fetch(`{{ route('teacher.individual-circles.template-sessions', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    templateSelect.innerHTML = '<option value="">اختر جلسة من القوالب المتاحة...</option>';
                    data.sessions.forEach(session => {
                        const option = document.createElement('option');
                        option.value = session.id;
                        option.textContent = `${session.sequence}. ${session.title} (${session.duration} دقيقة)`;
                        templateSelect.appendChild(option);
                    });
                } else {
                    showToast('خطأ في تحميل الجلسات المتاحة', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading template sessions:', error);
                showToast('خطأ في تحميل الجلسات المتاحة', 'error');
            });
    }

    // Add objective functionality
    addObjectiveBtn.addEventListener('click', function() {
        const objectives = objectivesContainer.querySelectorAll('.flex');
        if (objectives.length < 5) { // Limit to 5 objectives
            const newObjective = objectives[0].cloneNode(true);
            newObjective.querySelector('input').value = '';
            newObjective.querySelector('.remove-objective').style.display = 'block';
            objectivesContainer.appendChild(newObjective);
            
            // Show remove button on all objectives if more than one
            if (objectives.length >= 1) {
                objectives.forEach(obj => {
                    obj.querySelector('.remove-objective').style.display = 'block';
                });
            }
        }
    });

    // Remove objective functionality
    objectivesContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-objective')) {
            const objectives = objectivesContainer.querySelectorAll('.flex');
            if (objectives.length > 1) {
                e.target.closest('.flex').remove();
                
                // Hide remove button if only one objective left
                const remainingObjectives = objectivesContainer.querySelectorAll('.flex');
                if (remainingObjectives.length === 1) {
                    remainingObjectives[0].querySelector('.remove-objective').style.display = 'none';
                }
            }
        }
    });

    // Cancel button
    cancelBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Collect objectives into array
        const objectives = [];
        this.querySelectorAll('input[name="lesson_objectives[]"]').forEach(input => {
            if (input.value.trim()) {
                objectives.push(input.value.trim());
            }
        });
        
        // Remove all objective inputs and add as single array
        const existingObjectives = this.querySelectorAll('input[name="lesson_objectives[]"]');
        existingObjectives.forEach(input => formData.delete('lesson_objectives[]'));
        
        objectives.forEach(objective => {
            formData.append('lesson_objectives[]', objective);
        });
        
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
                showToast(data.message, 'success');
                closeModal();
                
                // Refresh the page to show the new session
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('خطأ: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error scheduling session:', error);
            showToast('حدث خطأ في جدولة الجلسة', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>
