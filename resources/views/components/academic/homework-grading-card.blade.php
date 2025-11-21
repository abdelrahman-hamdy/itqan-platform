@props([
    'session',  // Academic session object
    'report',   // Session report object
    'student'   // Student object
])

@php
    $hasHomework = !empty($session->homework_description) || !empty($session->homework_file);
    $hasSubmitted = $report->homework_submitted_at !== null;
    $isGraded = $report->homework_completion_degree !== null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">
            <i class="ri-file-edit-line ml-2"></i>
            تقييم الواجب - {{ $student->full_name }}
        </h2>
        @if($isGraded)
            <div class="flex items-center px-3 py-1 bg-green-100 border border-green-300 rounded-full">
                <i class="ri-checkbox-circle-line text-green-600 ml-1"></i>
                <span class="text-xs font-bold text-green-700">تم التقييم</span>
            </div>
        @endif
    </div>

    @if(!$hasHomework)
        <!-- No Homework Assigned -->
        <div class="text-center py-8">
            <i class="ri-file-list-line text-6xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm">لم يتم تعيين واجب لهذه الجلسة</p>
            <button type="button"
                    onclick="toggleAssignHomework()"
                    class="mt-4 px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-dark transition-colors">
                <i class="ri-add-line ml-1"></i>
                تعيين واجب
            </button>
        </div>

        <!-- Assign Homework Form (Hidden by default) -->
        <div id="assign-homework-form" class="hidden mt-4">
            <form action="{{ route('teacher.academic-sessions.assign-homework', $session->id) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">وصف الواجب</label>
                    <textarea name="homework_description"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="اكتب تفاصيل الواجب المطلوب..."
                              required></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">ملف الواجب (اختياري)</label>
                    <input type="file"
                           name="homework_file"
                           accept=".pdf,.doc,.docx"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-colors">
                        حفظ الواجب
                    </button>
                    <button type="button"
                            onclick="toggleAssignHomework()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-colors">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    @else
        <!-- Homework Details -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <label class="block text-sm font-bold text-gray-700 mb-2">وصف الواجب</label>
            <p class="text-gray-700 whitespace-pre-wrap">{{ $session->homework_description }}</p>

            @if($session->homework_file)
                <a href="{{ Storage::url($session->homework_file) }}"
                   target="_blank"
                   class="inline-flex items-center mt-2 text-sm text-blue-600 hover:text-blue-700">
                    <i class="ri-attachment-line ml-1"></i>
                    {{ basename($session->homework_file) }}
                </a>
            @endif
        </div>

        @if(!$hasSubmitted)
            <!-- Waiting for Submission -->
            <div class="text-center py-6 bg-yellow-50 rounded-lg border border-yellow-200">
                <i class="ri-time-line text-4xl text-yellow-500 mb-2"></i>
                <p class="text-yellow-700 text-sm font-bold">في انتظار تسليم الطالب</p>
            </div>
        @else
            <!-- Student Submission -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-gray-700">تاريخ التسليم</label>
                        <span class="text-sm text-gray-600">{{ $report->homework_submitted_at->format('Y-m-d H:i') }}</span>
                    </div>

                    @if($report->homework_file)
                        <a href="{{ Storage::url($report->homework_file) }}"
                           target="_blank"
                           class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                            <i class="ri-file-line text-blue-600 text-xl ml-2"></i>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-blue-900">عرض ملف الطالب</div>
                                <div class="text-xs text-blue-600">{{ basename($report->homework_file) }}</div>
                            </div>
                            <i class="ri-arrow-left-s-line text-blue-600"></i>
                        </a>
                    @endif
                </div>

                @if($isGraded)
                    <!-- Display Current Grade -->
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-bold text-green-900">الدرجة الحالية</label>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-green-600">{{ number_format($report->homework_completion_degree, 1) }}</span>
                                <span class="text-sm text-green-600 mr-1">/10</span>
                            </div>
                        </div>
                        @if($report->homework_feedback)
                            <div class="text-sm text-green-800 mt-2">
                                <strong>الملاحظات:</strong> {{ $report->homework_feedback }}
                            </div>
                        @endif
                        <button type="button"
                                onclick="toggleGradingForm()"
                                class="mt-3 w-full px-3 py-2 bg-white border border-green-300 text-green-700 text-sm font-bold rounded hover:bg-green-50 transition-colors">
                            <i class="ri-edit-line ml-1"></i>
                            تعديل التقييم
                        </button>
                    </div>
                @endif

                <!-- Grading Form -->
                <form action="{{ route('teacher.academic-sessions.grade-homework', [$session->id, $report->id]) }}"
                      method="POST"
                      id="grading-form"
                      class="{{ $isGraded ? 'hidden' : '' }}">
                    @csrf

                    <!-- Homework Grade -->
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="ri-star-line ml-1"></i>
                            درجة الواجب (من 0 إلى 10)
                        </label>
                        <input type="number"
                               name="homework_grade"
                               min="0"
                               max="10"
                               step="0.5"
                               value="{{ $report->homework_completion_degree ?? '' }}"
                               class="w-full px-4 py-2 text-lg font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                               required>
                    </div>

                    <!-- Feedback -->
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="ri-message-2-line ml-1"></i>
                            ملاحظات وتعليقات
                        </label>
                        <textarea name="homework_feedback"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                                  placeholder="اكتب ملاحظاتك وتعليقاتك على أداء الطالب...">{{ $report->homework_feedback ?? '' }}</textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full flex items-center justify-center px-4 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="ri-check-line ml-2"></i>
                        {{ $isGraded ? 'تحديث التقييم' : 'حفظ التقييم' }}
                    </button>

                    @if($isGraded)
                        <button type="button"
                                onclick="toggleGradingForm()"
                                class="w-full mt-2 px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-colors">
                            إلغاء
                        </button>
                    @endif
                </form>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    function toggleAssignHomework() {
        const form = document.getElementById('assign-homework-form');
        form.classList.toggle('hidden');
    }

    function toggleGradingForm() {
        const form = document.getElementById('grading-form');
        form.classList.toggle('hidden');
    }
</script>
@endpush
