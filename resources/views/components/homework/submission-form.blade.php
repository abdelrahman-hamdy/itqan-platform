@props([
    'homework',
    'submission' => null,
    'homeworkType' => 'academic', // 'academic', 'quran', 'interactive'
    'action' => null, // Form action URL
    'method' => 'POST',
])

@php
    $isDraft = $submission && in_array($submission->submission_status, ['not_submitted', 'draft']);
    $canSubmit = !$submission || $isDraft;
    $isLate = $homework->due_date && now()->isAfter($homework->due_date);
    $allowLateSubmission = $homework->allow_late_submissions ?? true;

    // Determine if can actually submit
    $canActuallySubmit = $canSubmit && (!$isLate || $allowLateSubmission);
@endphp

<!-- Homework Submission Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-900">{{ $homework->title ?? 'تسليم الواجب' }}</h3>
            @if($homework->due_date)
                <p class="text-sm text-gray-600 mt-1">
                    <i class="ri-calendar-line ml-1"></i>
                    <span>موعد التسليم: </span>
                    <span class="{{ $isLate ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                        {{ $homework->due_date->format('Y-m-d h:i A') }}
                    </span>
                    @if($isLate)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mr-2">
                            <i class="ri-error-warning-line ml-1"></i>
                            متأخر
                        </span>
                    @endif
                </p>
            @endif
        </div>

        @if($submission && $submission->submission_status)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $submission->submission_status === \App\Enums\HomeworkSubmissionStatus::NOT_STARTED ? 'bg-gray-100 text-gray-800' :
                   ($submission->submission_status === \App\Enums\HomeworkSubmissionStatus::DRAFT ? 'bg-yellow-100 text-yellow-800' :
                   (in_array($submission->submission_status, [\App\Enums\HomeworkSubmissionStatus::SUBMITTED, \App\Enums\HomeworkSubmissionStatus::LATE]) ? 'bg-blue-100 text-blue-800' :
                   (in_array($submission->submission_status, [\App\Enums\HomeworkSubmissionStatus::GRADED, \App\Enums\HomeworkSubmissionStatus::RETURNED]) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'))) }}">
                {{ $submission->submission_status->label() }}
            </span>
        @endif
    </div>

    @if(!$canActuallySubmit)
        <!-- Cannot Submit Message -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-red-600 text-xl ml-2 flex-shrink-0"></i>
                <div>
                    <h4 class="font-semibold text-red-900 mb-1">لا يمكن تسليم الواجب</h4>
                    <p class="text-sm text-red-700">
                        @if($isLate && !$allowLateSubmission)
                            انتهى موعد التسليم ولا يُسمح بالتسليم المتأخر.
                        @elseif($submission && !$isDraft)
                            تم تسليم الواجب مسبقاً.
                        @else
                            غير متاح للتسليم حالياً.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @else
        @if($isLate && $allowLateSubmission)
            <!-- Late Submission Warning -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="ri-time-line text-yellow-600 text-xl ml-2 flex-shrink-0"></i>
                    <div>
                        <h4 class="font-semibold text-yellow-900 mb-1">تنبيه: تسليم متأخر</h4>
                        <p class="text-sm text-yellow-700">
                            لقد انتهى موعد التسليم. سيتم تسجيل هذا الواجب كتسليم متأخر وقد يتم خصم درجات.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Homework Description -->
        @if($homework->description || $homework->instructions)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-blue-900 mb-2 flex items-center">
                    <i class="ri-information-line ml-2"></i>
                    تفاصيل الواجب
                </h4>

                @if($homework->description)
                    <div class="text-sm text-blue-800 mb-3">
                        <p class="font-medium mb-1">الوصف:</p>
                        <p class="leading-relaxed">{{ $homework->description }}</p>
                    </div>
                @endif

                @if($homework->instructions)
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">التعليمات:</p>
                        <p class="leading-relaxed">{{ $homework->instructions }}</p>
                    </div>
                @endif

                @if($homework->teacher_files && count($homework->teacher_files) > 0)
                    <div class="mt-3 pt-3 border-t border-blue-300">
                        <p class="font-medium text-blue-900 mb-2 text-sm">الملفات المرفقة من المعلم:</p>
                        <div class="space-y-2">
                            @foreach($homework->teacher_files as $file)
                                <a href="{{ Storage::url($file['path']) }}"
                                   target="_blank"
                                   class="inline-flex items-center text-sm text-blue-700 hover:text-blue-900 transition-colors">
                                    <i class="ri-attachment-line ml-1"></i>
                                    {{ $file['original_name'] ?? 'ملف مرفق' }}
                                    <span class="text-xs text-blue-600 mr-1">({{ number_format(($file['size'] ?? 0) / 1024, 2) }} KB)</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Submission Form -->
        <form
            action="{{ $action }}"
            method="POST"
            enctype="multipart/form-data"
            id="homeworkSubmissionForm"
            class="space-y-6">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <!-- Text Submission -->
            @if(in_array($homework->submission_type ?? 'both', ['text', 'both']))
                <div>
                    <label for="submission_text" class="block text-sm font-semibold text-gray-900 mb-2">
                        <i class="ri-edit-line ml-1"></i>
                        حل الواجب
                        @if(($homework->submission_type ?? 'both') === 'text')
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <textarea
                        id="submission_text"
                        name="submission_text"
                        rows="8"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="اكتب حل الواجب هنا..."
                        {{ ($homework->submission_type ?? 'both') === 'text' ? 'required' : '' }}>{{ $submission->submission_text ?? old('submission_text') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        اكتب حلك بشكل واضح ومفصل
                    </p>
                    @error('submission_text')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- File Upload -->
            @if(in_array($homework->submission_type ?? 'both', ['file', 'both']))
                <div>
                    <label for="submission_files" class="block text-sm font-semibold text-gray-900 mb-2">
                        <i class="ri-file-upload-line ml-1"></i>
                        رفع الملفات
                        @if(($homework->submission_type ?? 'both') === 'file')
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                        <input
                            type="file"
                            id="submission_files"
                            name="submission_files[]"
                            multiple
                            accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.ppt,.pptx,.xls,.xlsx"
                            class="hidden"
                            {{ ($homework->submission_type ?? 'both') === 'file' ? 'required' : '' }}
                            onchange="displaySelectedFiles(this)">
                        <label for="submission_files" class="cursor-pointer">
                            <div class="text-gray-600">
                                <i class="ri-upload-cloud-2-line text-4xl mb-2"></i>
                                <p class="font-medium">انقر لرفع الملفات</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    الحد الأقصى: {{ $homework->max_files ?? 5 }} ملفات، حجم كل ملف: {{ $homework->max_file_size_mb ?? 10 }}MB
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    الصيغ المدعومة: PDF, Word, Excel, PowerPoint, الصور, النصوص
                                </p>
                            </div>
                        </label>
                    </div>
                    <div id="selectedFilesDisplay" class="mt-3 space-y-2 hidden">
                        <!-- Selected files will be displayed here -->
                    </div>
                    @error('submission_files')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('submission_files.*')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Existing Files (for drafts) -->
                @if($submission && $submission->submission_files && count($submission->submission_files) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="font-medium text-gray-900 mb-2 text-sm flex items-center">
                            <i class="ri-file-list-line ml-1"></i>
                            الملفات المرفقة سابقاً:
                        </p>
                        <div class="space-y-2">
                            @foreach($submission->submission_files as $index => $file)
                                <div class="flex items-center justify-between bg-white rounded px-3 py-2">
                                    <a href="{{ Storage::url($file['path']) }}"
                                       target="_blank"
                                       class="flex items-center text-sm text-blue-600 hover:text-blue-800">
                                        <i class="ri-file-line ml-1"></i>
                                        {{ $file['original_name'] ?? "ملف {$index + 1}" }}
                                        <span class="text-xs text-gray-500 mr-2">({{ number_format(($file['size'] ?? 0) / 1024, 2) }} KB)</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <!-- Submission Notes -->
            <div>
                <label for="submission_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-sticky-note-line ml-1"></i>
                    ملاحظات إضافية (اختياري)
                </label>
                <textarea
                    id="submission_notes"
                    name="submission_notes"
                    rows="3"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="أي ملاحظات أو تعليقات تود إضافتها...">{{ $submission->submission_notes ?? old('submission_notes') }}</textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-200">
                <button
                    type="submit"
                    name="action"
                    value="submit"
                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-[1.02] shadow-md hover:shadow-lg">
                    <i class="ri-send-plane-fill ml-2"></i>
                    تسليم الواجب
                </button>

                <button
                    type="submit"
                    name="action"
                    value="draft"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors">
                    <i class="ri-save-line ml-2"></i>
                    حفظ كمسودة
                </button>
            </div>
        </form>
    @endif
</div>

<script>
function displaySelectedFiles(input) {
    const display = document.getElementById('selectedFilesDisplay');
    const files = input.files;

    if (files.length === 0) {
        display.classList.add('hidden');
        return;
    }

    display.classList.remove('hidden');
    display.innerHTML = '';

    Array.from(files).forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between bg-gray-50 rounded px-3 py-2';
        fileItem.innerHTML = `
            <div class="flex items-center text-sm">
                <i class="ri-file-line text-blue-600 ml-2"></i>
                <span class="font-medium text-gray-900">${file.name}</span>
                <span class="text-xs text-gray-500 mr-2">(${(file.size / 1024).toFixed(2)} KB)</span>
            </div>
            <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                <i class="ri-close-line text-lg"></i>
            </button>
        `;
        display.appendChild(fileItem);
    });
}

function removeFile(index) {
    const input = document.getElementById('submission_files');
    const dt = new DataTransfer();
    const files = input.files;

    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }

    input.files = dt.files;
    displaySelectedFiles(input);
}

// Form validation before submit
document.getElementById('homeworkSubmissionForm')?.addEventListener('submit', function(e) {
    const action = e.submitter.value;
    const textInput = document.getElementById('submission_text');
    const fileInput = document.getElementById('submission_files');
    const submissionType = '{{ $homework->submission_type ?? "both" }}';

    // Only validate if submitting (not draft)
    if (action === 'submit') {
        let hasText = textInput && textInput.value.trim() !== '';
        let hasFiles = fileInput && fileInput.files.length > 0;

        if (submissionType === 'text' && !hasText) {
            e.preventDefault();
            alert('يرجى كتابة حل الواجب');
            textInput.focus();
            return false;
        }

        if (submissionType === 'file' && !hasFiles) {
            e.preventDefault();
            alert('يرجى رفع ملف واحد على الأقل');
            fileInput.focus();
            return false;
        }

        if (submissionType === 'both' && !hasText && !hasFiles) {
            e.preventDefault();
            alert('يرجى كتابة حل الواجب أو رفع ملف واحد على الأقل');
            return false;
        }
    }
});
</script>
