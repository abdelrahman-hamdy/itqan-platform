@props([
    'session',
    'viewType' => 'teacher',
    'sessionType' => 'academic' // 'academic' or 'interactive'
])

<!-- Academic Homework Management Section (Teacher View) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="ri-file-edit-line text-primary ml-2"></i>
        إدارة الواجب
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
                    وصف الواجب <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="homework_description"
                    name="homework_description"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                    placeholder="اكتب تفاصيل الواجب المطلوب..."
                    required></textarea>
            </div>
            <div>
                <label for="homework_file" class="block text-sm font-medium text-gray-700 mb-2">
                    مرفق الواجب (اختياري)
                </label>
                <input
                    type="file"
                    id="homework_file"
                    name="homework_file"
                    accept=".pdf,.doc,.docx"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-gray-500 mt-1">PDF أو Word (حد أقصى 10 ميجابايت)</p>
            </div>
            <button
                type="submit"
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                <i class="ri-send-plane-line ml-2"></i>
                تعيين الواجب
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
                    وصف الواجب <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="homework_description_edit"
                    name="homework_description"
                    rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                    placeholder="اكتب تفاصيل الواجب المطلوب..."
                    required>{{ $session->homework_description }}</textarea>
            </div>
            <div>
                <label for="homework_file_edit" class="block text-sm font-medium text-gray-700 mb-2">
                    مرفق الواجب (اختياري)
                </label>
                @if($session->homework_file)
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-600">
                        <i class="ri-file-line"></i>
                        <a href="{{ Storage::url($session->homework_file) }}"
                           target="_blank"
                           class="text-primary hover:underline">
                            المرفق الحالي
                        </a>
                    </div>
                @endif
                <input
                    type="file"
                    id="homework_file_edit"
                    name="homework_file"
                    accept=".pdf,.doc,.docx"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-gray-500 mt-1">PDF أو Word (حد أقصى 10 ميجابايت) - اترك فارغاً للاحتفاظ بالمرفق الحالي</p>
            </div>
            <button
                type="submit"
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                <i class="ri-save-line ml-2"></i>
                تحديث الواجب
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
                            <h4 class="font-semibold text-green-900 mb-2">تم تسليم الواجب</h4>
                            <p class="text-sm text-green-700 mb-2">
                                تاريخ التسليم: {{ $studentReport->homework_submitted_at->format('Y-m-d H:i') }}
                            </p>
                            @if($studentReport->homework_file)
                                <a href="{{ Storage::url($studentReport->homework_file) }}"
                                   target="_blank"
                                   class="inline-flex items-center text-green-600 hover:text-green-800 text-sm">
                                    <i class="ri-attachment-line ml-1"></i>
                                    تحميل إجابة الطالب
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
                                درجة الواجب <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="homework_grade"
                                name="homework_grade"
                                min="0"
                                max="10"
                                step="0.5"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="من 0 إلى 10"
                                required>
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                ملاحظات على الواجب (اختياري)
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary focus:border-primary"
                                placeholder="ملاحظاتك على أداء الطالب..."></textarea>
                        </div>
                        <button
                            type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="ri-check-line ml-2"></i>
                            حفظ التقييم
                        </button>
                    </form>
                @else
                    <!-- Display Graded Homework -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="ri-star-line text-purple-600 text-xl mt-1"></i>
                            <div class="flex-1">
                                <h4 class="font-semibold text-purple-900 mb-2">التقييم:</h4>
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
                        <p class="text-yellow-800">في انتظار تسليم الطالب للواجب</p>
                    </div>
                </div>
            @endif
        @else
            <!-- For interactive courses, show simple info message -->
            <div class="text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <i class="ri-information-line ml-1"></i>
                سيتمكن الطلاب من رؤية الواجب وتسليم إجاباتهم من صفحة الكورس الخاصة بهم
            </div>
        @endif
    @endif
</div>

<!-- Academic Homework AJAX Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Unified notification function (centered toast style)
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg text-white shadow-lg z-50 flex items-center gap-2 ${
            type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
        notification.innerHTML = `<i class="ri-${type === 'success' ? 'check' : type === 'error' ? 'close' : 'information'}-line"></i><span>${message}</span>`;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
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
            submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ الحفظ...';

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
                console.error('Error:', error);
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
            submitButton.innerHTML = '<i class="ri-loader-line animate-spin ml-2"></i>جارٍ الحفظ...';

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
                console.error('Error:', error);
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
