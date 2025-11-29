@props([
    'session',           // Academic session object
    'report',            // Session report object (optional)
    'canSubmit' => true  // Whether student can submit
])

@php
    $hasHomework = !empty($session->homework_description) || !empty($session->homework_file);
    $hasSubmitted = $report && $report->homework_submitted_at !== null;
    $isGraded = $report && $report->homework_degree !== null;

    // Determine status
    $status = 'not_assigned';
    $statusColor = 'gray';
    $statusIcon = 'ri-file-line';
    $statusText = 'لم يتم تعيين واجب';

    if ($hasHomework) {
        if ($isGraded) {
            $status = 'graded';
            $statusColor = 'green';
            $statusIcon = 'ri-checkbox-circle-line';
            $statusText = 'تم التقييم';
        } elseif ($hasSubmitted) {
            $status = 'submitted';
            $statusColor = 'blue';
            $statusIcon = 'ri-send-plane-fill';
            $statusText = 'تم التسليم';
        } else {
            $status = 'pending';
            $statusColor = 'yellow';
            $statusIcon = 'ri-time-line';
            $statusText = 'في انتظار التسليم';
        }
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">الواجب المنزلي</h2>
        <div class="flex items-center px-3 py-1 bg-{{ $statusColor }}-100 border border-{{ $statusColor }}-300 rounded-full">
            <i class="{{ $statusIcon }} text-{{ $statusColor }}-600 ml-1"></i>
            <span class="text-xs font-bold text-{{ $statusColor }}-700">{{ $statusText }}</span>
        </div>
    </div>

    @if(!$hasHomework)
        <!-- No Homework Assigned -->
        <div class="text-center py-8">
            <i class="ri-file-list-line text-6xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm">لم يقم المعلم بتعيين واجب لهذه الجلسة</p>
        </div>
    @else
        <!-- Homework Description -->
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="ri-file-text-line ml-1"></i>
                وصف الواجب
            </label>
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-gray-700 whitespace-pre-wrap">{{ $session->homework_description }}</p>
            </div>
        </div>

        <!-- Teacher's Homework File (if exists) -->
        @if($session->homework_file)
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="ri-attachment-line ml-1"></i>
                    ملف الواجب
                </label>
                <a href="{{ Storage::url($session->homework_file) }}"
                   target="_blank"
                   class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                    <i class="ri-file-download-line text-blue-600 text-xl ml-2"></i>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-blue-900">تحميل ملف الواجب</div>
                        <div class="text-xs text-blue-600">{{ basename($session->homework_file) }}</div>
                    </div>
                    <i class="ri-arrow-left-s-line text-blue-600"></i>
                </a>
            </div>
        @endif

        @if($hasSubmitted)
            <!-- Student's Submission -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="ri-check-line ml-1"></i>
                    حالة التسليم
                </label>

                <div class="p-4 bg-green-50 rounded-lg border border-green-200 mb-3">
                    <div class="flex items-center text-green-700">
                        <i class="ri-checkbox-circle-line text-green-600 text-xl ml-2"></i>
                        <div>
                            <div class="text-sm font-bold">تم التسليم بنجاح</div>
                            <div class="text-xs">{{ $report->homework_submitted_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>

                @if($report->homework_file)
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">الملف المرفق</label>
                        <a href="{{ Storage::url($report->homework_file) }}"
                           target="_blank"
                           class="flex items-center p-2 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100 transition-colors text-sm">
                            <i class="ri-file-line text-gray-600 ml-2"></i>
                            <span class="text-gray-700">{{ basename($report->homework_file) }}</span>
                        </a>
                    </div>
                @endif

                @if($isGraded)
                    <!-- Teacher's Feedback -->
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-bold text-blue-900">تقييم المعلم</label>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-blue-600">{{ number_format($report->homework_degree, 1) }}</span>
                                <span class="text-sm text-blue-600 mr-1">/10</span>
                            </div>
                        </div>
                        @if($report->notes)
                            <div class="text-sm text-blue-800 mt-2">
                                <i class="ri-message-2-line ml-1"></i>
                                {{ $report->notes }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <div class="flex items-center text-yellow-700 text-sm">
                            <i class="ri-time-line text-yellow-600 ml-2"></i>
                            <span>في انتظار تقييم المعلم</span>
                        </div>
                    </div>
                @endif
            </div>
        @elseif($canSubmit)
            <!-- Submission Form -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <form action="{{ route('student.academic-sessions.submit-homework', $session->id) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      id="homework-submission-form">
                    @csrf

                    <!-- File Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="ri-upload-line ml-1"></i>
                            رفع الواجب
                            <span class="text-xs text-gray-500 font-normal">(PDF, Word, صور)</span>
                        </label>
                        <input type="file"
                               name="homework_file"
                               id="homework_file"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-primary"
                               required>
                        <p class="mt-1 text-xs text-gray-500">الحد الأقصى لحجم الملف: 10 ميجابايت</p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full flex items-center justify-center px-4 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="ri-send-plane-fill ml-2"></i>
                        تسليم الواجب
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    // Form submission handling
    document.getElementById('homework-submission-form')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin ml-2"></i> جاري التسليم...';
    });
</script>
@endpush
