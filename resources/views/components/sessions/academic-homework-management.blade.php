@props([
    'session',
    'viewType' => 'teacher',
    'sessionType' => 'academic' // 'academic' or 'interactive'
])

<!-- Academic Homework Management Section (Teacher View) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="ri-file-edit-line text-primary ms-2"></i>
        {{ __('components.sessions.academic_homework.title') }}
    </h3>

    @if(!$session->homework_description)
        <!-- Assign Homework Form -->
        <form id="assignHomeworkForm"
              data-url="{{ route('teacher.' . ($sessionType === 'academic' ? 'academic' : 'interactive') . '-sessions.assign-homework', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf
            <div>
                <label for="homework_description" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('components.sessions.academic_homework.description_label_required') }}
                </label>
                <textarea
                    id="homework_description"
                    name="homework_description"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                    placeholder="{{ __('components.sessions.academic_homework.description_placeholder') }}"
                    required></textarea>
            </div>
            <div>
                <label for="homework_file" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('components.sessions.academic_homework.file_label') }}
                </label>
                <input
                    type="file"
                    id="homework_file"
                    name="homework_file"
                    accept=".pdf,.doc,.docx"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-gray-500 mt-1">{{ __('components.sessions.academic_homework.file_help') }}</p>
            </div>
            <button
                type="submit"
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                <i class="ri-send-plane-line ms-2"></i>
                {{ __('components.sessions.academic_homework.assign_homework') }}
            </button>
        </form>
    @else
        <!-- Edit Assigned Homework Form -->
        <form id="updateHomeworkForm"
              data-url="{{ route('teacher.' . ($sessionType === 'academic' ? 'academic' : 'interactive') . '-sessions.update-homework', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
              enctype="multipart/form-data"
              class="space-y-4 mb-4">
            @csrf
            <div>
                <label for="homework_description_edit" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('components.sessions.academic_homework.description_label_required') }}
                </label>
                <textarea
                    id="homework_description_edit"
                    name="homework_description"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                    placeholder="{{ __('components.sessions.academic_homework.description_placeholder') }}"
                    required>{{ $session->homework_description }}</textarea>
            </div>
            <div>
                <label for="homework_file_edit" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('components.sessions.academic_homework.file_label') }}
                </label>
                @if($session->homework_file)
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-600">
                        <i class="ri-file-line"></i>
                        <a href="{{ Storage::url($session->homework_file) }}"
                           target="_blank"
                           class="text-primary hover:underline">
                            {{ __('components.sessions.academic_homework.current_attachment') }}
                        </a>
                    </div>
                @endif
                <input
                    type="file"
                    id="homework_file_edit"
                    name="homework_file"
                    accept=".pdf,.doc,.docx"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-gray-500 mt-1">{{ __('components.sessions.academic_homework.keep_current_note') }}</p>
            </div>
            <button
                type="submit"
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                <i class="ri-save-line ms-2"></i>
                {{ __('components.sessions.academic_homework.update_homework') }}
            </button>
        </form>

        @if($sessionType === 'academic')
            @php
                $studentReport = $session->studentReports ? $session->studentReports->where('student_id', $session->student_id)->first() : null;
            @endphp

            @if($studentReport && $studentReport->homework_submitted_at)
                <!-- Student Submission -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <i class="ri-checkbox-circle-line text-green-600 text-xl mt-1"></i>
                        <div class="flex-1">
                            <h4 class="font-semibold text-green-900 mb-2">{{ __('components.sessions.academic_homework.submission_received') }}</h4>
                            <p class="text-sm text-green-700 mb-2">
                                {{ __('components.sessions.academic_homework.submission_date') }} {{ $studentReport->homework_submitted_at->format('Y-m-d H:i') }}
                            </p>
                            @if($studentReport->homework_file)
                                <a href="{{ Storage::url($studentReport->homework_file) }}"
                                   target="_blank"
                                   class="inline-flex items-center text-green-600 hover:text-green-800 text-sm">
                                    <i class="ri-attachment-line ms-1"></i>
                                    {{ __('components.sessions.academic_homework.download_answer') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if($studentReport->homework_degree === null)
                    <!-- Grade Homework Form -->
                    <form id="gradeHomeworkForm"
                          data-url="{{ route('teacher.academic-sessions.grade-homework', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id, 'reportId' => $studentReport->id]) }}"
                          class="space-y-4">
                        @csrf
                        <div>
                            <label for="homework_grade" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('components.sessions.academic_homework.grade_label') }}
                            </label>
                            <input
                                type="number"
                                id="homework_grade"
                                name="homework_grade"
                                min="0"
                                max="10"
                                step="0.5"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="{{ __('components.sessions.academic_homework.grade_placeholder') }}"
                                required>
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('components.sessions.academic_homework.notes_label') }}
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="{{ __('components.sessions.academic_homework.notes_placeholder') }}"></textarea>
                        </div>
                        <button
                            type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="ri-check-line ms-2"></i>
                            {{ __('components.sessions.academic_homework.save_grade') }}
                        </button>
                    </form>
                @else
                    <!-- Display Graded Homework -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="ri-star-line text-purple-600 text-xl mt-1"></i>
                            <div class="flex-1">
                                <h4 class="font-semibold text-purple-900 mb-2">{{ __('components.sessions.academic_homework.grading_title') }}</h4>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl font-bold text-purple-600">
                                        {{ $studentReport->homework_degree }}/10
                                    </span>
                                </div>
                                @if($studentReport->notes)
                                    <p class="text-purple-800 text-sm">
                                        {{ $studentReport->notes }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Waiting for Student Submission -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <i class="ri-time-line text-yellow-600 text-xl"></i>
                        <p class="text-yellow-800">{{ __('components.sessions.academic_homework.waiting_submission') }}</p>
                    </div>
                </div>
            @endif
        @else
            <!-- For interactive courses, show simple info message -->
            <div class="text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <i class="ri-information-line ms-1"></i>
                {{ __('components.sessions.academic_homework.students_can_view') }}
            </div>
        @endif
    @endif
</div>

<!-- Academic Homework AJAX Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Use unified toast notification system
    function showNotification(message, type = 'info') {
        if (window.toast) {
            window.toast.show({ type: type, message: message });
        } else {
        }
    }

    // Generic form submission handler
    function handleHomeworkFormSubmit(form, method = 'POST', successMessage = 'تم الحفظ بنجاح') {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const url = this.dataset.url;
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>جارٍ الحفظ...';

            fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(successMessage, 'success');
                    // Reload page after short delay to show updated content
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                showNotification('حدث خطأ أثناء الحفظ', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }

    // Assign homework form
    const assignForm = document.getElementById('assignHomeworkForm');
    if (assignForm) {
        handleHomeworkFormSubmit(assignForm, 'POST', 'تم تعيين الواجب بنجاح');
    }

    // Update homework form (uses PUT method)
    const updateForm = document.getElementById('updateHomeworkForm');
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('_method', 'PUT'); // Add method spoofing for Laravel
            const url = this.dataset.url;
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="ri-loader-line animate-spin ms-2"></i>جارٍ الحفظ...';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('تم تحديث الواجب بنجاح', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                showNotification('حدث خطأ أثناء الحفظ', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }

    // Grade homework form
    const gradeForm = document.getElementById('gradeHomeworkForm');
    if (gradeForm) {
        handleHomeworkFormSubmit(gradeForm, 'POST', 'تم حفظ التقييم بنجاح');
    }
});
</script>
