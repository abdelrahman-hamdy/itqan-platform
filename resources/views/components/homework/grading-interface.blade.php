@props([
    'submission',
    'homework',
    'action',
    'method' => 'POST',
])

@php
    $canGrade = $submission && in_array($submission->submission_status, ['submitted', 'late', 'pending_review', 'under_review']);
    $isGraded = $submission && in_array($submission->submission_status, ['graded', 'returned']);
@endphp

<!-- Teacher Grading Interface -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900 flex items-center">
            <i class="ri-star-line text-yellow-600 ml-2"></i>
            تقييم الواجب
        </h3>
        @if($isGraded)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <i class="ri-check-line ml-1"></i>
                تم التقييم
            </span>
        @endif
    </div>

    @if(!$canGrade && !$isGraded)
        <!-- Cannot Grade Message -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <i class="ri-information-line text-yellow-600 text-xl ml-2 flex-shrink-0"></i>
                <div>
                    <h4 class="font-semibold text-yellow-900 mb-1">لا يمكن تقييم الواجب</h4>
                    <p class="text-sm text-yellow-700">
                        @if(!$submission)
                            لم يتم تسليم الواجب بعد.
                        @else
                            الواجب في حالة: {{ $submission->submission_status_text }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- Grading Form -->
        <form action="{{ $action }}" method="POST" id="gradingForm" class="space-y-6">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <!-- Student Submission Display -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <i class="ri-file-text-line text-gray-600 ml-1"></i>
                    إجابة الطالب
                </h4>

                @if($submission->submission_text)
                    <div class="bg-white rounded p-4 mb-3">
                        <p class="text-sm font-medium text-gray-700 mb-2">النص:</p>
                        <p class="text-gray-800 whitespace-pre-wrap">{{ $submission->submission_text }}</p>
                    </div>
                @endif

                @if($submission->submission_files && count($submission->submission_files) > 0)
                    <div class="bg-white rounded p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">الملفات المرفقة:</p>
                        <div class="space-y-2">
                            @foreach($submission->submission_files as $file)
                                <a href="{{ Storage::url($file['path']) }}"
                                   target="_blank"
                                   class="flex items-center p-2 hover:bg-gray-50 rounded transition-colors group">
                                    <i class="ri-file-line text-blue-600 ml-2"></i>
                                    <span class="text-sm text-gray-900 group-hover:text-blue-600">
                                        {{ $file['original_name'] ?? 'ملف مرفق' }}
                                    </span>
                                    <span class="text-xs text-gray-500 mr-2">({{ number_format(($file['size'] ?? 0) / 1024, 2) }} KB)</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Main Score -->
            <div>
                <label for="score" class="block text-sm font-semibold text-gray-900 mb-2">
                    <i class="ri-star-line ml-1"></i>
                    الدرجة الإجمالية <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input
                        type="number"
                        id="score"
                        name="score"
                        min="0"
                        max="{{ $homework->max_score ?? 100 }}"
                        step="0.1"
                        value="{{ old('score', $submission->score) }}"
                        {{ !$canGrade ? 'readonly' : '' }}
                        class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-semibold"
                        placeholder="0.00"
                        required>
                    <span class="text-lg font-semibold text-gray-600">/ {{ $homework->max_score ?? 100 }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    الحد الأقصى: {{ $homework->max_score ?? 100 }} درجة
                </p>
                @error('score')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Quality Scores (Optional) -->
            <div class="border-t border-gray-200 pt-6">
                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="ri-bar-chart-line ml-1"></i>
                    تقييمات تفصيلية (اختياري)
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Content Quality -->
                    <div>
                        <label for="content_quality_score" class="block text-sm font-medium text-gray-700 mb-2">
                            جودة المحتوى
                        </label>
                        <input
                            type="number"
                            id="content_quality_score"
                            name="content_quality_score"
                            min="0"
                            max="100"
                            step="0.1"
                            value="{{ old('content_quality_score', $submission->content_quality_score) }}"
                            {{ !$canGrade ? 'readonly' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                            placeholder="0-100">
                    </div>

                    <!-- Presentation -->
                    <div>
                        <label for="presentation_score" class="block text-sm font-medium text-gray-700 mb-2">
                            العرض والتنسيق
                        </label>
                        <input
                            type="number"
                            id="presentation_score"
                            name="presentation_score"
                            min="0"
                            max="100"
                            step="0.1"
                            value="{{ old('presentation_score', $submission->presentation_score) }}"
                            {{ !$canGrade ? 'readonly' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                            placeholder="0-100">
                    </div>

                    <!-- Effort -->
                    <div>
                        <label for="effort_score" class="block text-sm font-medium text-gray-700 mb-2">
                            الجهد المبذول
                        </label>
                        <input
                            type="number"
                            id="effort_score"
                            name="effort_score"
                            min="0"
                            max="100"
                            step="0.1"
                            value="{{ old('effort_score', $submission->effort_score) }}"
                            {{ !$canGrade ? 'readonly' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                            placeholder="0-100">
                    </div>

                    <!-- Creativity -->
                    <div>
                        <label for="creativity_score" class="block text-sm font-medium text-gray-700 mb-2">
                            الإبداع والتميز
                        </label>
                        <input
                            type="number"
                            id="creativity_score"
                            name="creativity_score"
                            min="0"
                            max="100"
                            step="0.1"
                            value="{{ old('creativity_score', $submission->creativity_score) }}"
                            {{ !$canGrade ? 'readonly' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                            placeholder="0-100">
                    </div>
                </div>
            </div>

            <!-- Teacher Feedback -->
            <div>
                <label for="teacher_feedback" class="block text-sm font-semibold text-gray-900 mb-2">
                    <i class="ri-feedback-line ml-1"></i>
                    ملاحظات وتعليقات <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="teacher_feedback"
                    name="teacher_feedback"
                    rows="6"
                    {{ !$canGrade ? 'readonly' : '' }}
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="اكتب ملاحظاتك وتعليقاتك على أداء الطالب..."
                    required>{{ old('teacher_feedback', $submission->teacher_feedback) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    قدم ملاحظات بناءة تساعد الطالب على التحسين
                </p>
                @error('teacher_feedback')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Grading Information -->
            @if($isGraded)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="ri-information-line text-green-600 text-xl ml-2 flex-shrink-0"></i>
                        <div class="text-sm">
                            <p class="font-medium text-green-900 mb-1">معلومات التقييم</p>
                            <p class="text-green-700">
                                تم التقييم بتاريخ: {{ $submission->graded_at->format('Y-m-d h:i A') }}
                            </p>
                            @if($submission->grader)
                                <p class="text-green-700">
                                    بواسطة: {{ $submission->grader->name }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            @if($canGrade)
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                    <button
                        type="submit"
                        name="action"
                        value="grade"
                        class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-[1.02] shadow-md hover:shadow-lg">
                        <i class="ri-check-double-line ml-2"></i>
                        حفظ التقييم
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="grade_and_return"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-[1.02] shadow-md hover:shadow-lg">
                        <i class="ri-send-plane-line ml-2"></i>
                        حفظ وإرجاع للطالب
                    </button>
                </div>
            @elseif($isGraded)
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                    <button
                        type="submit"
                        name="action"
                        value="update_grade"
                        class="flex-1 bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-[1.02] shadow-md hover:shadow-lg">
                        <i class="ri-edit-line ml-2"></i>
                        تحديث التقييم
                    </button>

                    @if($submission->submission_status !== \App\Enums\HomeworkSubmissionStatus::RETURNED)
                        <button
                            type="submit"
                            name="action"
                            value="return_to_student"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors">
                            <i class="ri-reply-line ml-2"></i>
                            إرجاع للطالب
                        </button>
                    @endif
                </div>
            @endif
        </form>
    @endif
</div>

<script>
// Auto-calculate percentage as score is entered
document.getElementById('score')?.addEventListener('input', function() {
    const score = parseFloat(this.value) || 0;
    const maxScore = parseFloat('{{ $homework->max_score ?? 100 }}');
    const percentage = (score / maxScore) * 100;

    // You can display the percentage somewhere if needed
});

// Form validation before submit
document.getElementById('gradingForm')?.addEventListener('submit', function(e) {
    const score = parseFloat(document.getElementById('score').value);
    const maxScore = parseFloat('{{ $homework->max_score ?? 100 }}');
    const feedback = document.getElementById('teacher_feedback').value.trim();

    if (isNaN(score) || score < 0 || score > maxScore) {
        e.preventDefault();
        window.toast?.warning(`يرجى إدخال درجة صحيحة بين 0 و ${maxScore}`);
        return false;
    }

    if (!feedback) {
        e.preventDefault();
        window.toast?.warning('يرجى كتابة ملاحظات وتعليقات على أداء الطالب');
        return false;
    }

    return confirm('هل أنت متأكد من حفظ التقييم؟');
});
</script>
